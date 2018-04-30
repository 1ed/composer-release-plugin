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

/**
 * Tokenize, validate, and parse SemVer version strings.
 *
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 */
abstract class SemVer
{
    private $major;
    private $minor;
    private $patch;
    private $pre;
    private $build;

    public function __construct($major, $minor, $patch, $pre = '', $build = '')
    {
        $this->assertValidNumericIdentifier($major);
        $this->assertValidNumericIdentifier($minor);
        $this->assertValidNumericIdentifier($patch);
        $pre ? $this->assertValidPreReleaseVersion($pre) : null;
        $build ? $this->assertValidBuildMetadata($build) : null;

        $this->major = (string) $major;
        $this->minor = (string) $minor;
        $this->patch = (string) $patch;
        $this->pre = (string) $pre;
        $this->build = (string) $build;
    }

    public function __toString()
    {
        return implode('.', [$this->major, $this->minor, $this->patch])
            .$this->prefix('-', $this->pre)
            .$this->prefix('+', $this->build);
    }

    public static function fromString($version)
    {
        $parts = self::parse($version);

        return new static($parts['major'], $parts['minor'], $parts['patch'], $parts['pre'], $parts['build']);
    }

    public function getMajor()
    {
        return $this->major;
    }

    public function getMinor()
    {
        return $this->minor;
    }

    public function getPatch()
    {
        return $this->patch;
    }

    public function getPre()
    {
        return $this->pre;
    }

    public function getBuild()
    {
        return $this->build;
    }

    protected static function match($pattern, $value, $invalidValueMessage)
    {
        if (!preg_match('{\\A'.$pattern.'\\Z}i', $value, $matches)) {
            if (PREG_NO_ERROR !== $error = preg_last_error()) {
                throw new \RuntimeException(sprintf('PCRE regex error with code: "%s"', $error)); // @codeCoverageIgnore
            }

            throw new InvalidVersionException(sprintf($invalidValueMessage, $value));
        }

        return $matches;
    }

    private static function parse($version)
    {
        $p = self::patterns();
        // Main Version - Three dot-separated numeric identifiers.
        $mainVersion = '(?P<major>'.$p['numeric_identifier'].')\\.(?P<minor>'.$p['numeric_identifier'].')\\.(?P<patch>'.$p['numeric_identifier'].')';
        // Pre-release Version - Hyphen, followed by one or more dot-separated pre-release version identifiers.
        $pre = '(?:-(?P<pre>'.$p['pre_version'].'))';
        // Build Metadata - Plus sign, followed by one or more dot-separated build metadata identifiers.
        $build = '(?:\\+(?P<build>'.$p['build_meta'].'))';
        // Full Version String - A main version, followed optionally by a pre-release version and build metadata.
        $full = 'v?'.$mainVersion.$pre.'?'.$build.'?';

        $parts = self::match($full, trim((string) $version), 'Version "%s" is not valid and cannot be parsed.');

        return array_replace(['major' => '', 'minor' => '', 'patch' => '', 'pre' => '', 'build' => ''], $parts);
    }

    private function prefix($prefix, $string)
    {
        return '' !== $string ? $prefix.$string : $string;
    }

    private function assertValidNumericIdentifier($value)
    {
        self::match(self::patterns()['numeric_identifier'], $value, 'Invalid numeric value "%s".');
    }

    private function assertValidPreReleaseVersion($value)
    {
        self::match(self::patterns()['pre_version'], $value, 'Invalid pre-release version "%s".');
    }

    private function assertValidBuildMetadata($value)
    {
        self::match(self::patterns()['build_meta'], $value, 'Invalid build metadata "%s".');
    }

    /**
     * @todo move to private static properties, requires php 5.6
     * @todo move to private constants, requires php 7.1
     */
    private static function patterns()
    {
        $patterns = [
            // Numeric Identifier - A single `0`, or a non-zero digit followed by zero or more digits.
            'numeric_identifier' => '0|[1-9]\\d*',
            // Non-numeric Identifier - Zero or more digits, followed by a letter or hyphen, and then zero or more letters, digits, or hyphens.
            'non_numeric_identifier' => '\\d*[a-zA-Z-][a-zA-Z0-9-]*',
            // Build Metadata Identifier - Any combination of digits, letters, or hyphens.
            'build_identifier' => '[0-9A-Za-z-]+',
        ];

        $patterns += [
            // Pre-release Version Identifier - A numeric identifier, or a non-numeric identifier.
            'pre_identifier' => '(?:'.$patterns['numeric_identifier'].'|'.$patterns['non_numeric_identifier'].')',
        ];

        return $patterns + [
            // Pre-release Version - One or more dot-separated pre-release version identifiers.
            'pre_version' => $patterns['pre_identifier'].'(?:\\.'.$patterns['pre_identifier'].')*',
            // Build Metadata - One or more dot-separated build metadata identifiers.
            'build_meta' => $patterns['build_identifier'].'(?:\\.'.$patterns['build_identifier'].')*',
        ];
    }
}
