<?php

/*
 * This file is part of the egabor/composer-release-plugin package.
 *
 * (c) Gábor Egyed <gabor.egyed@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace egabor\Composer\ReleasePlugin\Tests\Functional;

use Composer\Util\ProcessExecutor;
use egabor\Composer\ReleasePlugin\Test\ForwardCompatibleTestCase as TestCase;
use egabor\Composer\ReleasePlugin\Util\Git;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class GitTest extends TestCase
{
    private static $gitPath;
    private static $repoPath;

    /**                                                 y
     * @var Git
     */
    private static $git;

    public static function setUpBeforeClass()
    {
        try {
            // check if git installed
            $finder = new ExecutableFinder();
            self::$git = new Git(new ProcessExecutor(), self::$gitPath = $finder->find('git'));
        } catch (\RuntimeException $e) {
            self::markTestSkipped($e->getMessage());
        }

        self::$repoPath = sys_get_temp_dir().'/'.uniqid('egabor-composer-release-plugin-repo-', true);
        mkdir(self::$repoPath, 0777, true);
        self::runGit(['init']);
        self::commitAChange();

        self::$git->setWorkingDirectory(self::$repoPath);
    }

    public static function tearDownAfterClass()
    {
        (new Process(['rm', '-rf', self::$repoPath]))->mustRun();
    }

    /** @test */
    function it_can_create_a_new_tag()
    {
        $this->assertNotContains('1.0.0', self::runGit(['tag', '-l'])->getOutput());
        self::$git->tag('1.0.0', 'Release 1.0.0');
        $this->assertContains('1.0.0', self::runGit(['tag', '-l'])->getOutput());
    }

    /** @test */
    function it_can_count_commits_since_a_revision()
    {
        $this->assertSame(0, self::$git->countCommitsSince('1.0.0'));
        self::commitAChange();
        $this->assertSame(1, self::$git->countCommitsSince('1.0.0'));
    }

    private static function runGit(array $command)
    {
        return (new Process(array_merge([self::$gitPath], $command)))->setWorkingDirectory(self::$repoPath)->mustRun();
    }

    private static function commitAChange()
    {
        file_put_contents(self::$repoPath . '/test', uniqid('testContent', true));
        self::runGit(['add', '.']);
        self::runGit(['commit', '-m', '"test"']);
    }
}
