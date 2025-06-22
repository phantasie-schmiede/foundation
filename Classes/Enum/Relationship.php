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
 * Enum Relationship
 *
 * @package PSBits\Foundation\Enum
 */
enum Relationship: string
{
    case manyToMany = 'manyToMany';
    case oneToMany  = 'oneToMany';
    case oneToOne   = 'oneToOne';
}
