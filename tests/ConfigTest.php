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

use Composer\Package\RootPackageInterface;
use egabor\Composer\ReleasePlugin\Config;
use egabor\Composer\ReleasePlugin\Test\ForwardCompatibleTestCase as TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class ConfigTest extends TestCase
{
    /** @test */
    function it_has_a_default_configuration()
    {
        $config = $this->createConfig();

        $this->assertTrue($config->shouldUsePrefix());
        $this->assertSame('master', $config->getReleaseBranch());
    }

    /** @test */
    function it_prefers_the_package_config_over_defaults()
    {
        $config = $this->createConfig([
            'egabor-release' => [
                'use-prefix' => false,
                'release-branch' => 'develop',
            ],
        ]);

        $this->assertFalse($config->shouldUsePrefix());
        $this->assertSame('develop', $config->getReleaseBranch());
    }

    private function createConfig(array $rootPackageConfig = [])
    {
        /** @var RootPackageInterface|MockObject $package */
        $package = $this->getMockBuilder(RootPackageInterface::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock();
        $package
            ->method('getExtra')
            ->willReturn($rootPackageConfig);

        return new Config($package);
    }
}
