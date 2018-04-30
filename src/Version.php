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

use egabor\Composer\ReleasePlugin\Exception\InvalidVersionException;

final class Version extends SemVer
{
    private static $supportedPreLevels = [
        'alpha',
        'beta',
        'rc',
    ];
    private $preReleaseVersionParts;

    public function __construct($major, $minor, $patch, $pre = '', $build = '')
    {
        parent::__construct($major, $minor, $patch, $pre, $build);

        $this->assertValidVersion();
        $this->preReleaseVersionParts = '' !== $this->getPre() ? self::parsePreReleaseVersion($this->getPre()) : ['level' => '', 'separator' => '', 'number' => ''];
    }

    public function getPreLevel()
    {
        return $this->preReleaseVersionParts['level'];
    }

    public function getPreSeparator()
    {
        return $this->preReleaseVersionParts['separator'];
    }

    public function getPreNumber()
    {
        return $this->preReleaseVersionParts['number'];
    }

    public function isStable()
    {
        return 0 < $this->getMajor() && '' === $this->getPre();
    }

    private static function parsePreReleaseVersion($tag)
    {
        $pattern = '(?P<level>'.implode('|', self::$supportedPreLevels).')(?P<separator>\\.?)(?P<number>\\d*)';
        $matches = self::match($pattern, $tag, 'Pre-release version is not valid and cannot be parsed.');

        return array_replace(['level' => '', 'separator' => '', 'number' => ''], $matches);
    }

    private function assertValidVersion()
    {
        if ('0' === $this->getMajor() && '' !== $this->getPre()) {
            throw new InvalidVersionException('Invalid version. Pre-release versions can not have pre part.');
        }

        if ('0' !== $this->getPatch() && '' !== $this->getPre()) {
            throw new InvalidVersionException('Invalid version. Patch versions can not have pre part.');
        }
    }
}
