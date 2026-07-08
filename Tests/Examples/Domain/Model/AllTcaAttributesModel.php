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
use PSBits\Foundation\Attribute\TCA\ColumnType\Category;
use PSBits\Foundation\Attribute\TCA\ColumnType\Check;
use PSBits\Foundation\Attribute\TCA\ColumnType\Color;
use PSBits\Foundation\Attribute\TCA\ColumnType\Datetime;
use PSBits\Foundation\Attribute\TCA\ColumnType\Enum;
use PSBits\Foundation\Attribute\TCA\ColumnType\File;
use PSBits\Foundation\Attribute\TCA\ColumnType\Group;
use PSBits\Foundation\Attribute\TCA\ColumnType\Inline;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Link;
use PSBits\Foundation\Attribute\TCA\ColumnType\Number;
use PSBits\Foundation\Attribute\TCA\ColumnType\PassThrough;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Slug;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use PSBits\Foundation\Attribute\TCA\ColumnType\User;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Field;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use PSBits\Foundation\Attribute\TCA\Palette;
use PSBits\Foundation\Attribute\TCA\Tab;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

#[Table('tx_foundation_all_tca_attributes')]
#[Ctrl(
    label: 'mappedField',
    searchFields: [
        'mappedField',
        'textField',
    ],
    defaultSortBy: null,
    delete: null,
    enableColumns: null,
    iconFile: null,
    languageField: null,
    origUid: null,
    transOrigDiffSourceField: null,
    transOrigPointerField: null,
    translationSource: null,
    tstamp: null,
    crdate: null,
)]
#[Palette(identifier: 'main_palette')]
#[Tab(identifier: 'extra_tab', label: 'Extra Tab')]
class AllTcaAttributesModel extends AbstractEntity
{
    #[Column(position: 'palette:main_palette')]
    #[Field('mapped_field')]
    #[Input]
    protected string $mappedField = '';

    #[Column(position: 'tab:extra_tab')]
    #[Text]
    protected string $textField = '';

    #[Number]
    protected int $numberField = 0;

    #[Check]
    protected bool $checkField = false;

    #[Select(items: [
        [
            'label' => 'One',
            'value' => 1,
        ],
        [
            'label' => 'Two',
            'value' => 2,
        ],
    ])]
    protected int $selectField = 0;

    #[Group(foreignTable: 'sys_category')]
    protected string $groupField = '';

    #[Inline(foreignTable: 'sys_category')]
    protected int $inlineField = 0;

    #[Category]
    protected int $categoryField = 0;

    #[File]
    protected int $fileField = 0;

    #[Datetime]
    protected ?\DateTime $datetimeField = null;

    #[Link]
    protected string $linkField = '';

    #[Slug(generatorOptions: [
        'fields' => [
            'mapped_field',
        ],
    ])]
    protected string $slugField = '';

    #[Color]
    protected string $colorField = '';

    #[Enum(AllTcaAttributesEnum::class)]
    protected string $enumField = '';

    #[Column(databaseDefinition: 'varchar(64) DEFAULT \'\'')]
    #[PassThrough]
    protected string $passThroughField = '';

    #[Column(databaseDefinition: 'varchar(255) DEFAULT \'\'')]
    #[User(renderType: 'testUserRenderType')]
    protected string $userField = '';
}

