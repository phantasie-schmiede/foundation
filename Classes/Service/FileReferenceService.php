<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service;

use PSBits\Foundation\Service\Configuration\TcaService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class FileReferenceService
 *
 * @package PSBits\Foundation\Service
 */
class FileReferenceService
{
    public const TABLE_NAME = 'sys_file_reference';

    public function __construct(
        protected readonly TcaService $tcaService,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function create(
        AbstractEntity $domainModel,
        FileInterface  $file,
        string         $property,
    ): int {
        $fieldName = $this->tcaService->convertPropertyNameToColumnName($property);
        $tableName = $this->tcaService->convertClassNameToTableName($domainModel::class);

        $data = [
            'crdate'      => time(),
            'fieldname'   => $fieldName,
            'pid'         => $domainModel->getPid(),
            'tablenames'  => $tableName,
            'tstamp'      => time(),
            'uid_foreign' => $domainModel->getUid(),
            'uid_local'   => $file->getUid(),
        ];

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->insert(self::TABLE_NAME)
            ->values($data);
        $queryBuilder->executeStatement();

        return (int)$connection->lastInsertId($tableName);
    }
}
