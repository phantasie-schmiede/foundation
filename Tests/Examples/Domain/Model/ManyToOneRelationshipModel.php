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
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use PSBits\Foundation\Enum\Relationship;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ManyToOneRelationshipModel
 *
 * Defines a select column with the "manyToOne" relationship, which is a
 * valid combination for the select column type.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foundation_many_to_one_relationship')]
#[Ctrl(
    coreFields: 'none',
    delete: null,
    iconFile: null,
    label: 'title',
    origUid: null,
)]
class ManyToOneRelationshipModel extends AbstractEntity
{
    #[Input]
    protected string $title = '';

    #[Select(foreignTable: 'sys_category', relationship: Relationship::manyToOne)]
    protected int $company = 0;
}
