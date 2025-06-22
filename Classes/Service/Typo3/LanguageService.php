<?php
declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace PSBits\Foundation\Service\Typo3;

use Doctrine\DBAL\Exception;
use JsonException;
use PSBits\Foundation\Utility\Localization\LoggingUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Localization\LanguageService as Typo3LanguageService;
use function is_string;
use function strlen;

/**
 * Class LanguageService
 *
 * Overwrites the original functions in order to respect plural forms and support logging.
 *
 * @package PSBits\Foundation\Service\Typo3
 * @TODO    Check original file on TYPO3 update!
 */
class LanguageService extends Typo3LanguageService
{
    private static bool $pluralFormMissing = false;

    /**
     * Overwrites the function to support logging.
     *
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function sL($input): string
    {
        $input = (string)$input;
        $output = parent::sL($input);

        if ('' !== $input) {
            LoggingUtility::logLanguageLabelAccess($input);
            LoggingUtility::logMissingLanguageLabels($input, '' !== $output && !self::$pluralFormMissing);
        }

        self::$pluralFormMissing = false;

        return $output;
    }

    /**
     * Returns the label with key $index from the $LOCAL_LANG array used as the second argument respecting possible
     * plural forms (falls back to plural form 0 if other plural form is given but not defined in language file).
     *
     * @param string $index         Label key
     * @param array  $localLanguage $LOCAL_LANG array to get label key from
     *
     * @return string
     */
    protected function getLLL(string $index, array $localLanguage): string
    {
        $pluralFormIndex = 0;

        if (str_contains($index, LocalizationUtility::PLURAL_FORM_MARKERS['BEGIN'])) {
            [
                $index,
                $pluralFormIndexStub,
            ] = explode(LocalizationUtility::PLURAL_FORM_MARKERS['BEGIN'], $index);
            $pluralFormIndex = (int)substr(
                $pluralFormIndexStub,
                0,
                -strlen(LocalizationUtility::PLURAL_FORM_MARKERS['END'])
            );
        }

        if (isset($localLanguage[$this->lang][$index])) {
            $languageKey = $this->lang;
        } elseif (isset($localLanguage['default'][$index])) {
            $languageKey = 'default';
        }

        if (isset ($languageKey)) {
            if (is_string($localLanguage[$languageKey][$index])) {
                $value = $localLanguage[$languageKey][$index];
            } elseif (isset($localLanguage[$languageKey][$index][$pluralFormIndex]['target'])) {
                $value = $localLanguage[$languageKey][$index][$pluralFormIndex]['target'];
            } else {
                // Set static property for logging
                self::$pluralFormMissing = true;
                $value = $localLanguage[$languageKey][$index][0]['target'];
            }
        }

        return $value ?? '';
    }
}
