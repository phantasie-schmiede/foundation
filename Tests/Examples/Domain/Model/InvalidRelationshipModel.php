<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Tests\Examples\Domain\Model;

use PSBits\Foundation\Attribute\TCA\ColumnType\File;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use PSBits\Foundation\Enum\Relationship;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class InvalidRelationshipModel
 *
 * Defines a file column with the "manyToMany" relationship, which the file
 * column type does not support. The TCA validation has to reject this
 * misconfiguration.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foundation_invalid_relationship')]
#[Ctrl(
    coreFields: 'none',
    delete: null,
    iconFile: null,
    label: 'title',
    origUid: null,
)]
class InvalidRelationshipModel extends AbstractEntity
{
    #[Input]
    protected string $title = '';

    #[File(relationship: Relationship::manyToMany)]
    protected int $documents = 0;
}
