<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples\Domain\Model;

use PSBits\Foundation\Attribute\TCA\Mapping\Table;

/**
 * Class ForeignTableModel
 *
 * Example model mapped to a table of another extension, used to test the
 * TCA_OVERRIDES bucket of the class/table mapping.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foreign_thing')]
class ForeignTableModel
{
}
