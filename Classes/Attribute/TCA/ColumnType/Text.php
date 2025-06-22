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
 * Class Text
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Text extends AbstractColumnType
{
    /**
     * @param int       $cols           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Text/Properties/Cols.html
     * @param bool|null $enableRichText https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Text/Properties/EnableRichtext.html
     * @param string    $eval           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Text/Properties/Eval.html
     * @param int       $rows           https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/Text/Properties/Rows.html
     */
    public function __construct(
        protected int    $cols = 32,
        protected ?bool  $enableRichText = null,
        protected string $eval = 'trim',
        protected int    $rows = 5,
    ) {
    }

    public function getCols(): int
    {
        return $this->cols;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::text();
    }

    public function getEval(): string
    {
        return $this->eval;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function isEnableRichText(): ?bool
    {
        return $this->enableRichText;
    }
}
