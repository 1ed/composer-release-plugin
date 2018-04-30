<?php

/*
 * This file is part of the egabor/composer-release-plugin package.
 *
 * (c) Gábor Egyed <gabor.egyed@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace egabor\Composer\ReleasePlugin\Util;

use Composer\IO\IOInterface;
use Composer\Util\ProcessExecutor;
use egabor\Composer\ReleasePlugin\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;

class Git
{
    const STATUS_UP_TO_DATE = 0;
    const STATUS_NEED_TO_PULL = 1;
    const STATUS_NEED_TO_PUSH = 2;
    const STATUS_DIVERGED = 3;

    private $processExecutor;
    private $gitBinPath;
    private $branches;
    private $currentBranch;
    private $version;
    private $workingDirectory;

    public function __construct(ProcessExecutor $processExecutor, $gitBinPath, $workingDirectory = null)
    {
        $this->processExecutor = $processExecutor;
        $this->gitBinPath = $gitBinPath;
        if (null === $workingDirectory && false !== getcwd()) {
            $workingDirectory = realpath(getcwd());
        }

        $this->workingDirectory = $workingDirectory;

        try {
            $this->getVersion();
        } catch (ProcessFailedException $e) {
            throw new \RuntimeException('git was not found, please check that it is installed and in the "PATH" env variable.');
        }
    }

    public static function create(IOInterface $io, $gitBinPath = 'git')
    {
        $finder = new ExecutableFinder();

        return new self(new ProcessExecutor($io), $finder->find('git', $gitBinPath));
    }

    public function getLatestReachableTag($branch = 'master')
    {
        return $this->execute(sprintf('describe %s --first-parent --tags --abbrev=0', ProcessExecutor::escape($branch)));
    }

    public function getVersion()
    {
        if (null === $this->version) {
            $output = $this->execute('--version');
            $this->version = '';
            if (preg_match('/^git version (\d+(?:\.\d+)+)/m', $output, $matches)) {
                $this->version = $matches[1];
            }
        }

        return $this->version;
    }

    public function tag($name, $message)
    {
        $this->execute(sprintf('tag -m %s %s', ProcessExecutor::escape($message), ProcessExecutor::escape($name)));
    }

    public function push()
    {
        $this->execute('push');
    }

    public function pushTags()
    {
        $this->execute('push --tags');
    }

    public function fetch()
    {
        $this->execute('fetch --all');
    }

    public function countCommitsSince($rev)
    {
        return (int) $this->execute(sprintf('rev-list --count %s..HEAD', ProcessExecutor::escape($rev)));
    }

    public function getRemoteStatus()
    {
        // https://stackoverflow.com/a/3278427
        $local = $this->execute('rev-parse @{0}');
        $remote = $this->execute('rev-parse @{u}');

        try {
            $base = $this->execute('merge-base @{0} @{u}');
        } catch (\RuntimeException $e) {
            $base = null;
        }

        if ($local === $remote) {
            return self::STATUS_UP_TO_DATE;
        }

        if ($local === $base) {
            return self::STATUS_NEED_TO_PULL;
        }

        if ($remote === $base) {
            return self::STATUS_NEED_TO_PUSH;
        }

        return self::STATUS_DIVERGED;
    }

    public function hasUpstream()
    {
        try {
            // https://stackoverflow.com/a/9753364
            $this->execute('rev-parse --abbrev-ref --symbolic-full-name @{u}');
        } catch (\RuntimeException $e) {
            return false;
        }

        return true;
    }

    public function getBranches()
    {
        if (null === $this->branches) {
            $branches = [];
            $output = $this->execute('branch --no-color --no-abbrev -v');
            foreach ($this->processExecutor->splitLines($output) as $branch) {
                if (!$branch || preg_match('{^ *[^/]+/HEAD }', $branch)) {
                    continue;
                }

                if (preg_match('{^(?:\* )? *(\S+) *([a-f0-9]+)(?: .*)?$}', $branch, $match)) {
                    $branches[$match[1]] = $match[2];
                }
            }

            $this->branches = $branches;
        }

        return $this->branches;
    }

    public function getCurrentBranch()
    {
        if (null === $this->currentBranch) {
            $output = $this->execute('branch --no-color');
            $this->currentBranch = '';
            $branches = $this->processExecutor->splitLines($output);
            foreach ($branches as $branch) {
                if ($branch && preg_match('{^\* +(\S+)}', $branch, $match)) {
                    $this->currentBranch = $match[1];
                    break;
                }
            }
        }

        return $this->currentBranch;
    }

    public function setWorkingDirectory($workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    private function execute($command)
    {
        $output = '';
        if (0 !== $exitCode = $this->processExecutor->execute($cmd = "{$this->gitBinPath} $command", $output, $this->workingDirectory)) {
            $message = trim($this->processExecutor->getErrorOutput());

            throw new ProcessFailedException(sprintf('Command "%s" returned with exit code "%d" and message "%s".', $cmd, $exitCode, $message));
        }

        return trim($output);
    }
}
