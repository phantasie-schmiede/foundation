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
use PSBits\Foundation\Enum\NumberFormat;
use PSBits\Foundation\Utility\Database\DefinitionUtility;

/**
 * Class Number
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Number extends AbstractColumnType
{
    /**
     * @param bool|null    $autocomplete https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Number/Properties/Autocomplete.html
     * @param NumberFormat $format       https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Number/Properties/Format.html
     * @param int          $precision    used internally for database definition only (when format=decimal)
     * @param int|null     $rangeLower   https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/Range.html
     * @param int|null     $rangeUpper   https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/Range.html
     * @param int          $scale        used internally for database definition only (when format=decimal)
     * @param int|null     $sliderStep   https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/Slider.html
     * @param int|null     $sliderWidth  https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/Slider.html
     * @param array|null   $valuePicker  https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Number/Properties/ValuePicker.html
     */
    public function __construct(
        protected ?bool        $autocomplete = null,
        protected NumberFormat $format = NumberFormat::integer,
        protected int          $precision = 11,
        protected ?int         $rangeLower = null,
        protected ?int         $rangeUpper = null,
        protected int          $scale = 2,
        protected ?int         $sliderStep = null,
        protected ?int         $sliderWidth = null,
        protected ?array       $valuePicker = null,
    ) {
    }

    public function getAutocomplete(): ?bool
    {
        return $this->autocomplete;
    }

    public function getDatabaseDefinition(): string
    {
        if (NumberFormat::decimal === $this->format) {
            return DefinitionUtility::decimal($this->precision, $this->scale);
        }

        $unsigned = NumberFormat::integer === $this->format && null !== $this->rangeLower && 0 <= $this->rangeLower;

        return DefinitionUtility::int(unsigned: $unsigned);
    }

    public function getFormat(): string
    {
        return $this->format->value;
    }

    public function getRange(): ?array
    {
        $range = null;

        if (null !== $this->rangeLower) {
            $range['lower'] = $this->rangeLower;
        }

        if (null !== $this->rangeUpper) {
            $range['upper'] = $this->rangeUpper;
        }

        return $range;
    }

    public function getSlider(): ?array
    {
        $sliderConfiguration = null;

        if (null !== $this->sliderStep) {
            $sliderConfiguration['step'] = $this->sliderStep;
        }

        if (null !== $this->sliderWidth) {
            $sliderConfiguration['width'] = $this->sliderWidth;
        }

        return $sliderConfiguration;
    }

    public function getValuePicker(): ?array
    {
        if (null === $this->valuePicker) {
            return null;
        }

        return ['items' => $this->valuePicker];
    }
}
