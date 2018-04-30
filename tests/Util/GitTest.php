<?php

/*
 * This file is part of the egabor/composer-release-plugin package.
 *
 * (c) Gábor Egyed <gabor.egyed@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace egabor\Composer\ReleasePlugin\Tests\Util;

use Composer\Util\ProcessExecutor;
use egabor\Composer\ReleasePlugin\Util\Git;
use egabor\Composer\ReleasePlugin\Test\ForwardCompatibleTestCase as TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class GitTest extends TestCase
{
    /** @test */
    function it_throws_when_git_was_not_found()
    {
        $executor = $this->createMock(ProcessExecutor::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('git was not found, please check that it is installed and in the "PATH" env variable.');

        new Git($executor, '');
    }

    /** @test */
    function it_gets_the_latest_reachable_tag_for_a_branch()
    {
        $git = $this->createGitMockWithCommands([
            "git describe 'master' --first-parent --tags --abbrev=0" => ["v1.0.0\n", 0],
        ]);

        $this->assertSame('v1.0.0', $git->getLatestReachableTag());
    }

    /** @test */
    function it_throws_when_the_latest_reachable_tag_for_a_branch_not_available()
    {
        $git = $this->createGitMockWithCommands([
            "git describe 'master' --first-parent --tags --abbrev=0" => ["fatal: No names found, cannot describe anything.\n", 128],
        ]);

        $this->expectException(\RuntimeException::class);

        $git->getLatestReachableTag();
    }

    /** @test */
    function it_gets_git_version()
    {
        $git = $this->createGitMockWithCommands([
            'git --version' => ["git version 2.7.4\n", 0],
        ]);

        $this->assertSame('2.7.4', $git->getVersion());
    }

    private function createGitMockWithCommands(array $commands)
    {
        /** @var ProcessExecutor|MockObject $executor */
        $executor = $this->getMockBuilder(ProcessExecutor::class)
            ->setMethods(['execute', 'getErrorOutput'])
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock();

        $commands = array_merge([
            'git --version' => ["git version 2.7.4\n", 0],
        ], $commands);

        $i = 0;
        foreach ($commands as $command => list($output, $exitCode)) {
            $executor
                ->expects($this->at($i))
                ->method('execute')
                ->willReturnCallback(function ($cmd, &$out) use ($command, $output, $exitCode) {
                    $this->assertSame($cmd, $command);

                    if (0 === $exitCode) {
                        $out = $output;
                    }

                    return $exitCode;
                })
            ;

            if (0 !== $exitCode) {
                $executor
                    ->expects($this->at($i))
                    ->method('getErrorOutput')
                    ->willReturn($output)
                ;
            }

            $i++;
        }

        return new Git($executor, 'git');
    }
}
