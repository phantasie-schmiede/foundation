<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute\TCA\ColumnType;

use Attribute;
use PSBits\Foundation\Enum\Relationship;
use PSBits\Foundation\Utility\Database\DefinitionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

/**
 * Class Category
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Category extends AbstractColumnType
{
    /**
     * @param array        $exclusiveKeys                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-exclusivekeys
     * @param string|null  $foreignTableItemGroup         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-foreign-table-item-group
     * @param string|null  $foreignTablePrefix            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-foreign-table-prefix
     * @param string|null  $foreignTableWhere             https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-foreign-table-where
     * @param array|null   $itemGroups                    https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-item-groups
     * @param int|null     $maxItems                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-maxitems
     * @param int|null     $minItems                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-minitems
     * @param Relationship $relationship                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-relationship
     * @param int|null     $size                          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-size
     * @param array|null   $treeConfig                    https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Category/Index.html#confval-category-treeconfig
     * @param string|null  $treeConfigChildrenField       https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-childrenfield
     *                                                    You can use the property name. It will be converted to the column
     *                                                    name automatically.
     * @param string|null  $treeConfigDataProvider        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-dataprovider
     * @param bool|null    $treeConfigExpandAll           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-expandall
     * @param int|null     $treeConfigMaxLevels           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-maxlevels
     * @param string|null  $treeConfigNonSelectableLevels https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-nonselectablelevels
     * @param string|null  $treeConfigParentField         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-parentfield
     *                                                    You can use the property name. It will be converted to the column
     *                                                    name automatically.
     * @param bool|null    $treeConfigShowHeader          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-showheader
     * @param array        $treeConfigStartingPoints      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-startingpoints
     */
    public function __construct(
        protected array        $exclusiveKeys = [],
        protected ?string      $foreignTableItemGroup = null,
        protected ?string      $foreignTablePrefix = null,
        protected ?string      $foreignTableWhere = null,
        protected ?array       $itemGroups = null,
        protected ?int         $maxItems = null,
        protected ?int         $minItems = null,
        protected Relationship $relationship = Relationship::manyToMany,
        protected ?int         $size = null,
        protected ?array       $treeConfig = null,
        protected ?string      $treeConfigChildrenField = null,
        protected ?string      $treeConfigDataProvider = null,
        protected ?bool        $treeConfigExpandAll = null,
        protected ?int         $treeConfigMaxLevels = null,
        protected ?string      $treeConfigNonSelectableLevels = null,
        protected ?string      $treeConfigParentField = null,
        protected ?bool        $treeConfigShowHeader = null,
        protected array        $treeConfigStartingPoints = [],
    ) {
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::int(unsigned: true);
    }

    public function getExclusiveKeys(): ?string
    {
        return $this->exclusiveKeys ? implode(', ', $this->exclusiveKeys) : null;
    }

    public function getForeignTableItemGroup(): ?string
    {
        return $this->foreignTableItemGroup;
    }

    public function getForeignTablePrefix(): ?string
    {
        return $this->foreignTablePrefix;
    }

    public function getForeignTableWhere(): ?string
    {
        return $this->foreignTableWhere;
    }

    public function getItemGroups(): ?array
    {
        return $this->itemGroups;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    public function getRelationship(): string
    {
        return $this->relationship->value;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getTreeConfig(): ?array
    {
        if (null !== $this->treeConfigExpandAll) {
            $configuration['appearance']['expandAll'] = $this->treeConfigExpandAll;
        }

        if (0 < $this->treeConfigMaxLevels) {
            $configuration['appearance']['maxLevels'] = $this->treeConfigMaxLevels;
        }

        if (null !== $this->treeConfigNonSelectableLevels) {
            $configuration['appearance']['nonSelectableLevels'] = $this->treeConfigNonSelectableLevels;
        }

        if (null !== $this->treeConfigShowHeader) {
            $configuration['appearance']['showHeader'] = $this->treeConfigShowHeader;
        }

        if (null !== $this->treeConfigChildrenField) {
            $configuration['childrenField'] = $this->tcaService()->convertPropertyNameToColumnName(
                $this->treeConfigChildrenField
            );
        }

        if (null !== $this->treeConfigDataProvider) {
            $configuration['dataProvider'] = $this->treeConfigDataProvider;
        }

        if (null !== $this->treeConfigParentField) {
            $configuration['parentField'] = $this->tcaService()->convertPropertyNameToColumnName(
                $this->treeConfigParentField
            );
        }

        if (!empty($this->treeConfigStartingPoints)) {
            $configuration['startingPoints'] = implode(', ', $this->treeConfigStartingPoints);
        }

        return $configuration ?? null;
    }
}
