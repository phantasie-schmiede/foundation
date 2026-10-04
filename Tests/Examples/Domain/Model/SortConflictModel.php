<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples\Domain\Model;

use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class SortConflictModel
 *
 * Defines both sortBy and a non default defaultSortBy, which is a
 * misconfiguration TcaService has to reject.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foundation_sort_conflict')]
#[Ctrl(
    label: 'singleField',
    sortBy: 'singleField',
    defaultSortBy: 'other_field',
    delete: null,
    crdate: null,
    tstamp: null,
    enableColumns: null,
    iconFile: null,
    languageField: null,
    origUid: null,
    transOrigDiffSourceField: null,
    transOrigPointerField: null,
    translationSource: null,
)]
class SortConflictModel extends AbstractEntity
{
    #[Input]
    protected string $singleField = '';
}
