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
 * Class Input
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Input extends AbstractColumnType
{
    /**
     * @param string|null $autocomplete https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-autocomplete
     * @param string      $eval         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-eval
     * @param bool        $isIn         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-is-in
     * @param int         $max          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-max
     * @param int|null    $min          https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-min
     * @param string|null $placeholder  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-placeholder
     * @param int         $size         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-size
     * @param string|null $softref      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-softref
     * @param array|null  $valuePicker  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Input/Index.html#confval-input-valuepicker
     */
    public function __construct(
        protected ?string $autocomplete = null,
        protected string  $eval = 'trim',
        protected ?bool   $isIn = null,
        protected int     $max = 255,
        protected ?int    $min = null,
        protected ?string $placeholder = null,
        protected int     $size = 20,
        protected ?string $softref = null,
        protected ?array  $valuePicker = null,
    ) {
    }

    public function getAutocomplete(): ?string
    {
        return $this->autocomplete;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::varchar($this->max);
    }

    public function getEval(): string
    {
        return $this->eval;
    }

    public function getIsIn(): ?bool
    {
        return $this->isIn;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getSoftref(): ?string
    {
        return $this->softref;
    }

    public function getValuePicker(): ?array
    {
        return $this->valuePicker;
    }
}
