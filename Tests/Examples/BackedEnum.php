<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples;

/**
 * Class BackedEnum
 *
 * @package PSBits\Foundation\Tests\Examples
 */
enum BackedEnum: string
{
    case Delta   = 'delta';
    case Epsilon = 'epsilon';
    case Zeta    = 'zeta';
}
