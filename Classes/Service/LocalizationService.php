<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service;

use JsonException;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;

/**
 * Class LocalizationService
 *
 * @deprecated Use \PSBits\Foundation\Utility\LocalizationUtility instead!
 * @package    PSBits\Foundation\Service
 */
class LocalizationService
{
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
     */
    public function translate(
        string $key,
        string $extensionName = null,
        array  $arguments = null,
        string $languageKey = null,
    ): ?string {
        return LocalizationUtility::translate(
            $key,
            $extensionName,
            $arguments,
            $languageKey
        );
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
    public function translateConcatenatingNewLines(
        string $key,
        string $extension = null,
        string $newLineMarker = '||',
    ): string {
        return LocalizationUtility::translateConcatenatingNewLines(
            $key,
            $extension,
            $newLineMarker
        );
    }

    /**
     * @throws AspectNotFoundException
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function translatePreservingNewLines(string $key, string $extension = null): string
    {
        return LocalizationUtility::translatePreservingNewLines($key, $extension);
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
    public function translationExists(string $key, bool $logMissingTranslation = true): bool
    {
        return LocalizationUtility::translationExists($key, $logMissingTranslation);
    }
}
