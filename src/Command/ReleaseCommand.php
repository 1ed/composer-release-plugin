<?php

/*
 * This file is part of the egabor/composer-release-plugin package.
 *
 * (c) Gábor Egyed <gabor.egyed@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace egabor\Composer\ReleasePlugin\Command;

use Composer\Command\BaseCommand;
use Composer\Util\Git as GitUtil;
use egabor\Composer\ReleasePlugin\Config;
use egabor\Composer\ReleasePlugin\ReleaseManager;
use egabor\Composer\ReleasePlugin\Util\Git;
use egabor\Composer\ReleasePlugin\Version;
use egabor\Composer\ReleasePlugin\VersionManipulator;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseCommand extends BaseCommand
{
    /**
     * @var Git
     */
    private $git;

    /**
     * @var ReleaseManager
     */
    private $releaseManager;

    protected function configure()
    {
        $this
            ->setName('release')
            ->setDescription('Creates a new tagged release.')
            ->addArgument('version', InputArgument::REQUIRED, 'Version to release.')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Message for the annotated tag.', 'Release %version%')
            ->addOption('no-push', 'np', InputOption::VALUE_NONE, 'Not push the new release tag.')
            ->addOption('latest-release', 'l', InputOption::VALUE_REQUIRED, 'Latest release version.')
            ->setHelp(<<<EOT
            
This command assists you to tag a new release for a package. It tries
to protect you from common mistakes and provide some shortcuts.

To create a new <comment>major|minor|patch|stable|alpha|beta|rc</comment> release:

    <comment>%command.full_name% major</comment>
    
This will create a new major release, make an annotated tag and push it 
to the remote branch. If you don't want to push right away, you can use
the <comment>--no-push</comment> option.

You can provide a custom message for the annotated tag like:

    <comment>%command.full_name% major --message "%version% released"</comment>
 
If provided the %version% placeholder will be replaced with the version. 

The command will inspect the repository and infer some information e.g. the 
previous release version. Based on these information it tries to protect 
you to make a wrong release. Sometimes when it can not detect the previous 
release version correctly or if you just want to override this behaviour 
than you can use the <comment>--latest-release</comment>:

    <comment>%command.full_name% major --latest-release "1.2.3"</comment>

EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        GitUtil::cleanEnv();

        $io = $this->getIO();
        $this->git = Git::create($io);
        $config = new Config($this->getComposer()->getPackage());

        $this->assertGitRepository();
        $this->assertGitCurrentBranch($config);
        $this->assertGitIsUpToDate();

        $io->writeError(sprintf('Using the <info>%s</info> branch as the release branch.', $config->getReleaseBranch()));

        $this->releaseManager = new ReleaseManager($config, $this->git);

        if (null !== $input->getOption('latest-release')) {
            return;
        }

        if (null !== $latestReleaseVersion = $this->releaseManager->getLatestReleaseVersion()) {
            $io->writeError('Latest release version: <info>'.$latestReleaseVersion.'</info>');
            $input->setOption('latest-release', $latestReleaseVersion);
            $this->assertGitHasChangesSince($latestReleaseVersion);
        } else {
            $io->writeError('<error>Latest release version can not be guessed or seems invalid.</error>');
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getIO();

        if (null === $input->getOption('latest-release')) {
            $answer = $io->askAndValidate('Please provide the latest released version [<comment>0.0.0</comment>]: ', function ($version) {
                if (null === $version) {
                    throw new \InvalidArgumentException('Version can not be empty.');
                }

                Version::fromString($version);

                return $version;
            }, 3, '0.0.0');
            $input->setOption('latest-release', $answer);
        }

        if (null === $input->getArgument('version')) {
            $answer = $io->askAndValidate('Please provide a version to release (eg. 1.0.0 or major): ', function ($version) {
                if (null === $version) {
                    throw new \InvalidArgumentException('Version can not be empty.');
                }

                if (!VersionManipulator::supportsLevel($version)) {
                    Version::fromString($version);
                }

                return $version;
            });
            $input->setArgument('version', $answer);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getIO();
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');

        $futureVersion = $input->getArgument('version');
        $latestReleaseVersion = Version::fromString($input->getOption('latest-release'));

        $futureVersion = $this->releaseManager->gatFutureVersion($futureVersion, $latestReleaseVersion);

        $doRelease = true;
        if ($input->isInteractive()) {
            $doRelease = $io->askConfirmation('<info>You are about to release version "'.$futureVersion.'". Do you want to continue?</info> [<comment>Y,n</comment>]? ', true);
        }

        if ($doRelease) {
            if ($input->getOption('no-push')) {
                $this->releaseManager->release($futureVersion, $input->getOption('message'));
            } else {
                $this->releaseManager->releaseAndPush($futureVersion, $input->getOption('message'));
            }

            $io->write([
                '',
                $formatter->formatBlock('Version "'.$futureVersion.'" has been released.', 'bg=green;fg=black', true),
                '',
            ]);
        } else {
            $io->write([
                '',
                $formatter->formatBlock('Aborted.', 'bg=red;fg=white', true),
                '',
            ]);
        }
    }

    private function assertGitRepository()
    {
        if (!is_dir('.git')) {
            throw new \RuntimeException('The .git directory is missing from "'.getcwd().'". Only git repositories are supported.');
        }
    }

    private function assertGitCurrentBranch(Config $config)
    {
        $branches = $this->git->getBranches();
        if (!count($branches)) {
            throw new \LogicException('The repository does not have any branches with commits. Please commit your work before release.');
        }

        if (!array_key_exists($config->getReleaseBranch(), $branches)) {
            throw new \LogicException('The release branch "'.$config->getReleaseBranch().'" does not exists. Please create and switch to the release branch.');
        }

        if ($this->git->getCurrentBranch() !== $config->getReleaseBranch()) {
            throw new \LogicException('Please switch to the release branch "'.$config->getReleaseBranch().'".');
        }
    }

    private function assertGitIsUpToDate()
    {
        if (!$this->git->hasUpstream()) {
            throw new \LogicException('The current branch does not have a remote tracking branch. Please setup one before continue.');
        }

        $this->git->fetch();

        $status = $this->git->getRemoteStatus();
        if (Git::STATUS_NEED_TO_PULL === $status) {
            throw new \LogicException('The current branch does not not in sync with the remote tracking branch. Please pull before continue.');
        }

        if (Git::STATUS_DIVERGED === $status) {
            throw new \LogicException('The current branch is diverged from its remote tracking branch. Please rebase or merge before continue.');
        }
    }

    private function assertGitHasChangesSince($rev)
    {
        if (1 > $this->git->countCommitsSince($rev)) {
            throw new \LogicException('There are no committed changes since the last release. Please commit your changes.');
        }
    }
}
