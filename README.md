
[![Build Status](https://travis-ci.org/1ed/composer-release-plugin.svg?branch=master)](https://travis-ci.org/1ed/composer-release-plugin)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/1ed/composer-release-plugin/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/1ed/composer-release-plugin/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/egabor/composer-release-plugin/v/stable)](https://packagist.org/packages/egabor/composer-release-plugin)
[![Latest Unstable Version](https://poser.pugx.org/egabor/composer-release-plugin/v/unstable)](https://packagist.org/packages/egabor/composer-release-plugin)
[![License](https://poser.pugx.org/egabor/composer-release-plugin/license)](https://packagist.org/packages/egabor/composer-release-plugin)

# Composer Release Plugin

A composer plugin to help making releases.

## Pre-requisites/assumptions

 * Your project uses `git`

## Installation

```bash
composer require --dev egabor/composer-release-plugin
```

## Usage

After installation a new `release` command should appear in the list of available commands.

### Documentation

If you need more information about the command and how to use it, you should read:

```
composer release --help
```

## Configuration

There are some configuration options:
```
use-prefix: use 'v' to prefix the release version number (default: false)
release-branch: name of the branch where the releases originated from (default: master)
```
These options can be set in the project's composer.json file, under the `extra.egabor-release` key, like:

```json
{
    "name": "vendor/package",
    "require-dev": {
        "egabor/composer-release-plugin": "^1.0"
    },
    "extra": {
        "egabor-release": {
            "release-branch": "release",
            "use-prefix": true
        }
    }
}
```
