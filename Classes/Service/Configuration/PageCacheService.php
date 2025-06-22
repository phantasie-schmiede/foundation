<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration;

use Doctrine\DBAL\Exception;
use InvalidArgumentException;
use PSBits\Foundation\Service\Configuration\CacheConfigurationBuilder\BuilderInterface;
use PSBits\Foundation\Utility\TypoScript\TypoScriptUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageCacheService
 *
 * @package PSBits\Foundation\Service\Configuration
 */
class PageCacheService
{
    protected static array $registeredBuilders = [];

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
    ) {
    }

    public static function registerBuilder(string $builderClass): void
    {
        if (!is_subclass_of($builderClass, BuilderInterface::class)) {
            throw new InvalidArgumentException(
                __CLASS__ . ': The builder doest not implement the required BuilderInterface!', 1729847294
            );
        }

        self::$registeredBuilders[] = $builderClass;
    }

    /**
     * @throws Exception
     */
    public function buildTSconfig(): string
    {
        $pagesArray = [];

        foreach (self::$registeredBuilders as $builder) {
            /** @var BuilderInterface $builderInstance */
            $builderInstance = GeneralUtility::makeInstance($builder);

            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($builderInstance->getTable());

            // Select distinct PIDs of all records of the given table
            $pids = $queryBuilder->select('pid')
                ->from($builderInstance->getTable())
                ->groupBy('pid')
                ->executeQuery()
                ->fetchFirstColumn();

            foreach ($pids as $pid) {
                if (!isset($pagesArray[$pid])) {
                    $pagesArray[$pid] = [];
                }

                $pagesArray[$pid][] = $builderInstance->getPidCacheTagPrefix();
            }
        }

        $tsConfigArray = [];

        foreach ($pagesArray as $pid => $cacheTags) {
            $cacheTags = array_unique($cacheTags);

            array_walk($cacheTags, static function(&$value) use ($pid) {
                $value = 'cacheTag:' . $value . $pid;
            });

            $tsConfigArray[] = [
                TypoScriptUtility::TYPO_SCRIPT_KEYS['CONDITION'] => $pid . ' == traverse(page, \'uid\')',
                'TCEMAIN'                                        => ['clearCacheCmd' => implode(', ', $cacheTags)],
            ];
        }

        if (empty($tsConfigArray)) {
            return '';
        }

        $tsConfig = '// Delete cache of pages when editing records in related SysFolders.' . LF;

        foreach ($tsConfigArray as $tsConfigArrayItem) {
            $tsConfig .= LF . TypoScriptUtility::convertArrayToTypoScript($tsConfigArrayItem);
        }

        return $tsConfig;
    }

    public function buildTypoScript(): string
    {
        $pagesArray = [];

        foreach (self::$registeredBuilders as $builder) {
            /** @var BuilderInterface $builderInstance */
            $builderInstance = GeneralUtility::makeInstance($builder);

            foreach ($builderInstance->collectSourceRelations() as $pid => $configuration) {
                if (!isset($pagesArray[$pid])) {
                    $pagesArray[$pid] = [];
                }

                array_walk($configuration, static function(&$value) use ($builderInstance) {
                    $value = $builderInstance->getTable() . ':' . $value;
                });

                $pagesArray[$pid] = array_merge($pagesArray[$pid], $configuration);
            }
        }

        if (empty($pagesArray)) {
            return '';
        }

        array_walk($pagesArray, static function(&$value) {
            $value = implode(', ', $value);
        });

        return TypoScriptUtility::convertArrayToTypoScript(
            [
                TypoScriptUtility::TYPO_SCRIPT_KEYS['COMMENT'] => 'Define which records should be considered when calculating the cache lifetime for pages.',
                'config'                                       => [
                    'cache' => $pagesArray,
                ],
            ]
        );
    }
}
