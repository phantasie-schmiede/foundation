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
use PSBits\Foundation\Enum\SelectRenderType;
use PSBits\Foundation\Exceptions\MisconfiguredTcaException;
use PSBits\Foundation\Service\Configuration\TcaService;
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
    public const EMPTY_DEFAULT_ITEM = [
        [
            'label' => 'LLL:EXT:foundation/Resources/Private/Language/Backend/Classes/Attribute/TCA/select.xlf:pleaseChoose',
            'value' => 0,
        ],
    ];

    protected ExtensionInformationService $extensionInformationService;
    protected TcaService                  $tcaService;

    /**
     * @param bool|null        $allowNonIdValues        https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/AllowNonIdValues.html
     * @param int|null         $autoSizeMax             https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/AutoSizeMax.html
     * @param string|null      $eval
     * @param array|null       $fieldControl            https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/FieldControl.html#tca-property-fieldcontrol
     * @param bool|null        $fieldControlDisableAddRecord
     * @param bool|null        $fieldControlDisableEditPopup
     * @param bool|null        $fieldControlDisableListModule
     * @param string|null      $foreignTable            https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/ForeignTable.html
     *                                                  Instead of directly specifying a foreign table, it is possible
     *                                                  to specify a domain model class via linkedModel.
     * @param string|null      $foreignTableWhere       https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/ForeignTableWhere.html
     * @param array|null       $items                   https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/Items.html
     * @param string|null      $itemsProcFunc           https://docs.typo3.org/m/typo3/reference-tca/11.5/en-us/ColumnsConfig/CommonProperties/ItemsProcFunc.html
     * @param string           $linkedModel             Instead of directly specifying a foreign table, it is possible
     *                                                  to specify a domain model class.
     * @param int|null         $maxItems                https://docs.typo3.org/m/typo3/reference-tca/12.4/en-us/ColumnsConfig/CommonProperties/Maxitems.html
     * @param int|null         $minItems                https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/Minitems.html#tca-property-minitems
     * @param string|null      $mm                      https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/Mm.html
     * @param bool|null        $mmHasUidField           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/Mm.html#confval-MM_hasUidField
     * @param array|null       $mmInsertFields          https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/Mm.html#confval-MM_insert_fields
     * @param array|null       $mmMatchFields           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/Mm.html#confval-MM_match_fields
     * @param string|null      $mmOppositeField         https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/Mm.html#confval-MM_opposite_field
     * @param bool|null        $multiple                https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/Multiple.html#tca-property-multiple
     * @param array|null       $prependItem
     * @param SelectRenderType $renderType
     * @param int|null         $size                    https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/Size.html#tca-property-size
     * @param array|null       $treeConfig              https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Select/Properties/SelectTreeConfig.html
     * @param string|null      $treeConfigChildrenField You can use the property name. It will be converted to the
     *                                                  column name automatically.
     * @param string|null      $treeConfigDataProvider
     * @param bool|null        $treeConfigExpandAll
     * @param int|null         $treeConfigMaxLevels
     * @param string|null      $treeConfigNonSelectableLevels
     * @param string|null      $treeConfigParentField   You can use the property name. It will be converted to the
     *                                                  column name automatically.
     * @param bool|null        $treeConfigShowHeader
     * @param array            $treeConfigStartingPoints
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(
        protected ?bool            $allowNonIdValues = null,
        protected ?int             $autoSizeMax = null,
        protected ?string          $eval = null,
        protected ?array           $fieldControl = null,
        protected ?bool            $fieldControlDisableAddRecord = null,
        protected ?bool            $fieldControlDisableEditPopup = null,
        protected ?bool            $fieldControlDisableListModule = null,
        protected ?string          $foreignTable = null,
        protected ?string          $foreignTableWhere = null,
        protected ?array           $items = null,
        protected ?string          $itemsProcFunc = null,
        protected string           $linkedModel = '',
        protected ?int             $maxItems = null,
        protected ?int             $minItems = null,
        protected ?string          $mm = null,
        protected ?bool            $mmHasUidField = null,
        protected ?array           $mmInsertFields = null,
        protected ?array           $mmMatchFields = null,
        protected ?string          $mmOppositeField = null,
        protected ?bool            $multiple = null,
        protected ?array           $prependItem = null,
        protected SelectRenderType $renderType = SelectRenderType::selectSingle,
        protected ?int             $size = null,
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
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);

        if (class_exists($linkedModel)) {
            $this->foreignTable = $this->tcaService->convertClassNameToTableName($linkedModel);
        }

        if (SelectRenderType::selectSingle === $renderType) {
            $this->autoSizeMax = $autoSizeMax ?? 1;
            $this->maxItems = $maxItems ?? 1;
            $this->size = $size ?? 1;
        }

        if (SelectRenderType::selectTree === $renderType) {
            $this->autoSizeMax = null;
            $this->size = null;
        }

        if (!empty($mm)) {
            $this->autoSizeMax = $autoSizeMax ?? 30;
            $this->maxItems = $maxItems ?? 0;
            $this->renderType = $renderType ?? SelectRenderType::selectMultipleSideBySide;
            $this->size = $size ?? 10;
        }
    }

    public function getAutoSizeMax(): ?int
    {
        return $this->autoSizeMax;
    }

    public function getDatabaseDefinition(): string
    {
        $hasFloatValues = false;
        $hasNegativeValues = false;
        $hasStringValues = false;
        $maxStringLength = 0;

        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $value = $item['value'];
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

    public function getForeignTable(): ?string
    {
        return $this->foreignTable;
    }

    public function getForeignTableWhere(): ?string
    {
        return $this->foreignTableWhere;
    }

    public function getItems(): ?array
    {
        if (null === $this->items && null === $this->prependItem) {
            return null;
        }

        $this->items = array_merge($this->prependItem ?? [], $this->items ?? []);
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

    public function getMmHasUidField(): ?bool
    {
        return $this->mmHasUidField;
    }

    public function getMmInsertFields(): ?array
    {
        return $this->mmInsertFields;
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

        return $this->tcaService->convertPropertyNameToColumnName($this->mmOppositeField);
    }

    public function getRenderType(): string
    {
        return $this->renderType->value;
    }

    public function getSize(): ?int
    {
        return $this->size;
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
            $configuration['childrenField'] = $this->tcaService->convertPropertyNameToColumnName(
                $this->treeConfigChildrenField,
            );
        }

        if (null !== $this->treeConfigDataProvider) {
            $configuration['dataProvider'] = $this->treeConfigDataProvider;
        }

        if (null !== $this->treeConfigParentField) {
            $configuration['parentField'] = $this->tcaService->convertPropertyNameToColumnName(
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
