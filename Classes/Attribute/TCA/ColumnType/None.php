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

/**
 * Class None
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class None extends AbstractColumnType
{
    /**
     * @param string|array|null $format https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/None/Index.html#confval-none-format
     * @param int               $size   https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/None/Index.html#confval-none-size
     */
    public function __construct(
        protected string|array|null $format = null,
        protected int               $size = 1,
    ) {
    }

    /**
     * The type none is a virtual type.
     * Database definition has to be provided by extension author! Either in ext_tables.sql or the property
     * "databaseDefinition" of the attribute PSBits\Foundation\Attribute\TCA\Column.
     */
    public function getDatabaseDefinition(): string
    {
        return '';
    }

    public function getFormat(): string|array|null
    {
        return $this->format;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
