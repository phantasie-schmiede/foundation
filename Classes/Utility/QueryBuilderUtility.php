<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility;

use Doctrine\DBAL\Exception;
use Generator;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class QueryBuilderUtility
 *
 * @package PSBits\Foundation\Utility
 */
class QueryBuilderUtility
{
    /**
     * @throws Exception
     */
    public static function processInChunks(QueryBuilder $queryBuilder, int $chunkSize): Generator
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // $queryBuilder must be cloned, as it changes state during execution and may therefore only be used once!
        $itemsToProcess = (clone $queryBuilder)->count('*')
            ->executeQuery()
            ->fetchOne();
        $queryBuilder->setMaxResults(
            0 < $queryBuilder->getMaxResults() ? min(
                $queryBuilder->getMaxResults(),
                $chunkSize
            ) : $chunkSize
        );

        while (0 < $itemsToProcess) {
            yield (clone $queryBuilder)->executeQuery();

            $itemsToProcess -= $chunkSize;

            if (0 < $itemsToProcess) {
                $queryBuilder->setFirstResult($queryBuilder->getFirstResult() + $chunkSize);

                /*
                 * Closes and reopens the database connection to free memory used by php and avoid errors like this:
                 * Fatal error: Allowed memory size of [] bytes exhausted (tried to allocate [] bytes)
                 */
                $connectionPool->resetConnections();
            }
        }
    }
}
