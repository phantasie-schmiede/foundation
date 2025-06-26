<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Attribute\TCA\ColumnType;

/**
 * Interface ColumnTypeInterface
 *
 * @package PSBits\Foundation\Attribute\TCA\ColumnType
 */
interface ColumnTypeInterface
{
    public function getDatabaseDefinition(): string;

    public function getType(): string;

    public function toArray(): array;
}
