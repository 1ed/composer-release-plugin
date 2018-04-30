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

use egabor\Composer\ReleasePlugin\Exception\UnstableVersionException;

/**
 * Opinionated version manipulations.
 *
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 */
class VersionManipulator
{
    private static $supportedLevels = [
        'major',
        'minor',
        'patch',
        'stable',
        'alpha',
        'beta',
        'rc',
    ];

    public static function bump($level, Version $version)
    {
        switch ($level) {
            case 'major':
                return self::bumpMajor($version);
            case 'minor':
                return self::bumpMinor($version);
            case 'patch':
                return self::bumpPatch($version);
            case 'stable':
                return self::toStable($version);
            case 'alpha':
            case 'beta':
            case 'rc':
                return self::bumpPre($version, $level);
            default:
                throw new \InvalidArgumentException(sprintf('Bumping "%s" version is not supported.', $level));
        }
    }

    public static function supportsLevel($level)
    {
        return in_array((string) $level, self::$supportedLevels, true);
    }

    private static function bumpMajor(Version $current)
    {
        if ('' !== $current->getPre() && '0' !== $current->getMinor()) {
            throw new UnstableVersionException('Need to release a stable version first.');
        }

        if ('' !== $current->getPre()) {
            return self::toStable($current);
        }

        return new Version((int) $current->getMajor() + 1, 0, 0);
    }

    private static function bumpMinor(Version $current)
    {
        if ('' !== $current->getPre() && '0' === $current->getMinor()) {
            throw new UnstableVersionException('Need to release a stable version first.');
        }

        if ('' !== $current->getPre()) {
            return self::toStable($current);
        }

        return new Version($current->getMajor(), (int) $current->getMinor() + 1, 0);
    }

    private static function bumpPatch(Version $current)
    {
        if ('' !== $current->getPre()) {
            throw new UnstableVersionException('Need to release a stable version first.');
        }

        return new Version($current->getMajor(), $current->getMinor(), (int) $current->getPatch() + 1);
    }

    private static function bumpPre(Version $current, $level)
    {
        if ('' === $current->getPre() && '0' === $current->getMajor()) {
            return new Version(1, 0, 0, $level.'1');
        }

        if ('' === $current->getPre()) {
            return new Version($current->getMajor(), (int) $current->getMinor() + 1, 0, $level.'1');
        }

        if ($level === $current->getPreLevel()) {
            $pre = $level.$current->getPreSeparator().((int) $current->getPreNumber() + 1);
        } else {
            $pre = $level.$current->getPreSeparator().'1';
        }

        return new Version($current->getMajor(), $current->getMinor(), $current->getPatch(), $pre);
    }

    private static function toStable(Version $current)
    {
        if ($current->isStable()) {
            throw new \LogicException(sprintf('"%s" version is already stable.', $current));
        }

        if ('' !== $current->getPre()) {
            return new Version($current->getMajor(), $current->getMinor(), $current->getPatch());
        }

        return new Version((int) $current->getMajor() + 1, 0, 0);
    }
}
