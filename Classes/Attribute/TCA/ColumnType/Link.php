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
use PSBits\Foundation\Utility\Database\DefinitionUtility;

/**
 * Class Link
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Link extends AbstractColumnType
{
    /**
     * @param array|null $allowedTypes https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Link/Index.html#confval-link-allowedtypes
     * @param array|null $appearance   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Link/Index.html#confval-link-appearance
     * @param bool       $autocomplete https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Link/Index.html#confval-link-autocomplete
     * @param string     $placeholder  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Link/Index.html#confval-link-placeholder
     * @param int        $size         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Link/Index.html#confval-link-size
     * @param array|null $valuePicker  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Link/Index.html#confval-link-valuepicker
     */
    public function __construct(
        protected ?array $allowedTypes = null,
        protected ?array $appearance = null,
        protected bool   $autocomplete = false,
        protected ?string $placeholder = null,
        protected ?int   $size = null,
        protected ?array $valuePicker = null,
    ) {
    }

    public function getAllowedTypes(): ?array
    {
        return $this->allowedTypes;
    }

    public function getAppearance(): ?array
    {
        return $this->appearance;
    }

    public function getAutocomplete(): bool
    {
        return $this->autocomplete;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::text();
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getValuePicker(): ?array
    {
        if (null === $this->valuePicker) {
            return null;
        }

        return ['items' => $this->valuePicker];
    }
}
