<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples\Domain\Model;

use PSBits\Foundation\Attribute\TCA\Column;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Number;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;

/**
 * Class ExtendedTcaChildModel
 *
 * Child model extending ExtendedTcaParentModel. Used to test the TCA
 * override mode, which only adds properties declared in the child class.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foundation_extended_tca')]
#[Ctrl(label: 'overrideField')]
class ExtendedTcaChildModel extends ExtendedTcaParentModel
{
    #[Input]
    protected string $overrideField = '';

    #[Column(position: 'after:overrideField', required: true)]
    #[Number]
    protected int $statusField = 0;
}
