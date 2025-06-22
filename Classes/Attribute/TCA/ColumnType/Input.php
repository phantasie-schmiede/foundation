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
     * @param string     $eval        https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/Eval.html
     * @param int        $max         https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/Max.html
     * @param int|null   $min         https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/Min.html
     * @param int        $size        https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/Size.html
     * @param array|null $valuePicker https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Input/Properties/ValuePicker.html
     */
    public function __construct(
        protected string $eval = 'trim',
        protected int    $max = 255,
        protected ?int   $min = null,
        protected int    $size = 20,
        protected ?array $valuePicker = null,
    ) {
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::varchar($this->max);
    }

    public function getEval(): string
    {
        return $this->eval;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getValuePicker(): ?array
    {
        return $this->valuePicker;
    }
}
