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
 * Class PositionLoopModel
 *
 * The position of both fields references the other field, which creates a
 * loop that TcaService must detect.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foundation_position_loop')]
#[Ctrl(
    label: 'a',
    delete: null,
    crdate: null,
    tstamp: null,
    defaultSortBy: null,
    enableColumns: null,
    iconFile: null,
    languageField: null,
    origUid: null,
    transOrigDiffSourceField: null,
    transOrigPointerField: null,
    translationSource: null,
)]
class PositionLoopModel extends AbstractEntity
{
    #[Column(position: 'after:b')]
    #[Input]
    protected string $a = '';

    #[Column(position: 'after:a')]
    #[Input]
    protected string $b = '';
}
