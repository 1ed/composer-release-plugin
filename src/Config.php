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

use Composer\Package\RootPackageInterface;

final class Config
{
    private $config;

    public function __construct(RootPackageInterface $package)
    {
        $extra = $package->getExtra();
        $this->config = array_replace([
            'use-prefix' => true,
            'release-branch' => 'master',
        ], isset($extra['egabor-release']) ? $extra['egabor-release'] : []);
    }

    public function shouldUsePrefix()
    {
        return $this->config['use-prefix'];
    }

    public function getReleaseBranch()
    {
        return $this->config['release-branch'];
    }
}
