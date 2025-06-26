<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility;

use JsonException;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\Localization\LoggingUtility;
use PSBits\Foundation\Utility\Localization\PluralFormUtility;
use PSBits\Foundation\Utility\Xml\XmlUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as ExtbaseLocalizationUtility;
use function array_slice;
use function is_string;

/**
 * Class LocalizationUtility
 *
 * @package PSBits\Foundation\Utility
 */
class LocalizationUtility
{
    public const array PLACEHOLDER_MARKERS = [
        'BEGIN' => '{',
        'END'   => '}',
    ];
    public const array PLURAL_FORM_MARKERS = [
        'BEGIN' => '[',
        'END'   => ']',
    ];
    public const string QUANTITY_ARGUMENT  = 'quantity';

    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param string      $key                     The key from the LOCAL_LANG array for which to return the value.
     * @param string|null $extensionName           The name of the extension
     * @param array|null  $arguments               The arguments of the extension, being passed over to vsprintf
     * @param string|null $languageKey             The language key or null for using the current language from the
     *                                             system
     *
     * @return string|null The value from LOCAL_LANG or null if no translation was found.
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @see ExtbaseLocalizationUtility
     */
    public static function translate(
        string $key,
        string $extensionName = null,
        array  $arguments = null,
        string $languageKey = null,
    ): ?string {
        if (isset($arguments[self::QUANTITY_ARGUMENT]) && is_numeric($arguments[self::QUANTITY_ARGUMENT])) {
            if (is_string($arguments[self::QUANTITY_ARGUMENT])) {
                $quantity = StringUtility::convertString($arguments[self::QUANTITY_ARGUMENT]);
            } else {
                $quantity = $arguments[self::QUANTITY_ARGUMENT];
            }

            $pluralForm = PluralFormUtility::getPluralForm(
                $languageKey ?? ContextUtility::getCurrentLocale(),
                $quantity
            );
            $key .= self::PLURAL_FORM_MARKERS['BEGIN'] . $pluralForm . self::PLURAL_FORM_MARKERS['END'];
        }

        $translation = ExtbaseLocalizationUtility::translate(
            $key,
            $extensionName,
            $arguments,
            $languageKey
        );

        // Insert translation arguments into placeholders:
        if (is_string($translation) && !empty($arguments) && ArrayUtility::isAssociative($arguments)) {
            $placeholderReplacements = [];

            foreach ($arguments as $placeholder => $replacement) {
                $placeholderReplacements[self::PLACEHOLDER_MARKERS['BEGIN'] . $placeholder . self::PLACEHOLDER_MARKERS['END']] = $replacement;
            }

            $translation = str_replace(
                array_keys($placeholderReplacements),
                array_values($placeholderReplacements),
                $translation
            );
        }

        return $translation;
    }

    /**
     * @param string      $key
     * @param string|null $extension
     * @param string      $newLineMarker If set, user defined new lines are created while plain line breaks will still
     *                                   be removed
     *
     * @return string
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function translateConcatenatingNewLines(
        string $key,
        string $extension = null,
        string $newLineMarker = '||',
    ): string {
        $translation = self::translate($key, $extension);
        $translation = preg_replace('/\s+/', ' ', $translation);
        if ('' !== $newLineMarker) {
            $translation = str_replace($newLineMarker, "\n", $translation);
        }

        return $translation;
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function translatePreservingNewLines(string $key, string $extension = null): string
    {
        $translation = self::translate($key, $extension);

        // split string by line breaks and remove surrounding whitespaces for each line
        $lines = array_map('trim', explode(LF, $translation));

        // remove first and/or last element if they are empty
        if ('' === $lines[0]) {
            array_shift($lines);
        }

        if ('' === array_values(array_slice($lines, -1))[0]) {
            array_pop($lines);
        }

        return implode("\n", $lines);
    }

    /**
     * This method can be used to check if a given language key is implemented even if TYPO3's LocalizationFactory isn't
     * initialized yet.
     *
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function translationExists(string $key, bool $logMissingTranslation = true): bool
    {
        $keyParts = explode(':', $key);

        if ('LLL' === $keyParts[0]) {
            unset($keyParts[0]);
        }

        $id = array_pop($keyParts);
        $languageFilePath = implode(':', $keyParts);
        $languageFilePath = GeneralUtility::getFileAbsFileName($languageFilePath);

        if (file_exists($languageFilePath)) {
            $xmlData = XmlUtility::convertFromXml(file_get_contents($languageFilePath));
            $transUnitArray = $xmlData['xliff']['file']['body']['trans-unit'] ?? null;

            if (null !== $transUnitArray) {
                // If file contains only one label, an additional array level has to be added for the following foreach.
                if (ArrayUtility::isAssociative($transUnitArray)) {
                    $transUnitArray = [$transUnitArray];
                }

                foreach ($transUnitArray as $transUnit) {
                    if (isset($transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']])) {
                        $transUnitTagAttributes = $transUnit[XmlUtility::SPECIAL_ARRAY_KEYS['ATTRIBUTES']];

                        if ($id === $transUnitTagAttributes['id']) {
                            LoggingUtility::logMissingLanguageLabels($key, true);

                            return true;
                        }
                    }
                }
            }
        }

        if ($logMissingTranslation) {
            LoggingUtility::logMissingLanguageLabels($key, false);
        }

        return false;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function validateLabel(string $label): bool
    {
        if ('' === $label) {
            return false;
        }

        if (!str_starts_with($label, FilePathUtility::LANGUAGE_LABEL_PREFIX)) {
            return true;
        }

        return self::translationExists($label);
    }
}
