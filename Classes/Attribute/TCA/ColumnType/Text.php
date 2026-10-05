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
     * @param int         $cols                https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-cols
     * @param bool|null   $enableRichText      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-enablerichtext
     * @param bool|null   $enableTabulator     https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-enabletabulator
     * @param string      $eval                https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-eval
     * @param bool|null   $fixedFont           https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-fixedfont
     * @param bool|null   $isIn                https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-is-in
     * @param int|null    $max                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-max
     * @param int|null    $min                 https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-min
     * @param string|null $placeholder         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-placeholder
     * @param array|null  $richtextConfiguration https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-richtextconfiguration
     * @param int         $rows                https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-rows
     * @param string|null $wrap                https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Text/Default/Index.html#confval-text-wrap
     */
    public function __construct(
        protected int    $cols = 32,
        protected ?bool  $enableRichText = null,
        protected ?bool  $enableTabulator = null,
        protected string $eval = 'trim',
        protected ?bool  $fixedFont = null,
        protected ?bool  $isIn = null,
        protected ?int   $max = null,
        protected ?int   $min = null,
        protected ?string $placeholder = null,
        protected ?array $richtextConfiguration = null,
        protected int    $rows = 5,
        protected ?string $wrap = null,
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

    public function getEnableTabulator(): ?bool
    {
        return $this->enableTabulator;
    }

    public function getEval(): string
    {
        return $this->eval;
    }

    public function getFixedFont(): ?bool
    {
        return $this->fixedFont;
    }

    public function getIsIn(): ?bool
    {
        return $this->isIn;
    }

    public function getMax(): ?int
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

    public function getRichtextConfiguration(): ?array
    {
        return $this->richtextConfiguration;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function getWrap(): ?string
    {
        return $this->wrap;
    }

    public function isEnableRichText(): ?bool
    {
        return $this->enableRichText;
    }
}
