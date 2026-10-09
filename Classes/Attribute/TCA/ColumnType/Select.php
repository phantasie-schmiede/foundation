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
use JsonException;
use PSBits\Foundation\Enum\Relationship;
use PSBits\Foundation\Enum\SelectRenderType;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\ExtensionInformationService;
use PSBits\Foundation\Utility\ArrayUtility;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\Database\DefinitionUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function is_array;
use function is_float;
use function is_int;
use function is_string;

/**
 * Class Select
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Select extends AbstractColumnType implements ColumnTypeWithItemsInterface
{
    /** @deprecated Will be removed in v4.0. Use more specific constant instead! */
    public const array EMPTY_DEFAULT_ITEM = [
        [
            'label' => self::LANGUAGE_LABEL_PREFIX . 'pleaseChoose',
            'value' => 0,
        ],
    ];
    public const array EMPTY_DEFAULT_ITEM_MANDATORY_INT = [
        [
            'label' => self::LANGUAGE_LABEL_PREFIX . 'pleaseChoose.mandatory',
            'value' => 0,
        ],
    ];
    public const array EMPTY_DEFAULT_ITEM_MANDATORY_STRING = [
        [
            'label' => self::LANGUAGE_LABEL_PREFIX . 'pleaseChoose.mandatory',
            'value' => '',
        ],
    ];
    public const array EMPTY_DEFAULT_ITEM_OPTIONAL_INT = [
        [
            'label' => self::LANGUAGE_LABEL_PREFIX . 'pleaseChoose.optional',
            'value' => 0,
        ],
    ];
    public const array EMPTY_DEFAULT_ITEM_OPTIONAL_STRING = [
        [
            'label' => self::LANGUAGE_LABEL_PREFIX . 'pleaseChoose.optional',
            'value' => '',
        ],
    ];
    private const string LANGUAGE_LABEL_PREFIX = 'LLL:EXT:foundation/Resources/Private/Language/Backend/Classes/Attribute/TCA/ColumnType/select.xlf:';

    protected ExtensionInformationService $extensionInformationService;

    /**
     * @param bool|null        $allowNonIdValues              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-allownonidvalues
     * @param string|null      $authMode                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-authmode
     * @param int|null         $autoSizeMax                   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-autosizemax
     * @param int|null         $dbFieldLength                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-dbfieldlength
     * @param bool|null        $disableNoMatchingValueElement https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-disablenomatchingvalueelement
     * @param string|null      $eval
     * @param array|null       $fieldControl                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-fieldcontrol
     * @param bool|null        $fieldControlDisableAddRecord
     * @param bool|null        $fieldControlDisableEditPopup
     * @param bool|null        $fieldControlDisableListModule
     * @param array|null       $fileFolderConfig              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-filefolderconfig
     * @param string|null      $foreignTable                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-foreign-table
     *                                                        Instead of directly specifying a foreign table, it is possible
     *                                                        to specify a domain model class via linkedModel.
     * @param string|null      $foreignTableItemGroup         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-foreign-table-item-group
     * @param string|null      $foreignTablePrefix            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-foreign-table-prefix
     * @param string|null      $foreignTableWhere             https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-foreign-table-where
     * @param array|null       $itemGroups                    https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-itemgroups
     * @param array|null       $items                         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-items
     * @param string|null      $itemsProcFunc                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/ItemsProcFunc.html
     * @param string           $linkedModel                   Instead of directly specifying a foreign table, it is possible
     *                                                        to specify a domain model class.
     * @param int|null         $maxItems                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-maxitems
     * @param int|null         $minItems                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-minitems
     * @param string|null      $mm                            https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/Mm.html#confval-mm
     * @param array|null       $mmMatchFields                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/Mm.html#confval-mm-match-fields
     * @param string|null      $mmOppositeField               https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/Mm.html#confval-mm-opposite-field
     * @param array|null       $mmOppositeUsage               https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-mm-oppositeusage
     * @param string|null      $mmTableWhere                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-mm-table-where
     * @param bool|null        $multiple                      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-multiple
     * @param array|null       $prependItem
     * @param Relationship     $relationship                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-relationship
     * @param SelectRenderType $renderType
     * @param int|null         $size                          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-single-size
     * @param string|null      $sortItems                     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Single/Index.html#confval-select-sortitems
     * @param array|null       $treeConfig                    https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig
     * @param string|null      $treeConfigChildrenField       https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-childrenfield
     *                                                        You can use the property name. It will be converted to the
     *                                                        column name automatically.
     * @param string|null      $treeConfigDataProvider        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-dataprovider
     * @param bool|null        $treeConfigExpandAll           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-expandall
     * @param int|null         $treeConfigMaxLevels           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-maxlevels
     * @param string|null      $treeConfigNonSelectableLevels https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-nonselectablelevels
     * @param string|null      $treeConfigParentField         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-parentfield
     *                                                        You can use the property name. It will be converted to the
     *                                                        column name automatically.
     * @param bool|null        $treeConfigShowHeader          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-showheader
     * @param array            $treeConfigStartingPoints      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Select/Tree/Index.html#confval-select-treeconfig-startingpoints
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(
        protected ?bool            $allowNonIdValues = null,
        protected ?string          $authMode = null,
        protected ?int             $autoSizeMax = null,
        protected ?int             $dbFieldLength = null,
        protected ?bool            $disableNoMatchingValueElement = null,
        protected ?string          $eval = null,
        protected ?array           $fieldControl = null,
        protected ?bool            $fieldControlDisableAddRecord = null,
        protected ?bool            $fieldControlDisableEditPopup = null,
        protected ?bool            $fieldControlDisableListModule = null,
        protected ?array           $fileFolderConfig = null,
        protected ?string          $foreignTable = null,
        protected ?string          $foreignTableItemGroup = null,
        protected ?string          $foreignTablePrefix = null,
        protected ?string          $foreignTableWhere = null,
        protected ?array           $itemGroups = null,
        protected ?array           $items = null,
        protected ?string          $itemsProcFunc = null,
        protected string           $linkedModel = '',
        protected ?int             $maxItems = null,
        protected ?int             $minItems = null,
        protected ?string          $mm = null,
        protected ?array           $mmMatchFields = null,
        protected ?string          $mmOppositeField = null,
        protected ?array           $mmOppositeUsage = null,
        protected ?string          $mmTableWhere = null,
        protected ?bool            $multiple = null,
        protected ?array           $prependItem = null,
        protected Relationship     $relationship = Relationship::manyToMany,
        protected SelectRenderType $renderType = SelectRenderType::selectSingle,
        protected ?int             $size = null,
        protected ?string          $sortItems = null,
        protected ?array           $treeConfig = null,
        protected ?string          $treeConfigChildrenField = null,
        protected ?string          $treeConfigDataProvider = null,
        protected ?bool            $treeConfigExpandAll = null,
        protected ?int             $treeConfigMaxLevels = null,
        protected ?string          $treeConfigNonSelectableLevels = null,
        protected ?string          $treeConfigParentField = null,
        protected ?bool            $treeConfigShowHeader = null,
        protected array            $treeConfigStartingPoints = [],
    ) {
        $this->extensionInformationService = GeneralUtility::makeInstance(ExtensionInformationService::class);

        if (class_exists($linkedModel)) {
            $this->foreignTable = $this->tcaService()->convertClassNameToTableName($linkedModel);
        }

        if (SelectRenderType::selectSingle === $renderType) {
            $this->autoSizeMax = $autoSizeMax ?? 1;
            $this->maxItems    = $maxItems ?? 1;
            $this->size        = $size ?? 1;
        }

        if (SelectRenderType::selectTree === $renderType) {
            $this->autoSizeMax = null;
            $this->size        = null;
        }

        if (!empty($mm)) {
            $this->autoSizeMax = $autoSizeMax ?? 30;
            $this->maxItems    = $maxItems ?? 0;
            $this->size        = $size ?? 10;
        }
    }

    public function getAutoSizeMax(): ?int
    {
        return $this->autoSizeMax;
    }

    public function getAuthMode(): ?string
    {
        return $this->authMode;
    }

    public function getDatabaseDefinition(): string
    {
        $hasFloatValues    = false;
        $hasNegativeValues = false;
        $hasStringValues   = false;
        $maxStringLength   = 0;

        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $value           = $item['value'];
                $maxStringLength = max($maxStringLength, strlen((string)$value));

                if (is_string($value)) {
                    $hasStringValues = true;
                }

                if (is_float($value)) {
                    $hasFloatValues = true;
                } elseif (is_int($value) && 0 > $value) {
                    $hasNegativeValues = true;
                }
            }
        }

        if ($hasStringValues) {
            if (1 < $this->maxItems || !empty($this->foreignTable) || !empty($this->linkedModel)) {
                $maxStringLength = 255;
            }

            return DefinitionUtility::varchar($maxStringLength);
        }

        if ($hasFloatValues) {
            return DefinitionUtility::decimal(11, 2);
        }

        return DefinitionUtility::int(unsigned: !$hasNegativeValues);
    }

    public function getDbFieldLength(): ?int
    {
        return $this->dbFieldLength;
    }

    public function getEval(): ?string
    {
        return $this->eval;
    }

    public function getFieldControl(): ?array
    {
        $fieldControl = $this->fieldControl;

        if (true === $this->fieldControlDisableAddRecord) {
            $fieldControl['addRecord']['disabled'] = true;
        }

        if (true === $this->fieldControlDisableEditPopup) {
            $fieldControl['editPopup']['disabled'] = true;
        }

        if (true === $this->fieldControlDisableListModule) {
            $fieldControl['listModule']['disabled'] = true;
        }

        return $fieldControl;
    }

    public function getFileFolderConfig(): ?array
    {
        return $this->fileFolderConfig;
    }

    public function getForeignTable(): ?string
    {
        return $this->foreignTable;
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

    public function getItems(): ?array
    {
        if (null === $this->items && null === $this->prependItem) {
            return null;
        }

        $this->items       = array_merge($this->prependItem ?? [], $this->items ?? []);
        $this->prependItem = [];

        return $this->items;
    }

    public function getItemsProcFunc(): ?string
    {
        return $this->itemsProcFunc;
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getMmOppositeField(): ?string
    {
        if (null === $this->mmOppositeField) {
            return null;
        }

        return $this->tcaService()->convertPropertyNameToColumnName($this->mmOppositeField);
    }

    public function getMmOppositeUsage(): ?array
    {
        return $this->mmOppositeUsage;
    }

    public function getMmTableWhere(): ?string
    {
        return $this->mmTableWhere;
    }

    public function getRelationship(): string
    {
        return $this->relationship->value;
    }

    public function getRenderType(): string
    {
        return $this->renderType->value;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getSortItems(): ?string
    {
        return $this->sortItems;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws MisconfiguredTcaException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getTreeConfig(): ?array
    {
        if (SelectRenderType::selectTree !== $this->renderType) {
            return null;
        }

        if (empty($this->treeConfigChildrenField) && empty($this->treeConfigParentField)) {
            throw new MisconfiguredTcaException(
                __CLASS__ . ': Either childrenField or parentField has to be set in treeConfig - childrenField takes precedence.',
                1682339361
            );
        }

        $configuration = [];

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
                $this->treeConfigChildrenField,
            );
        }

        if (null !== $this->treeConfigDataProvider) {
            $configuration['dataProvider'] = $this->treeConfigDataProvider;
        }

        if (null !== $this->treeConfigParentField) {
            $configuration['parentField'] = $this->tcaService()->convertPropertyNameToColumnName(
                $this->treeConfigParentField,
            );
        }

        if (!empty($this->treeConfigStartingPoints)) {
            $configuration['startingPoints'] = implode(', ', $this->treeConfigStartingPoints);
        }

        return $configuration;
    }

    public function isAllowNonIdValues(): ?bool
    {
        return $this->allowNonIdValues;
    }

    public function isDisableNoMatchingValueElement(): ?bool
    {
        return $this->disableNoMatchingValueElement;
    }

    public function isMultiple(): ?bool
    {
        return $this->multiple;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function processItems(string $labelPath = ''): void
    {
        if (!is_array($this->items)) {
            return;
        }

        // $items already has TCA format
        if (ArrayUtility::isMultiDimensionalArray($this->items)) {
            $this->processTcaFormat();

            return;
        }

        // $items has to be transformed into TCA format
        $this->processSimpleFormat($labelPath);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function processSimpleFormat(string $labelPath = ''): void
    {
        $selectItems = [];

        foreach ($this->items as $key => $value) {
            if (!is_string($key) && (is_string($value) || is_numeric($value))) {
                $label = (string)$value;
            } else {
                $label = (string)$key;
            }

            if (!empty($labelPath) && !str_starts_with($label, FilePathUtility::LANGUAGE_LABEL_PREFIX)) {
                $label = $labelPath . GeneralUtility::underscoredToLowerCamelCase($label);
            }

            if (str_starts_with($label, FilePathUtility::LANGUAGE_LABEL_PREFIX)) {
                LocalizationUtility::translationExists($label);
            }

            $selectItems[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        $this->items = $selectItems;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    private function processTcaFormat(): void
    {
        foreach ($this->items as $item) {
            $label = $item['label'] ?? '';

            if (str_starts_with($label, FilePathUtility::LANGUAGE_LABEL_PREFIX)) {
                LocalizationUtility::translationExists($label);
            }
        }
    }
}
