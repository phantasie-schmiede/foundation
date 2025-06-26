<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Enum;

/**
 * Enum NumberFormat
 *
 * @package PSBits\Foundation\Enum
 */
enum NumberFormat: string
{
    case decimal = 'decimal';
    case integer = 'integer';
}
