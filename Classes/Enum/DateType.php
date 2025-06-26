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
 * Enum DateType
 *
 * @package PSBits\Foundation\Enum
 */
enum DateType: string
{
    case date     = 'date';
    case datetime = 'datetime';
    case time     = 'time';
}
