<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service\Configuration\CacheConfigurationBuilder;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BuilderForNews
 *
 * @package PSBits\Foundation\Service\Configuration\CacheConfigurationBuilder
 */
readonly class BuilderForNews implements BuilderInterface
{
    public function __construct(
        protected ConnectionPool  $connectionPool,
        protected FlexFormService $flexFormService,
    ) {
    }

    /**
     * @throws Exception
     */
    public function collectSourceRelations(): array
    {
        $contentTable = 'tt_content';
        $relations = [];

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($contentTable);
        $pluginRows = $queryBuilder->select('pi_flexform', 'pid')
            ->from($contentTable)
            ->where(
                $queryBuilder->expr()
                    ->like('CType', $queryBuilder->createNamedParameter('news%'))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($pluginRows as $pluginRow) {
            if (!isset($relations[$pluginRow['pid']])) {
                $relations[$pluginRow['pid']] = [];
            }

            $pluginConfiguration = $this->flexFormService->convertFlexFormContentToArray($pluginRow['pi_flexform']);
            $startingPoints = GeneralUtility::intExplode(',', $pluginConfiguration['settings']['startingpoint'], true);
            array_push($relations[$pluginRow['pid']], ...$startingPoints);
        }

        return $relations;
    }

    public function getPidCacheTagPrefix(): string
    {
        return 'tx_news_pid_';
    }

    public function getTable(): string
    {
        return 'tx_news_domain_model_news';
    }
}
