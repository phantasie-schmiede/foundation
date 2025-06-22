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
use PSBits\Foundation\Utility\ArrayUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Utility\PathUtility;
use function array_slice;

/**
 * Class FilePathUtility
 *
 * @package PSBits\Foundation\Utility\Configuration
 */
class FilePathUtility
{
    public const EXTENSION_DIRECTORY_PREFIX    = 'EXT:';
    public const LANGUAGE_FILE_EXTENSION       = '.xlf';
    public const LANGUAGE_LABEL_LOG_FILES_PATH = 'log/foundation/language_labels/';
    public const LANGUAGE_LABEL_PREFIX         = 'LLL:';

    public static function getLanguageFilePath(ExtensionInformationInterface $extensionInformation): string
    {
        return self::LANGUAGE_LABEL_PREFIX . self::getResourcePath($extensionInformation) . 'Private/Language/';
    }

    /**
     * For use in php-files located in an extension directory.
     *
     * This function generates the corresponding prefix for backend labels.
     * Example: Calling from EXT:my_extension/Configuration/TCA/Overrides/tt_content.php without submitting
     * a filename will return
     * "LLL:EXT:my_extension/Resources/Private/Language/Backend/Configuration/TCA/Overrides/tt_content.xlf:".
     *
     * @param ExtensionInformationInterface $extensionInformation
     * @param string|null                   $filename custom filename without extension (.xlf is added automatically)
     *
     * @return string
     */
    public static function getLanguageFilePathForCurrentFile(
        ExtensionInformationInterface $extensionInformation,
        string                        $filename = null,
    ): string {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $callingFilePathElements = explode('/', $trace[0]['file']);

        // Search for extension_key in file path. If not found, search for package-key.
        $indexOfExtensionKey = ArrayUtility::findLastOccurrence(
            $extensionInformation->getExtensionKey(),
            $callingFilePathElements
        ) ?: ArrayUtility::findLastOccurrence(
            str_replace(
                '_',
                '-',
                $extensionInformation->getExtensionKey()
            ),
            $callingFilePathElements
        );

        // Retrieve all array items AFTER the extension key.
        $relativeFilePathElements = array_slice($callingFilePathElements, $indexOfExtensionKey + 1);
        $callingFilename = array_pop($relativeFilePathElements);
        $filename = ($filename ?? str_replace('.php', '', $callingFilename));

        if (!str_ends_with($filename, self::LANGUAGE_FILE_EXTENSION)) {
            $filename .= self::LANGUAGE_FILE_EXTENSION;
        }

        return self::getLanguageFilePath($extensionInformation) . 'Backend/' . implode(
                '/',
                $relativeFilePathElements
            ) . '/' . lcfirst($filename) . ':';
    }

    public static function getLanguageLabelLogFilesPath(): string
    {
        return rtrim(
                Environment::getVarPath(),
                '/'
            ) . '/' . self::LANGUAGE_LABEL_LOG_FILES_PATH;
    }

    public static function getPrivateResourcePath(
        ExtensionInformationInterface $extensionInformation,
        string                        $subdirectory = '',
    ): string {
        return self::getResourcePath($extensionInformation, 'Private/' . ltrim($subdirectory, '/'));
    }

    public static function getPublicResourcePath(
        ExtensionInformationInterface $extensionInformation,
        string                        $subdirectory = '',
    ): string {
        return self::getResourcePath($extensionInformation, 'Public/' . ltrim($subdirectory, '/'));
    }

    /**
     * @throws InvalidFileException
     */
    public static function getPublicResourceWebPath(
        ExtensionInformationInterface $extensionInformation,
        string                        $subdirectory = '',
    ): string {
        $directoryPath = self::getPublicResourcePath($extensionInformation, $subdirectory);

        return PathUtility::getPublicResourceWebPath($directoryPath);
    }

    public static function getResourcePath(
        ExtensionInformationInterface $extensionInformation,
        string                        $subdirectory = '',
    ): string {
        $directoryPath = $extensionInformation->getExtensionKey() . '/Resources/' . ltrim($subdirectory, '/');

        return self::EXTENSION_DIRECTORY_PREFIX . $directoryPath;
    }
}
