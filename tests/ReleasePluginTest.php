<?php

/*
 * This file is part of the egabor/composer-release-plugin package.
 *
 * (c) Gábor Egyed <gabor.egyed@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace egabor\Composer\ReleasePlugin\Tests;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider;
use egabor\Composer\ReleasePlugin\Command\ReleaseCommand;
use egabor\Composer\ReleasePlugin\ReleasePlugin;
use egabor\Composer\ReleasePlugin\Test\ForwardCompatibleTestCase as TestCase;

final class ReleasePluginTest extends TestCase
{
    /** @test */
    function it_provides_command_capabilities()
    {
        $plugin = new ReleasePlugin();
        $this->assertArrayHasKey(CommandProvider::class, $plugin->getCapabilities());
    }

    /** @test */
    function it_provides_a_release_command()
    {
        $plugin = new ReleasePlugin();
        $commands = $plugin->getCommands();

        foreach ($commands as $command) {
            $isValidCommand = $command instanceof BaseCommand && '' !== $command->getName();
            if ($command instanceof ReleaseCommand && $isValidCommand) {
                $this->assertTrue(true);

                return;
            }
        }

        $this->fail();
    }
}
