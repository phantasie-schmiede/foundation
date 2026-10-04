<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples\Domain\Model;

use PSBits\Foundation\Attribute\TCA\ColumnType\Inline;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ExtendedTcaParentModel
 *
 * Parent model for ExtendedTcaChildModel. Uses most of the default Ctrl
 * values (enable columns, language fields) to exercise the dummy
 * configuration branches of TcaService.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foundation_extended_tca')]
#[Ctrl(
    label: 'baseField',
    delete: null,
    crdate: null,
    tstamp: null,
    defaultSortBy: null,
    iconFile: null,
    origUid: null,
)]
class ExtendedTcaParentModel extends AbstractEntity
{
    #[Input]
    protected string $baseField = '';

    #[Text]
    protected string $parentText = '';

    #[Inline(foreignTable: 'sys_category', foreignField: 'parentUid')]
    protected int $parentInline = 0;
}
