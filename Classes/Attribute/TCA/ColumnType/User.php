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
 * Class User
 *
 * @link    https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/User/Index.html#properties-rendertype-default
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class User extends AbstractColumnType
{
    public function __construct(
        protected ?array $parameters = null,
        protected string $renderType = '',
    ) {
    }

    /**
     * Database definition has to be provided by extension author! Either in ext_tables.sql or the property
     * "databaseDefinition" of the attribute PSBits\Foundation\Attribute\TCA\Column.
     */
    public function getDatabaseDefinition(): string
    {
        return '';
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function getRenderType(): string
    {
        return $this->renderType;
    }
}
