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
use DateMalformedStringException;
use Doctrine\DBAL\Exception as DBALException;
use Error;
use InvalidArgumentException;
use JsonException;
use PSBits\Foundation\Utility\ArrayUtility;
use PSBits\Foundation\Utility\Configuration\FilePathUtility;
use PSBits\Foundation\Utility\Database\DefinitionUtility;
use PSBits\Foundation\Utility\LocalizationUtility;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;

use function array_merge;
use function is_string;
use function str_starts_with;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Color extends AbstractColumnType implements ColumnTypeWithItemsInterface
{
    /**
     * @param array<int|string, mixed> $items       The items are not a TCA option by themselves, they are passed to valuePicker.
     *                                              https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-valuepicker
     * @param string|null              $mode        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-mode
     * @param string|null              $opacity     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-opacity
     * @param string|null              $placeholder https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-placeholder
     * @param int|null                 $size        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-size
     * @param array<string, mixed>     $valuePicker https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Color/Index.html#confval-color-valuepicker
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

    /**
     * @return array<string, mixed>
     */
    public function getValuePicker(): array
    {
        return array_merge($this->valuePicker, ['items' => $this->items]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DateMalformedStringException
     * @throws DBALException
     * @throws Error
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
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
     * @throws DateMalformedStringException
     * @throws DBALException
     * @throws Error
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
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
     * @throws DateMalformedStringException
     * @throws DBALException
     * @throws Error
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NoServerRequestGivenException // t3:14
     * @throws ReflectionException
     * @throws RuntimeException
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
