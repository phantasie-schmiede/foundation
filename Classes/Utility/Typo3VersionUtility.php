<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Class Typo3VersionUtility
 *
 * Single place to ask for the running core version.
 *
 * VersionNumberUtility::getNumericTypo3Version() returns a dotted string such as "13.4.9".
 * The comparison is covered by Typo3VersionUtilityTest on every major.
 *
 * @package PSBits\Foundation\Utility
 */
class Typo3VersionUtility
{
    /**
     * @param string $version A comparable version, e.g. "13.0" or "13.4.0". The minor and patch
     *                        levels are optional, "13" and "13.0" are both accepted.
     */
    public static function isAtLeast(string $version): bool
    {
        return version_compare(
            VersionNumberUtility::getNumericTypo3Version(),
            $version,
            '>='
        );
    }
}
