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
use PSBits\Foundation\Attribute\TCA\Palette;
use PSBits\Foundation\Attribute\TCA\Tab;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class PositionReferenceModel
 *
 * A domain model whose fields reference a user-defined palette and tab in their position specifications.
 *
 * @package PSBits\Foundation\Tests\Examples\Domain\Model
 */
#[Table('tx_foundation_position_reference')]
#[Ctrl(coreFields: 'none', delete: null, iconFile: null, origUid: null)]
#[Palette(identifier: 'ref_palette')]
#[Tab(identifier: 'ref_tab', label: 'Ref Tab')]
class PositionReferenceModel extends AbstractEntity
{
    #[Column(position: 'before:ref_palette')]
    #[Input]
    protected string $beforePalette = '';

    #[Column(position: 'palette:ref_palette')]
    #[Input]
    protected string $inPalette = '';

    #[Column(position: 'after:ref_palette')]
    #[Input]
    protected string $afterPalette = '';

    #[Column(position: 'tab:ref_tab')]
    #[Input]
    protected string $inTab = '';

    #[Column(position: 'after:ref_tab')]
    #[Input]
    protected string $afterTab = '';
}
