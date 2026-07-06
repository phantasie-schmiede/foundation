<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration;

use BackedEnum;
use PSBits\Foundation\Service\GlobalVariableProviders\EarlyAccessConstantsProvider;
use PSBits\Foundation\Service\GlobalVariableService;
use PSBits\Foundation\Utility\StringUtility;
use Throwable;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use UnitEnum;

/**
 * Class FlexFormService
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class FlexFormService
{
    public const string ALL_PLUGINS                          = '*';
    public const string EARLY_ACCESS_CONSTANTS_MARKER_PREFIX = 'EAC:';
    public const string MARKER_POSTFIX                       = '###';
    public const string MARKER_PREFIX                        = '###';

    /**
     * Replaces ###MARKER### placeholders in the FlexForm XML with resolved values.
     *
     * Supported marker formats:
     * - ###EAC:path.to.value###               — value from EarlyAccessConstantsProvider
     * - ###\Full\Class\Name::CONSTANT###     — PHP class constant value
     * - ###\Full\Class\Name::EnumCaseName### — enum case: backing value for backed enums, case name for unit enums
     *
     * Markers that cannot be resolved are left unchanged.
     */
    public function processMarkers(string $xml): string
    {
        return preg_replace_callback(
            '/###(.+?)###/s',
            static function (array $matches): string {
                try {
                    $markerContent = $matches[1];

                    if (str_starts_with($markerContent, self::EARLY_ACCESS_CONSTANTS_MARKER_PREFIX)) {
                        return self::stringifyResolvedMarkerValue(
                            GlobalVariableService::get(
                                EarlyAccessConstantsProvider::class . '.' . mb_substr(
                                    $markerContent,
                                    mb_strlen(self::EARLY_ACCESS_CONSTANTS_MARKER_PREFIX)
                                )
                            ),
                            $markerContent,
                            $matches[0]
                        );
                    }

                    return self::stringifyResolvedMarkerValue(
                        StringUtility::convertString($markerContent),
                        $markerContent,
                        $matches[0]
                    );
                } catch (Throwable) {
                    return $matches[0];
                }
            },
            $xml
        ) ?? $xml;
    }

    private static function stringifyResolvedMarkerValue(
        mixed  $value,
        string $markerContent,
        string $fallbackMarker,
    ): string {
        if ($value instanceof BackedEnum) {
            return (string)$value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (!is_scalar($value)) {
            return $fallbackMarker;
        }

        $value = (string)$value;

        if ($value === $markerContent) {
            return $fallbackMarker;
        }

        return $value;
    }

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
        $xml = $this->processMarkers($xml);

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
