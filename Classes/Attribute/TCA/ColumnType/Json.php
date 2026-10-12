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
 * Class Json
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Json extends AbstractColumnType
{
    /**
     * @param int|null $cols             https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Json/Index.html#confval-json-cols
     * @param bool     $enableCodeEditor https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Json/Index.html#confval-json-enablecodeeditor
     * @param string   $placeholder      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Json/Index.html#confval-json-placeholder
     * @param int|null $rows             https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Json/Index.html#confval-json-rows
     */
    public function __construct(
        protected ?int   $cols = null,
        protected bool   $enableCodeEditor = false,
        protected string $placeholder = '',
        protected ?int   $rows = null,
    ) {
    }

    public function getCols(): ?int
    {
        return $this->cols;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::text();
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getRows(): ?int
    {
        return $this->rows;
    }

    public function isEnableCodeEditor(): bool
    {
        return $this->enableCodeEditor;
    }
}
