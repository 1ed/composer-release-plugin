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

use Composer\Semver\Comparator;
use egabor\Composer\ReleasePlugin\Util\Git;

class ReleaseManager
{
    private $config;
    private $git;

    public function __construct(Config $config, Git $git)
    {
        $this->config = $config;
        $this->git = $git;
    }

    public function gatFutureVersion($version, Version $latestVersion = null)
    {
        if (null === $latestVersion) {
            $latestVersion = $this->getLatestReleaseVersion() ?: Version::fromString('0.0.0');
        }

        if (VersionManipulator::supportsLevel($version)) {
            $futureVersion = (string) VersionManipulator::bump($version, $latestVersion);
        } else {
            $futureVersion = (string) Version::fromString($version); // validation
        }

        if (Comparator::lessThanOrEqualTo($futureVersion, (string) $latestVersion)) {
            throw new \LogicException(sprintf('The provided version "%s" should be greater than the latest released version "%s".', $futureVersion, $latestVersion));
        }

        return $this->config->shouldUsePrefix() ? 'v'.$futureVersion : $futureVersion;
    }

    public function release($futureVersion, $message = null)
    {
        $this->git->tag($futureVersion, strtr($message, ['%version%' => $futureVersion]) ?: 'Release '.$futureVersion);
    }

    public function releaseAndPush($futureVersion, $message = null)
    {
        $this->release($futureVersion, $message);

        $this->git->push();
        $this->git->pushTags();
    }

    public function getLatestReleaseVersion()
    {
        try {
            $tag = $this->git->getLatestReachableTag($this->config->getReleaseBranch());
        } catch (\Exception $e) {
            return null;
        }

        return Version::fromString($tag);
    }
}
