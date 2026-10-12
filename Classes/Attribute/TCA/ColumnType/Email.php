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
 * Class Email
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Email extends AbstractColumnType
{
    /**
     * @param bool|null   $autocomplete https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Email/Index.html#confval-email-autocomplete
     * @param string      $eval         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Email/Index.html#confval-email-eval
     * @param string|null $placeholder  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Email/Index.html#confval-email-placeholder
     * @param int|null    $size         https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Email/Index.html#confval-email-size
     */
    public function __construct(
        protected ?bool   $autocomplete = null,
        protected string  $eval = 'trim',
        protected ?string $placeholder = null,
        protected ?int    $size = null,
    ) {
    }

    public function getAutocomplete(): ?bool
    {
        return $this->autocomplete;
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::varchar(255);
    }

    public function getEval(): string
    {
        return $this->eval;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }
}
