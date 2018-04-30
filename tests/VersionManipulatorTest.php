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

use egabor\Composer\ReleasePlugin\Exception\UnstableVersionException;
use egabor\Composer\ReleasePlugin\Version;
use egabor\Composer\ReleasePlugin\VersionManipulator;
use egabor\Composer\ReleasePlugin\Test\ForwardCompatibleTestCase as TestCase;

final class VersionManipulatorTest extends TestCase
{
    /** @test @dataProvider validVersionIncrements */
    function it_increments_a_version($current, $level, $target)
    {
        $this->assertEquals(Version::fromString($target), VersionManipulator::bump($level, Version::fromString($current)));
    }

    /** @test */
    function it_throws_when_an_unsupported_level_provided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bumping "invalid-level" version is not supported.');

        VersionManipulator::bump('invalid-level', Version::fromString('1.0.0'));
    }

    /** @test @dataProvider invalidVersionIncrements */
    function it_throws_for_invalid_version_increments($current, $level, $exception, $message)
    {
        $current = Version::fromString($current);

        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        VersionManipulator::bump($level, $current);
    }

    /** @test @dataProvider supportedLevels */
    function it_supports_different_version_levels($level)
    {
        $this->assertTrue(VersionManipulator::supportsLevel($level));
    }

    function validVersionIncrements()
    {
        yield ['0.0.0', 'patch', '0.0.1'];
        yield ['0.0.1', 'patch', '0.0.2'];
        yield ['0.1.0', 'patch', '0.1.1'];
        yield ['0.1.1', 'patch', '0.1.2'];
        yield ['1.0.0', 'patch', '1.0.1'];
        yield ['1.0.1', 'patch', '1.0.2'];
        yield ['1.1.0', 'patch', '1.1.1'];
        yield ['1.1.1', 'patch', '1.1.2'];

        yield ['0.0.0', 'minor', '0.1.0'];
        yield ['0.0.1', 'minor', '0.1.0'];
        yield ['0.1.0', 'minor', '0.2.0'];
        yield ['0.1.1', 'minor', '0.2.0'];
        yield ['1.0.0', 'minor', '1.1.0'];
        yield ['1.0.1', 'minor', '1.1.0'];
        yield ['1.1.0', 'minor', '1.2.0'];
        yield ['1.1.1', 'minor', '1.2.0'];

        yield ['0.0.0', 'major', '1.0.0'];
        yield ['0.0.1', 'major', '1.0.0'];
        yield ['0.1.0', 'major', '1.0.0'];
        yield ['0.1.1', 'major', '1.0.0'];
        yield ['1.0.0', 'major', '2.0.0'];
        yield ['1.0.1', 'major', '2.0.0'];
        yield ['1.1.0', 'major', '2.0.0'];
        yield ['1.1.1', 'major', '2.0.0'];

        yield ['0.0.0', 'alpha', '1.0.0-alpha1'];
        yield ['0.0.1', 'alpha', '1.0.0-alpha1'];
        yield ['0.1.0', 'alpha', '1.0.0-alpha1'];
        yield ['0.1.1', 'alpha', '1.0.0-alpha1'];
        yield ['1.0.0', 'alpha', '1.1.0-alpha1'];
        yield ['1.0.1', 'alpha', '1.1.0-alpha1'];
        yield ['1.1.0', 'alpha', '1.2.0-alpha1'];
        yield ['1.1.1', 'alpha', '1.2.0-alpha1'];

        yield ['0.0.0', 'stable', '1.0.0'];
        yield ['0.0.1', 'stable', '1.0.0'];
        yield ['0.1.0', 'stable', '1.0.0'];
        yield ['0.1.1', 'stable', '1.0.0'];

        yield ['1.1.0-alpha1', 'minor', '1.1.0'];

        yield ['1.0.0-alpha1', 'major', '1.0.0'];

        yield ['1.0.0-alpha1', 'alpha', '1.0.0-alpha2'];
        yield ['1.1.0-alpha1', 'alpha', '1.1.0-alpha2'];

        yield ['1.0.0-alpha1', 'beta', '1.0.0-beta1'];
        yield ['1.1.0-alpha1', 'beta', '1.1.0-beta1'];

        yield ['1.0.0-alpha1', 'stable', '1.0.0'];
        yield ['1.1.0-alpha1', 'stable', '1.1.0'];
    }

    function invalidVersionIncrements()
    {
        yield ['1.0.0', 'stable', \LogicException::class, '"1.0.0" version is already stable.'];
        yield ['1.0.1', 'stable', \LogicException::class, '"1.0.1" version is already stable.'];
        yield ['1.1.0', 'stable', \LogicException::class, '"1.1.0" version is already stable.'];
        yield ['1.1.1', 'stable', \LogicException::class, '"1.1.1" version is already stable.'];

        yield ['1.0.0-alpha1', 'patch', UnstableVersionException::class, 'Need to release a stable version first.'];
        yield ['1.1.0-alpha1', 'patch', UnstableVersionException::class, 'Need to release a stable version first.'];

        yield ['1.0.0-alpha1', 'minor', UnstableVersionException::class, 'Need to release a stable version first.'];

        yield ['1.1.0-alpha1', 'major', UnstableVersionException::class, 'Need to release a stable version first.'];
    }

    function supportedLevels()
    {
        yield ['major'];
        yield ['minor'];
        yield ['patch'];
        yield ['stable'];
        yield ['alpha'];
        yield ['beta'];
        yield ['rc'];
    }
}
