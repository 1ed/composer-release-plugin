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

use egabor\Composer\ReleasePlugin\Exception\InvalidVersionException;
use egabor\Composer\ReleasePlugin\Version;
use egabor\Composer\ReleasePlugin\Test\ForwardCompatibleTestCase as TestCase;

final class VersionTest extends TestCase
{
    /** @test @dataProvider validVersions */
    function it_can_be_created_from_valid_version_strings($version, $major, $minor, $patch, $pre, $build, $preLevel, $preSeparator, $preNumber)
    {
        $v = Version::fromString($version);

        $this->assertSame($major, $v->getMajor());
        $this->assertSame($minor, $v->getMinor());
        $this->assertSame($patch, $v->getPatch());
        $this->assertSame($pre, $v->getPre());
        $this->assertSame($build, $v->getBuild());
        $this->assertSame($preLevel, $v->getPreLevel());
        $this->assertSame($preSeparator, $v->getPreSeparator());
        $this->assertSame($preNumber, $v->getPreNumber());
        $this->assertSame($version, (string) $v);
    }

    /** @test @dataProvider validVersions */
    function it_can_be_created_from_valid_version_parts($version, $major, $minor, $patch, $pre, $build, $preLevel, $preSeparator, $preNumber)
    {
        $v = new Version($major, $minor, $patch, $pre, $build);

        $this->assertSame($major, $v->getMajor());
        $this->assertSame($minor, $v->getMinor());
        $this->assertSame($patch, $v->getPatch());
        $this->assertSame($pre, $v->getPre());
        $this->assertSame($build, $v->getBuild());
        $this->assertSame($preLevel, $v->getPreLevel());
        $this->assertSame($preSeparator, $v->getPreSeparator());
        $this->assertSame($preNumber, $v->getPreNumber());
        $this->assertSame($version, (string) $v);
    }

    /** @test @dataProvider invalidVersionStrings */
    function it_throws_for_invalid_version_strings($version, $message)
    {
        $this->expectException(InvalidVersionException::class);
        $this->expectExceptionMessage(sprintf($message, trim($version)));

        Version::fromString($version);
    }

    /** @test @dataProvider invalidVersionParts */
    function it_throws_for_invalid_version_parts($major, $minor, $patch, $pre, $build)
    {
        $this->expectException(InvalidVersionException::class);

        new Version($major, $minor, $patch, $pre, $build);
    }

    /** @test @dataProvider dirtyVersions */
    function it_cleans_dirty_version_strings($dirty, $clean)
    {
        $this->assertSame($clean, (string) Version::fromString($dirty));
    }

    /** @test @dataProvider stableVersions */
    function it_can_be_stable($version)
    {
        $v = Version::fromString($version);

        $this->assertTrue($v->isStable());
    }

    /** @test @dataProvider unstableVersions */
    function it_can_be_unstable($version)
    {
        $v = Version::fromString($version);

        $this->assertFalse($v->isStable());
    }

    public function validVersions()
    {
        yield ['0.0.0', '0', '0', '0', '', '', '', '', ''];
        yield ['0.0.1', '0', '0', '1', '', '', '', '', ''];
        yield ['0.1.0', '0', '1', '0', '', '', '', '', ''];
        yield ['0.1.1', '0', '1', '1', '', '', '', '', ''];
        yield ['1.0.0', '1', '0', '0', '', '', '', '', ''];
        yield ['1.0.1', '1', '0', '1', '', '', '', '', ''];
        yield ['1.1.0', '1', '1', '0', '', '', '', '', ''];
        yield ['1.1.1', '1', '1', '1', '', '', '', '', ''];
        yield ['2.3.4', '2', '3', '4', '', '', '', '', ''];
        yield ['2.3.0-alpha1', '2', '3', '0', 'alpha1', '', 'alpha', '', '1'];
        yield ['2.3.0-alpha.3', '2', '3', '0', 'alpha.3', '', 'alpha', '.', '3'];
        yield ['2.3.0-beta2', '2', '3', '0', 'beta2', '', 'beta', '', '2'];
        yield ['2.3.0-rc1', '2', '3', '0', 'rc1', '', 'rc', '', '1'];
        yield ['2.3.4+a1b3cf', '2', '3', '4', '', 'a1b3cf', '', '', ''];
        yield ['2.3.0-alpha1+a1b3cf', '2', '3', '0', 'alpha1', 'a1b3cf', 'alpha', '', '1'];
        yield ['2.3.0-alpha1+a1b3cf.qw3eda21', '2', '3', '0', 'alpha1', 'a1b3cf.qw3eda21', 'alpha', '', '1'];
    }

    public function dirtyVersions()
    {
        yield [' 1.2.3 ', '1.2.3'];
        yield [' 1.2.0-beta3 ', '1.2.0-beta3'];
        yield ['v1.2.3', '1.2.3'];
        yield ["\t1.2.3", '1.2.3'];
        yield ["1.2.3\n", '1.2.3'];
    }

    public function invalidVersionStrings()
    {
        yield ['asdas', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['asda.s', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1.0', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['01.21.01', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1.01.01', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1.1.01', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['2.a.fv', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['0.3.fv', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['0.c.2', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1,2,3', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1~2.3', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1.0.0-', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1.0.0+', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['-1.0.0+', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['  =v1.2.3   ', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['>1.2.3', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['~1.2.3', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['<=1.2.3', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1.2.x', 'Version "%s" is not valid and cannot be parsed.'];
        yield ['1.2.2-beta1', 'Invalid version. Patch versions can not have pre part.'];
        yield ['1.2.12-rc.3', 'Invalid version. Patch versions can not have pre part.'];
        yield ['0.1.0-RC4', 'Invalid version. Pre-release versions can not have pre part.'];
        yield ['1.0.0-invalid1', 'Pre-release version is not valid and cannot be parsed.'];
    }

    public function invalidVersionParts()
    {
        yield ['asdas', '0', '0', '', ''];
        yield ['0', 'asdas', '0', '', ''];
        yield ['0', '0', 'asdas', '', ''];
        yield ['0', '0', '0', 'as dj', ''];
        yield ['0', '0', '0', '', 'wv a'];
        yield ['asda.s', '0', '0', '', ''];
        yield ['0', '1.0', '0', '', ''];
        yield ['1', '2', '3', 'alpha2', ''];
        yield ['1', '2', '40', 'rc.1', ''];
    }

    public function stableVersions()
    {
        yield ['1.0.0'];
        yield ['1.2.1'];
        yield ['2.3.4'];
        yield ['20.3.42'];
    }

    public function unstableVersions()
    {
        yield ['0.0.0'];
        yield ['0.0.1'];
        yield ['0.1.1'];
        yield ['0.3.1'];
        yield ['0.1.0'];
        yield ['0.2.10'];
        yield ['1.0.0-alpha1'];
        yield ['1.0.0-rc4'];
        yield ['2.6.0-beta3'];
    }
}
