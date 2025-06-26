<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class FlexFormService
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class FlexFormService
{
    public const string ALL_PLUGINS = '*';

    /**
     * @param string $xml             Pass the raw XML-data, not the file path!
     * @param string $pluginSignature '*' if you add a FlexForm for a content element, otherwise:
     *                                '[extensionkey]_[pluginname]'
     * @param string $cType           Plugins use the default value ('list').
     *
     * @return void
     */
    public function register(string $xml, string $pluginSignature = self::ALL_PLUGINS, string $cType = 'list'): void
    {
        if (self::ALL_PLUGINS !== $pluginSignature) {
            $pluginSignature = strtolower($pluginSignature);
            $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        }

        ExtensionManagementUtility::addPiFlexFormValue(
            $pluginSignature,
            $xml,
            $cType
        );
    }
}
