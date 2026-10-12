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
use PSBits\Foundation\Utility\ArrayUtility;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\Database\DefinitionUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Color
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Color extends AbstractColumnType implements ColumnTypeWithItemsInterface
{
    /**
     * @param array       $items       The items are not a TCA option by themselves, they are passed to valuePicker.
     *                                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-valuepicker
     * @param string|null $mode        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-mode
     * @param string|null $opacity     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-opacity
     * @param string|null $placeholder https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-placeholder
     * @param int|null    $size        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-size
     * @param array       $valuePicker https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-valuepicker
     */
    public function __construct(
        protected array   $items = [],
        protected ?string $mode = null,
        protected ?string $opacity = null,
        protected ?string $placeholder = null,
        protected ?int    $size = null,
        protected array   $valuePicker = [],
    ) {
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::char(7);
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function getOpacity(): ?string
    {
        return $this->opacity;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getValuePicker(): array
    {
        return array_merge($this->valuePicker, ['items' => $this->items]);
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
        // $items already has TCA format
        if (ArrayUtility::isMultiDimensionalArray($this->items)) {
            $this->processTcaFormat();
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
                $label,
                $value,
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
            $label = $item[0];

            if (str_starts_with($label, FilePathUtility::LANGUAGE_LABEL_PREFIX)) {
                LocalizationUtility::translationExists($label);
            }
        }
    }
}
