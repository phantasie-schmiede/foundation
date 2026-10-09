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
 * Class Group
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Group extends AbstractColumnType
{
    /**
     * $mmOppositeUsage automatically populates $allowed if it's empty.
     *
     * @param string|null  $allowed                                https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-allowed
     * @param int|null     $autoSizeMax                            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-autosizemax
     * @param bool|null    $dontRemapTablesOnCopy                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-dontremaptablesoncopy
     * @param array|null   $elementBrowserEntryPoints              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-elementbrowserentrypoints
     * @param string|null  $filter                                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-filter
     * @param bool|null    $hideDeleteIcon                         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-hidedeleteicon
     * @param bool|null    $hideMoveIcons                          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-hidemoveicons
     * @param string       $linkedModel                            Instead of directly specifying a foreign table, it is
     *                                                             possible to specify a domain model class.
     * @param bool|null    $localizeReferencesAtParentLocalization https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-localizereferencesatparentlocalization
     * @param int|null     $maxItems                               https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-maxitems
     * @param int|null     $minItems                               https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-minitems
     * @param string|null  $mm                                     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-mm
     * @param array|null   $mmMatchFields                          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-mm-match-fields
     * @param string|null  $mmOppositeField                        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-mm-opposite-field
     * @param array|null   $mmOppositeUsage                        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-mm-opposite-usage
     * @param string|null  $mmTableWhere                           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-mm-table-where
     * @param bool|null    $multiple                               https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-multiple
     * @param string|null  $prependTname                           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-prepend-tname
     * @param Relationship $relationship                           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-relationship
     * @param int|null     $size                                   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Group/Index.html#confval-group-size
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(
        protected ?string      $allowed = null,
        protected ?int         $autoSizeMax = null,
        protected ?bool        $dontRemapTablesOnCopy = null,
        protected ?array       $elementBrowserEntryPoints = null,
        protected ?string      $filter = null,
        protected ?bool        $hideDeleteIcon = null,
        protected ?bool        $hideMoveIcons = null,
        protected string       $linkedModel = '',
        protected ?bool        $localizeReferencesAtParentLocalization = null,
        protected ?int         $maxItems = null,
        protected ?int         $minItems = null,
        protected ?string      $mm = null,
        protected ?array       $mmMatchFields = null,
        protected ?string      $mmOppositeField = null,
        protected ?array       $mmOppositeUsage = null,
        protected ?string      $mmTableWhere = null,
        protected ?bool        $multiple = null,
        protected ?string      $prependTname = null,
        protected Relationship $relationship = Relationship::manyToMany,
        protected ?int         $size = null,
    ) {
        if (class_exists($linkedModel)) {
            $this->allowed = $this->tcaService()->convertClassNameToTableName($linkedModel);
        }

        if (!empty($mmOppositeUsage)) {
            $this->mmOppositeUsage = [];

            foreach ($mmOppositeUsage as $modelOrTableName => $fieldOrPropertyNames) {
                $this->mmOppositeUsage[$this->tcaService()->convertClassNameToTableName($modelOrTableName)] = array_map(
                    fn(string $fieldOrPropertyName) => $this->tcaService()->convertPropertyNameToColumnName(
                        $fieldOrPropertyName
                    ),
                    $fieldOrPropertyNames
                );
            }

            if (null === $this->allowed) {
                $this->allowed = implode(',', array_keys($this->mmOppositeUsage));
            }
        }
    }

    public function getAllowed(): ?string
    {
        return $this->allowed;
    }

    public function getAutoSizeMax(): ?int
    {
        return $this->autoSizeMax;
    }

    public function getDatabaseDefinition(): string
    {
        if (empty($this->mm)) {
            return DefinitionUtility::text();
        }

        return DefinitionUtility::int(unsigned: true);
    }

    public function getElementBrowserEntryPoints(): ?array
    {
        return $this->elementBrowserEntryPoints;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    public function getMm(): ?string
    {
        return $this->mm;
    }

    public function getMmMatchFields(): ?array
    {
        return $this->mmMatchFields;
    }

    public function getMmOppositeField(): ?string
    {
        return $this->mmOppositeField;
    }

    public function getMmOppositeUsage(): ?array
    {
        return $this->mmOppositeUsage;
    }

    public function getMmTableWhere(): ?string
    {
        return $this->mmTableWhere;
    }

    public function getPrependTname(): ?string
    {
        return $this->prependTname;
    }

    public function getRelationship(): string
    {
        return $this->relationship->value;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function isDontRemapTablesOnCopy(): ?bool
    {
        return $this->dontRemapTablesOnCopy;
    }

    public function isHideDeleteIcon(): ?bool
    {
        return $this->hideDeleteIcon;
    }

    public function isHideMoveIcons(): ?bool
    {
        return $this->hideMoveIcons;
    }

    public function isLocalizeReferencesAtParentLocalization(): ?bool
    {
        return $this->localizeReferencesAtParentLocalization;
    }

    public function isMultiple(): ?bool
    {
        return $this->multiple;
    }
}
