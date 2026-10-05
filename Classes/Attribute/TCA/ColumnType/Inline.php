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
use PSBits\Foundation\Service\Configuration\TcaService;
use PSBits\Foundation\Utility\Database\DefinitionUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Inline
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Inline extends AbstractColumnType
{
    protected TcaService $tcaService;

    /**
     * @param array|null   $appearance                        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-appearance
     * @param int|null     $autoSizeMax                       https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-autosizemax
     * @param string[]|null $customControls                   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-customcontrols
     * @param bool|null    $disableMovingChildrenWithParent   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-behaviour-disablemovingchildrenwithparent
     * @param bool|null    $enableCascadingDelete             https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-behaviour-enablecascadingdelete
     * @param string|null  $filter                            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-filter
     * @param string|null  $foreignDefaultSortBy              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-default-sortby
     * @param string|null  $foreignField                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-field
     * @param string|null  $foreignLabel                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-label
     * @param array|null   $foreignMatchFields                https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-match-fields
     * @param array|null   $foreignSelector                   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-selector
     * @param string|null  $foreignSortBy                     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-sortby
     * @param string|null  $foreignTable                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-properties-foreign-table
     * @param string       $linkedModel                      Instead of directly specifying a foreign table, it is
     *                                                       possible to specify a domain model class.
     * @param string|null  $foreignTableField                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-table-field
     * @param string|null  $foreignUnique                     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-unique
     * @param int|null     $maxItems                          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-maxitems
     * @param int|null     $minItems                          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-minitems
     * @param string|null  $mm                                https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-mm
     * @param array|null   $mmMatchFields                     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/Mm.html#confval-mm-match-fields
     * @param string|null  $mmOppositeField                   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-mm-opposite-field
     * @param array|null   $mmOppositeUsage                   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/Mm.html#confval-mm-oppositeusage
     * @param string|null  $mmTableWhere                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/Mm.html#confval-mm-table-where
     * @param string|null  $overrideChildTca                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-overridechildtca
     * @param Relationship $relationship                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-relationship
     * @param int|null     $size                              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-size
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(
        protected ?array   $appearance = [
            'collapseAll'                     => true,
            'enabledControls'                 => [
                'dragdrop' => true,
            ],
            'expandSingle'                    => true,
            'levelLinksPosition'              => 'bottom',
            'showAllLocalizationLink'         => true,
            'showPossibleLocalizationRecords' => true,
            'showSynchronizationLink'         => true,
            'useSortable'                     => true,
        ],
        protected ?int     $autoSizeMax = null,
        protected ?array   $customControls = null,
        protected ?bool    $disableMovingChildrenWithParent = null,
        protected ?bool    $enableCascadingDelete = null,
        protected ?string  $filter = null,
        protected ?string  $foreignDefaultSortBy = null,
        protected ?string  $foreignField = null,
        protected ?string  $foreignLabel = null,
        protected ?array   $foreignMatchFields = null,
        protected ?array   $foreignSelector = null,
        protected ?string  $foreignSortBy = null,
        protected ?string  $foreignTable = null,
        protected string   $linkedModel = '',
        protected ?string  $foreignTableField = null,
        protected ?string  $foreignUnique = null,
        protected ?int     $maxItems = null,
        protected ?int     $minItems = null,
        protected ?string  $mm = null,
        protected ?array   $mmMatchFields = null,
        protected ?string  $mmOppositeField = null,
        protected ?array   $mmOppositeUsage = null,
        protected ?string  $mmTableWhere = null,
        protected ?string  $overrideChildTca = null,
        protected Relationship $relationship = Relationship::oneToMany,
        protected ?int     $size = null,
    ) {
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);

        if (class_exists($linkedModel)) {
            $this->foreignTable = $this->tcaService->convertClassNameToTableName($linkedModel);
        }
    }

    public function getAppearance(): array
    {
        return $this->appearance;
    }

    public function getAutoSizeMax(): ?int
    {
        return $this->autoSizeMax;
    }

    public function getCustomControls(): ?array
    {
        return $this->customControls;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::int(unsigned: true);
    }

    public function getForeignDefaultSortBy(): ?string
    {
        return $this->foreignDefaultSortBy;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getForeignField(): ?string
    {
        if (null === $this->foreignField) {
            return null;
        }

        return $this->tcaService->convertPropertyNameToColumnName($this->foreignField, $this->linkedModel);
    }

    public function getForeignLabel(): ?string
    {
        return $this->foreignLabel;
    }

    public function getForeignMatchFields(): ?array
    {
        return $this->foreignMatchFields;
    }

    public function getForeignSelector(): ?array
    {
        return $this->foreignSelector;
    }

    public function getForeignSortBy(): ?string
    {
        return $this->foreignSortBy;
    }

    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    public function getForeignTableField(): ?string
    {
        return $this->foreignTableField;
    }

    public function getForeignUnique(): ?string
    {
        return $this->foreignUnique;
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

    public function getOverrideChildTca(): ?string
    {
        return $this->overrideChildTca;
    }

    public function getRelationship(): string
    {
        return $this->relationship->value;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function toArray(): array
    {
        $configuration = parent::toArray();
        $behaviour     = [];

        if (null !== $this->enableCascadingDelete) {
            $behaviour['enableCascadingDelete'] = $this->enableCascadingDelete;
        }

        if (null !== $this->disableMovingChildrenWithParent) {
            $behaviour['disableMovingChildrenWithParent'] = $this->disableMovingChildrenWithParent;
        }

        if (!empty($behaviour)) {
            $configuration['behaviour'] = $behaviour;
        }

        return $configuration;
    }
}
