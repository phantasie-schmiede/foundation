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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use function count;

/**
 * Class QueryUtility
 *
 * @package PSBits\Foundation\Utility
 */
class QueryUtility
{
    public static function applyConstraints(array $constraints, QueryInterface $query): void
    {
        switch (count($constraints)) {
            case 0:
                break;
            case 1:
                $query->matching($constraints[0]);
                break;
            default:
                $query->matching($query->logicalAnd(...$constraints));
        }
    }

    /**
     * @throws Exception
     */
    public static function processInChunks(QueryInterface $query, int $chunkSize): Generator
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $convertedQuery = GeneralUtility::makeInstance(Typo3DbQueryParser::class)
            ->convertQueryToDoctrineQueryBuilder($query);
        $itemsToProcess = $convertedQuery->count('*')
            ->executeQuery()
            ->fetchOne();
        $query->setLimit(0 < $query->getLimit() ? min($query->getLimit(), $chunkSize) : $chunkSize);

        while (0 < $itemsToProcess) {
            yield $query->execute();

            $itemsToProcess -= $chunkSize;

            if (0 < $itemsToProcess) {
                $query->setOffset($query->getOffset() + $chunkSize);

                /*
                 * Closes and reopens the database connection to free memory used by php and avoid errors like this:
                 * Fatal error: Allowed memory size of [] bytes exhausted (tried to allocate [] bytes)
                 */
                $connectionPool->resetConnections();
            }
        }
    }
}
