<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Configuration;

use PSBits\Foundation\Data\ExtensionInformationInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IconUtility
 *
 * @package PSBits\Foundation\Utility\Configuration
 */
class IconUtility
{
    public static function getDefaultIdentifier(
        ExtensionInformationInterface $extensionInformation,
        string                        $name,
    ): string {
        return str_replace(
            '_',
            '-',
            $extensionInformation->getExtensionKey()
        ) . '-' . str_replace(
            '_',
            '-',
            GeneralUtility::camelCaseToLowerCaseUnderscored($name)
        );
    }
}
