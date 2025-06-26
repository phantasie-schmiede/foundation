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
     * @param array|null  $appearance           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/Appearance.html
     * @param string|null $foreignDefaultSortBy https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Index.html#confval-inline-foreign-default-sortby
     * @param string|null $foreignField         https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignField.html
     * @param array|null  $foreignMatchFields   https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignMatchFields.html
     * @param string|null $foreignSortBy        https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignSortby.html#confval-foreign_sortby
     * @param string|null $foreignTable         https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/ForeignTable.html
     * @param string      $linkedModel          Instead of directly specifying a foreign table, it is possible to
     *                                          specify a domain model class.
     * @param int|null    $maxItems             https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/Maxitems.html
     * @param string|null $mm                   https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/Mm.html
     * @param array|null  $mmMatchFields
     * @param string|null $mmOppositeField      https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Inline/Properties/Mm.html#confval-MM_opposite_field-type-inline
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(
        protected ?array  $appearance = [
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
        protected ?string $foreignDefaultSortBy = null,
        protected ?string $foreignField = null,
        protected ?array  $foreignMatchFields = null,
        protected ?string $foreignSortBy = null,
        protected ?string $foreignTable = null,
        protected string  $linkedModel = '',
        protected ?int    $maxItems = null,
        protected ?string $mm = null,
        protected ?array  $mmMatchFields = null,
        protected ?string $mmOppositeField = null,
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

    public function getForeignMatchFields(): ?array
    {
        return $this->foreignMatchFields;
    }

    public function getForeignSortBy(): ?string
    {
        return $this->foreignSortBy;
    }

    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
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
}
