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
 * Class Flex
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Flex extends AbstractColumnType
{
    /**
     * @param array|null $ds             https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Flex/Index.html#confval-flex-ds
     * @param string     $dsPointerField https://docs.typo3.org/m/typo3/reference-tca/13.4/en-us/ColumnsConfig/Type/Flex/Index.html#confval-flex-ds-pointerfield
     *                                   Removed in TYPO3 v14: the pointer field functionality of TCA flex was
     *                                   removed (Breaking-107047). The option is kept for TYPO3 v13 support.
     */
    public function __construct(
        protected ?array $ds = null,
        protected string $dsPointerField = '',
    ) {
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::text();
    }

    public function getDs(): ?array
    {
        return $this->ds;
    }

    public function getDsPointerField(): ?string
    {
        return $this->dsPointerField ?: null;
    }
}
