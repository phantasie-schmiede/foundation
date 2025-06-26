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
 * Enum ContentType
 *
 * @package PSBits\Foundation\Enum
 */
enum ContentType: string
{
    case HTML  = 'text/html';
    case JSON  = 'application/json';
    case PLAIN = 'text/plain';
    case RSS   = 'application/rss+xml';
    case XML   = 'text/xml';
}
