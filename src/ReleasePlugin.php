<?php

/*
 * This file is part of the egabor/composer-release-plugin package.
 *
 * (c) Gábor Egyed <gabor.egyed@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace egabor\Composer\ReleasePlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use egabor\Composer\ReleasePlugin\Command\ReleaseCommand;

/**
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 */
class ReleasePlugin implements Capable, CommandProvider, PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
    }

    public function getCapabilities()
    {
        return [
            CommandProvider::class => __CLASS__,
        ];
    }

    public function getCommands()
    {
        return [
            new ReleaseCommand(),
        ];
    }
}
