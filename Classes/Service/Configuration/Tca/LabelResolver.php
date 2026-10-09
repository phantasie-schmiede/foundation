<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\Tca;

use JsonException;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function lcfirst;
use function strrpos;
use function substr;

/**
 * Class LabelResolver
 *
 * @package PSBits\Foundation\Service\Configuration\Tca
 */
class LabelResolver
{
    /**
     * Builds the default LLL label path of the domain model the given class belongs to.
     *
     * If the table is already present in the TCA (i.e. the class extends another domain model), the label path
     * points to the Overrides directory.
     *
     * @param string $className Fully qualified class name of the domain model.
     * @param string $tableName The table name of the domain model.
     */
    public static function getDefaultLabelPath(string $className, string $tableName): string
    {
        $extensionKey = GeneralUtility::makeInstance(ExtensionInformationService::class)
                            ->extractExtensionInformationFromClassName($className)['extensionKey'];
        $defaultLabelPath = 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/Backend/Configuration/TCA/';

        if (isset($GLOBALS['TCA'][$tableName])) {
            $defaultLabelPath .= 'Overrides/';
        }

        $defaultLabelPath .= lcfirst(substr($className, strrpos($className, '\\') + 1)) . '.xlf:';

        return $defaultLabelPath;
    }

    /**
     * Resolves a label against a default label.
     * If the given label is valid (plain text or an existing LLL label), it is returned. Otherwise, the default
     * label is returned if a translation exists. If neither is available, the fallback is returned.
     *
     * @param string $label                 The label to validate.
     * @param string $defaultLabel          The default LLL label (including the complete label path).
     * @param string $fallback              The value to return if no valid label and no default translation exists.
     * @param bool   $logMissingTranslation Whether the default label is logged as missing translation if it does not
     *                                      exist.
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function resolveLabel(
        string $label,
        string $defaultLabel,
        string $fallback = '',
        bool   $logMissingTranslation = true,
    ): string {
        if (true === LocalizationUtility::validateLabel($label)) {
            return $label;
        }

        if (LocalizationUtility::translationExists($defaultLabel, $logMissingTranslation)) {
            return $defaultLabel;
        }

        return $fallback;
    }
}
