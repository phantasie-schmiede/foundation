<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Service;

use Exception;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Exceptions\ImplementationException;
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Utility\ObjectUtility;
use PSBits\Foundation\Utility\ReflectionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Repository;
use function get_class;

/**
 * Class ObjectService
 *
 * @package PSBits\Foundation\Service
 */
class ObjectService
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TcaService     $tcaService,
    ) {
    }

    /**
     * This can be used to get a collection of domain models from a mm-relation of type group with more than one table
     * allowed.
     * If the related table name can be resolved to a domain model, the result will include an instance of it,
     * otherwise a database row will be fetched (no overlays are applied yet!. If more than one domain model is mapped
     * to a single table, the preferred models (array of full qualified class names) will be used as filter. The first
     * mapping result is used if there is no preferred model.
     *
     * @throws ContainerExceptionInterface
     * @throws ImplementationException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function resolveMmRelationWithDifferentTables(
        AbstractEntity $domainModel,
        string         $property,
        array          $preferredModels = [],
    ): array {
        $columnName = $this->tcaService->convertPropertyNameToColumnName($property, $domainModel::class);
        $tableName = $this->tcaService->convertClassNameToTableName($domainModel::class);
        $fieldConfiguration = $GLOBALS['TCA'][$tableName]['columns'][$columnName]['config'];

        if ('group' !== $fieldConfiguration['type']) {
            throw new RuntimeException(
                __CLASS__ . ': The property "' . $property . '" of object "' . get_class(
                    $domainModel
                ) . '" is not of TCA type group!', 1721396926
            );
        }

        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->start(
            $domainModel->_getProperty($property),
            $fieldConfiguration['allowed'] ?? $fieldConfiguration['foreign_table'] ?? '',
            $fieldConfiguration['MM'] ?? '',
            $domainModel->getUid(),
            $tableName,
            $fieldConfiguration
        );

        $relationHandler->processDeletePlaceholder();

        $result = [];
        $repositories = [];

        foreach ($relationHandler->itemArray as $item) {
            $classNames = $this->tcaService->convertTableNameToClassNames($item['table']);

            if (!empty($classNames)) {
                $classNames = (array_intersect($classNames, $preferredModels) ?: $classNames);
                $className = array_shift($classNames);
                $repositoryClassName = ObjectUtility::getRepositoryClassName($className);

                if (isset($repositories[$repositoryClassName])) {
                    $repository = $repositories[$repositoryClassName];
                } elseif (class_exists($repositoryClassName)) {
                    $repository = GeneralUtility::makeInstance($repositoryClassName);
                    $repositories[$repositoryClassName] = $repository;
                }

                if (isset($repository) && $repository instanceof Repository) {
                    $result[] = $repository->findByUid($item['id']);
                    continue;
                }
            }

            $result[] = BackendUtility::getRecord($item['table'], $item['id']);
        }

        return $result;
    }

    /**
     * If you have a select field in TCA with 'multiple' set to true, Extbase still returns each selected record only
     * once. This method returns the whole selected set sorted as in backend.
     *
     * @throws Exception
     */
    public function resolveMultipleMmRelation(AbstractDomainObject $object, string $property): array
    {
        // Store each ObjectStorage element by uid.
        $reflectionClass = new ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($property);

        $selectConfiguration = ReflectionUtility::getAttributeInstance(Select::class, $reflectionProperty);

        if (!$selectConfiguration instanceof Select) {
            throw new RuntimeException(
                __CLASS__ . ': The property "' . $property . '" of object "' . get_class(
                    $object
                ) . '" is not of TCA type select!', 1584867595
            );
        }

        if (empty($selectConfiguration->getMm())) {
            throw new RuntimeException(
                __CLASS__ . ': The select attribute of the property "' . $property . '" of object "' . get_class(
                    $object
                ) . '" does not define a mm-table!', 1687382027
            );
        }

        $objectStorageElements = $reflectionProperty->getValue($object);
        $objectStorageElementsByUid = [];

        /** @var AbstractDomainObject $element */
        foreach ($objectStorageElements as $element) {
            $objectStorageElementsByUid[$element->getUid()] = $element;
        }

        // Get all mm-relation entries.
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($selectConfiguration->getMm());
        $statement = $queryBuilder->select('uid_foreign')
            ->from($selectConfiguration->getMm())
            ->where(
                $queryBuilder->expr()
                    ->eq('uid_local', $queryBuilder->createNamedParameter($object->getUid()))
            )
            ->orderBy('sorting')
            ->executeQuery();

        // Create a complete collection by using the ordered items of the mm-table by replacing the foreign uid with the
        // concrete object.
        $completeElements = [];

        while ($row = $statement->fetchAssociative()) {
            $completeElements[] = $objectStorageElementsByUid[$row['uid_foreign']];
        }

        return $completeElements;
    }
}
