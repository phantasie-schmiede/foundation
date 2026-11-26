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
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A domain model whose fields reference the default fields (language, access, hidden, timeRestriction) in their
 * position specifications.
 */
#[Table('tx_foundation_core_field_position')]
#[Ctrl]
class CoreFieldPositionModel extends AbstractEntity
{
    #[Column(position: 'after:hidden')]
    #[Input]
    protected string $afterHidden = '';

    #[Column(position: 'after:language')]
    #[Input]
    protected string $afterLanguage = '';

    #[Column(position: 'before:access')]
    #[Input]
    protected string $beforeAccess = '';

    #[Input]
    protected string $name = '';

    #[Column(position: 'newLineBefore:timeRestriction')]
    #[Input]
    protected string $newLineTimeRestriction = '';
}
