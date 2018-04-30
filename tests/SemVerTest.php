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
use egabor\Composer\ReleasePlugin\SemVer;
use egabor\Composer\ReleasePlugin\Test\ForwardCompatibleTestCase as TestCase;

final class SemVerTest extends TestCase
{
    /** @test @dataProvider validVersions */
    function it_can_be_created_from_valid_version_strings($version, $major, $minor, $patch, $pre, $build)
    {
        $v = SemVerVersion::fromString($version);

        $this->assertSame($major, $v->getMajor());
        $this->assertSame($minor, $v->getMinor());
        $this->assertSame($patch, $v->getPatch());
        $this->assertSame($pre, $v->getPre());
        $this->assertSame($build, $v->getBuild());
        $this->assertSame($version, (string) $v);
    }

    /** @test @dataProvider validVersions */
    function it_can_be_created_from_valid_version_parts($version, $major, $minor, $patch, $pre, $build)
    {
        $v = new SemVerVersion($major, $minor, $patch, $pre, $build);

        $this->assertSame($major, $v->getMajor());
        $this->assertSame($minor, $v->getMinor());
        $this->assertSame($patch, $v->getPatch());
        $this->assertSame($pre, $v->getPre());
        $this->assertSame($build, $v->getBuild());
        $this->assertSame($version, (string) $v);
    }

    /** @test @dataProvider invalidVersionStrings */
    function it_throws_for_invalid_version_strings($version)
    {
        $this->expectException(InvalidVersionException::class);
        $this->expectExceptionMessage(sprintf('Version "%s" is not valid and cannot be parsed.', trim($version)));

        SemVerVersion::fromString($version);
    }

    /** @test @dataProvider invalidVersionParts */
    function it_throws_for_invalid_version_parts($major, $minor, $patch, $pre, $build)
    {
        $this->expectException(InvalidVersionException::class);

        new SemVerVersion($major, $minor, $patch, $pre, $build);
    }

    /** @test @dataProvider dirtyVersions */
    function it_cleans_dirty_version_strings($dirty, $clean)
    {
        $this->assertSame($clean, (string) SemVerVersion::fromString($dirty));
    }

    public function validVersions()
    {
        yield ['0.0.0', '0', '0', '0', '', ''];
        yield ['0.0.1', '0', '0', '1', '', ''];
        yield ['0.1.0', '0', '1', '0', '', ''];
        yield ['0.1.1', '0', '1', '1', '', ''];
        yield ['1.0.0', '1', '0', '0', '', ''];
        yield ['1.0.1', '1', '0', '1', '', ''];
        yield ['1.1.0', '1', '1', '0', '', ''];
        yield ['1.1.1', '1', '1', '1', '', ''];
        yield ['2.3.4', '2', '3', '4', '', ''];
        yield ['2.3.4-alpha1', '2', '3', '4', 'alpha1', ''];
        yield ['2.3.4-alpha.3', '2', '3', '4', 'alpha.3', ''];
        yield ['2.3.4-beta2', '2', '3', '4', 'beta2', ''];
        yield ['2.3.4-rc1', '2', '3', '4', 'rc1', ''];
        yield ['2.3.4-rc-1', '2', '3', '4', 'rc-1', ''];
        yield ['2.3.4+a1b3cf', '2', '3', '4', '', 'a1b3cf'];
        yield ['2.3.4-alpha1+a1b3cf', '2', '3', '4', 'alpha1', 'a1b3cf'];
        yield ['2.3.4-alpha1+a1b3cf.qw3eda21', '2', '3', '4', 'alpha1', 'a1b3cf.qw3eda21'];
    }

    public function dirtyVersions()
    {
        yield [' 1.2.3 ', '1.2.3'];
        yield [' 1.2.3-4 ', '1.2.3-4'];
        yield [' 1.2.3-pre ', '1.2.3-pre'];
        yield ['v1.2.3', '1.2.3'];
        yield ["\t1.2.3", '1.2.3'];
        yield ["1.2.3\n", '1.2.3'];
    }

    public function invalidVersionStrings()
    {
        yield ['asdas'];
        yield ['asda.s'];
        yield ['1.0'];
        yield ['01.21.01'];
        yield ['1.01.01'];
        yield ['1.1.01'];
        yield ['2.a.fv'];
        yield ['0.3.fv'];
        yield ['0.c.2'];
        yield ['1,2,3'];
        yield ['1~2.3'];
        yield ['1.0.0-'];
        yield ['1.0.0+'];
        yield ['-1.0.0+'];
        yield ['  =v1.2.3   '];
        yield ['>1.2.3'];
        yield ['~1.2.3'];
        yield ['<=1.2.3'];
        yield ['1.2.x'];
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
    }
}

class SemVerVersion extends SemVer
{
}
