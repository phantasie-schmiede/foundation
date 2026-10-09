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
 * Class Radio
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Radio extends AbstractColumnType implements ColumnTypeWithItemsInterface
{
    /**
     * @param array       $items         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Radio/Index.html#confval-radio-items
     * @param string|null $itemsProcFunc https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/CommonProperties/ItemsProcFunc.html
     */
    public function __construct(
        protected array   $items = [],
        protected ?string $itemsProcFunc = null,
    ) {
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::varchar(255);
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getItemsProcFunc(): ?string
    {
        return $this->itemsProcFunc;
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
        $items = [];

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

            $items[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        $this->items = $items;
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
            if (!empty($item['label']) && str_starts_with($item['label'], FilePathUtility::LANGUAGE_LABEL_PREFIX)) {
                LocalizationUtility::translationExists($item['label']);
            }
        }
    }
}
