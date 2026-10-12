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
 * Class Password
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Password extends AbstractColumnType
{
    /**
     * @param bool|null $hashed      https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Password/Index.html#confval-password-hashed
     * @param string    $placeholder https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Password/Index.html#confval-password-placeholder
     * @param int|null  $size        https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Password/Index.html#confval-password-size
     */
    public function __construct(
        protected ?bool  $hashed = null,
        protected string $placeholder = '',
        protected ?int   $size = null,
    ) {
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::varchar(255);
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function isHashed(): ?bool
    {
        return $this->hashed;
    }
}
