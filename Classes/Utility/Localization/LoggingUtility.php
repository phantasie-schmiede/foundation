<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Localization;

use Closure;
use Doctrine\DBAL\Exception;
use JsonException;
use PSBits\Foundation\Data\ExtensionInformation;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\ContextUtility;
use PSBits\Foundation\Utility\FileUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LoggingUtility
 *
 * @package PSBits\Foundation\Utility\Localization
 */
class LoggingUtility
{
    public const string LOCK_FILE_NAME    = '_.lock';
    public const array  LOG_FILE_PATTERNS = [
        'ACCESS'  => 'access.*.log',
        'MISSING' => 'missing.*.log',
    ];
    public const array  LOG_TABLES        = [
        'ACCESS'  => 'tx_foundation_accessed_language_labels',
        'MISSING' => 'tx_foundation_missing_language_labels',
    ];

    // Store extension configuration settings in static variables to avoid recurrent lookup. It's a mini cache.
    private static ?bool $logLanguageLabelAccess   = null;
    private static ?bool $logMissingLanguageLabels = null;

    /**
     * @throws Exception
     * @throws \Exception
     */
    public static function checkPostponedAccessLogEntries(): void
    {
        self::checkPostponedLogEntries(
            static function($logEntry) {
                [
                    $postponedKey,
                    $count,
                ] = json_decode(
                    $logEntry,
                    false,
                    512,
                    JSON_THROW_ON_ERROR
                );
                self::writeAccessLogToDatabase($postponedKey, (int)$count);
            },
            self::LOG_FILE_PATTERNS['ACCESS']
        );
    }

    /**
     * @throws \Exception
     */
    public static function checkPostponedLogEntries(Closure $closure, string $logFilePattern): void
    {
        $logFilePath = FilePathUtility::getLanguageLabelLogFilesPath();
        $lockFile = $logFilePath . self::LOCK_FILE_NAME;

        if (file_exists($lockFile)) {
            return;
        }

        FileUtility::write($lockFile, '');
        $finder = Finder::create()
            ->files()
            ->in($logFilePath)
            ->name($logFilePattern);

        /** @var SplFileInfo $fileInfo */
        foreach ($finder as $fileInfo) {
            $logFile = $fileInfo->getRealPath();
            $logContent = trim(file_get_contents($logFile));
            $closure($logContent);
            unlink($logFile);
        }

        unlink($lockFile);
    }

    /**
     * @throws JsonException
     * @throws \Exception
     */
    public static function checkPostponedMissingLogEntries(): void
    {
        self::checkPostponedLogEntries(
            static function($logEntry) {
                [
                    $postponedKey,
                    $postponedKeyExists,
                ] = json_decode(
                    $logEntry,
                    false,
                    512,
                    JSON_THROW_ON_ERROR
                );
                self::writeMissingLogToDatabase($postponedKey, $postponedKeyExists);
            },
            self::LOG_FILE_PATTERNS['MISSING']
        );
    }

    /**
     * @param string $key
     *
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws NotFoundExceptionInterface
     * @throws \Exception
     */
    public static function logLanguageLabelAccess(string $key): void
    {
        if (null === self::$logLanguageLabelAccess) {
            self::$logLanguageLabelAccess = (bool)self::getExtensionConfigurationSetting(
                'debug.logLanguageLabelAccess'
            );
        }

        if (!self::$logLanguageLabelAccess || !str_starts_with($key, 'LLL:')) {
            return;
        }

        if (ContextUtility::isBootProcessRunning()) {
            /*
             * The TCA is not loaded yet. That means the ConnectionPool is not available and the logging has to be
             * postponed.
             */
            $fileName = self::createFileName($key, self::LOG_FILE_PATTERNS['ACCESS']);

            if (file_exists($fileName)) {
                $count = (int)(json_decode(
                                   trim(file_get_contents($fileName)),
                                   false,
                                   512,
                                   JSON_THROW_ON_ERROR
                               )[1]) + 1;
            }

            FileUtility::write(
                $fileName,
                json_encode(
                    [
                        $key,
                        $count ?? 1,
                    ],
                    JSON_THROW_ON_ERROR
                )
            );
        } else {
            self::writeAccessLogToDatabase($key);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public static function logMissingLanguageLabel(string $key, bool $keyExists): void
    {
        if (null === self::$logMissingLanguageLabels) {
            self::$logMissingLanguageLabels = (bool)self::getExtensionConfigurationSetting(
                'debug.logMissingLanguageLabels'
            );
        }

        if (!self::$logMissingLanguageLabels) {
            return;
        }

        if (ContextUtility::isBootProcessRunning()) {
            /*
             * The TCA is not loaded yet. That means the ConnectionPool is not available and the logging has to be
             * postponed.
             */
            FileUtility::write(
                self::createFileName($key, self::LOG_FILE_PATTERNS['MISSING']),
                json_encode(
                    [
                        $key,
                        $keyExists,
                    ],
                    JSON_THROW_ON_ERROR
                )
            );
        } else {
            self::writeMissingLogToDatabase($key, $keyExists);
        }
    }

    private static function createFileName(string $key, string $pattern): string
    {
        return FilePathUtility::getLanguageLabelLogFilesPath() . str_replace(
                '*',
                substr(md5($key), 0, 8),
                $pattern
            );
    }

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    private static function getExtensionConfigurationSetting(string $key): mixed
    {
        $extensionInformation = GeneralUtility::makeInstance(ExtensionInformation::class);
        $extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);

        return $extensionInformationService->getConfiguration(
            $extensionInformation,
            $key
        );
    }

    /**
     * @throws Exception
     */
    private static function writeAccessLogToDatabase(string $key, int $count = 1): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::LOG_TABLES['ACCESS']);

        $queryBuilder = $connection->createQueryBuilder();
        $hitCount = $queryBuilder->select('hit_count')
            ->from(self::LOG_TABLES['ACCESS'])
            ->where(
                $queryBuilder->expr()
                    ->eq(
                        'locallang_key',
                        $queryBuilder->createNamedParameter($key)
                    )
            )
            ->executeQuery()
            ->fetchOne();

        if (false === $hitCount) {
            $connection->insert(self::LOG_TABLES['ACCESS'], [
                'hit_count'     => $count,
                'locallang_key' => $key,
            ]);
        } else {
            $connection->update(self::LOG_TABLES['ACCESS'], ['hit_count' => $hitCount + $count], [
                'locallang_key' => $key,
            ]);
        }
    }

    private static function writeMissingLogToDatabase(string $key, bool $keyExists): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::LOG_TABLES['MISSING']);

        // Avoid duplicates without using a select query as check for existing entries
        $connection->delete(self::LOG_TABLES['MISSING'], [
            'locallang_key' => $key,
        ]);

        if (false === $keyExists) {
            $connection->insert(self::LOG_TABLES['MISSING'], [
                'locallang_key' => $key,
            ]);
        }
    }
}
