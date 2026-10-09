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
 * Class Uuid
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Uuid extends AbstractColumnType
{
    /**
     * @param bool     $enableCopyToClipboard https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Uuid/Index.html#confval-uuid-enablecopytoclipboard
     * @param int      $size                  https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Uuid/Index.html#confval-uuid-size
     * @param int|null $version               https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/ColumnsConfig/Type/Uuid/Index.html#confval-uuid-version
     */
    public function __construct(
        protected bool $enableCopyToClipboard = false,
        protected int  $size = 36,
        protected ?int $version = null,
    ) {
    }

    public function getDatabaseDefinition(): string
    {
        return DefinitionUtility::char(36);
    }

    public function getEnableCopyToClipboard(): bool
    {
        return $this->enableCopyToClipboard;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }
}
