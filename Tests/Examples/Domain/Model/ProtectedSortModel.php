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
 * Class ProtectedSortModel
 *
 * Sorts on a reserved system column, which is a misconfiguration TcaService
 * has to reject.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foundation_protected_sort')]
#[Ctrl(
    label: 'singleField',
    sortBy: 'uid',
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
class ProtectedSortModel extends AbstractEntity
{
    #[Input]
    protected string $singleField = '';
}
