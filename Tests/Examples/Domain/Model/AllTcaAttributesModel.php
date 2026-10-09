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
use PSBits\Foundation\Attribute\TCA\ColumnType\Email;
use PSBits\Foundation\Attribute\TCA\ColumnType\Enum;
use PSBits\Foundation\Attribute\TCA\ColumnType\File;
use PSBits\Foundation\Attribute\TCA\ColumnType\Flex;
use PSBits\Foundation\Attribute\TCA\ColumnType\Folder;
use PSBits\Foundation\Attribute\TCA\ColumnType\Group;
use PSBits\Foundation\Attribute\TCA\ColumnType\ImageManipulation;
use PSBits\Foundation\Attribute\TCA\ColumnType\Inline;
use PSBits\Foundation\Attribute\TCA\ColumnType\Input;
use PSBits\Foundation\Attribute\TCA\ColumnType\Json;
use PSBits\Foundation\Attribute\TCA\ColumnType\Link;
use PSBits\Foundation\Attribute\TCA\ColumnType\None;
use PSBits\Foundation\Attribute\TCA\ColumnType\Number;
use PSBits\Foundation\Attribute\TCA\ColumnType\PassThrough;
use PSBits\Foundation\Attribute\TCA\ColumnType\Password;
use PSBits\Foundation\Attribute\TCA\ColumnType\Radio;
use PSBits\Foundation\Attribute\TCA\ColumnType\Select;
use PSBits\Foundation\Attribute\TCA\ColumnType\Slug;
use PSBits\Foundation\Attribute\TCA\ColumnType\Text;
use PSBits\Foundation\Attribute\TCA\ColumnType\User;
use PSBits\Foundation\Attribute\TCA\ColumnType\Uuid;
use PSBits\Foundation\Attribute\TCA\Ctrl;
use PSBits\Foundation\Attribute\TCA\Mapping\Field;
use PSBits\Foundation\Attribute\TCA\Mapping\Table;
use PSBits\Foundation\Attribute\TCA\Palette;
use PSBits\Foundation\Attribute\TCA\Tab;
use PSBits\Foundation\Attribute\TCA\Type;
use PSBits\Foundation\Enum\Relationship;
use PSBits\Foundation\Tests\Examples\BackedEnum;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

#[Table('tx_foundation_all_tca_attributes')]
#[Ctrl(
    coreFields: 'none',
    defaultSortBy: null,
    delete: null,
    iconFile: null,
    label: 'mappedField',
    origUid: null,
    previewRenderer: 'PSBits\\Foundation\\Tests\\Examples\\Utility\\DummyPreviewRenderer',
    searchFields: [
        'mappedField',
        'textField',
    ],
    type: 'record_type',
)]
#[Palette(identifier: 'hidden_palette', isHiddenPalette: true, label: 'HiddenPalette')]
#[Palette(identifier: 'labelled_palette', label: 'PaletteLabel')]
#[Palette(identifier: 'main_palette')]
#[Tab(identifier: 'extra_tab', label: 'Extra Tab')]
#[Type(recordType: 0, showitem: '')]
#[Type(
    columnsOverrides: [
        'text_field' => [
            'config' => [
                'max' => 100,
            ],
        ],
    ],
    creationOptions: [
        'defaultValues' => [
            'raw_value' => 'raw',
        ],
    ],
    defaultValues: [
        'hidden' => 1,
    ],
    previewRenderer: 'PSBits\\Foundation\\Tests\\Examples\\Utility\\DummyPreviewRenderer',
    recordType: 1,
    saveAndClose: true,
)]
class AllTcaAttributesModel extends AbstractEntity
{
    #[Column(
        allowLanguageSynchronization: true,
        fieldControl: [
            'addRecord' => [
                'disabled' => true,
            ],
        ],
        position: 'palette:main_palette',
    )]
    #[Field('mapped_field')]
    #[Input(autocomplete: 'off', placeholder: 'Please enter...')]
    protected string $mappedField = '';

    #[Column(position: 'tab:extra_tab')]
    #[Text(enableTabulator: true, placeholder: 'Please write...')]
    protected string $textField = '';

    #[Column(position: 'after:checkField')]
    #[Number(size: 10)]
    protected int $numberField = 0;

    #[Check]
    #[Column(position: 'palette:labelled_palette')]
    protected bool $checkField = false;

    #[Select(
        authMode: 'strict',
        dbFieldLength: 255,
        disableNoMatchingValueElement: true,
        items: [
            [
                'label' => 'One',
                'value' => 1,
            ],
            [
                'label' => 'Two',
                'value' => 2,
            ],
        ],
        sortItems: 'value ASC',
    )]
    protected int $selectField = 0;

    #[Group(
        allowed: 'sys_category',
        autoSizeMax: 5,
        hideDeleteIcon: true,
        minItems: 1,
        mmTableWhere: 'AND 1=1',
        multiple: true,
        prependTname: '1',
        relationship: Relationship::oneToMany,
        size: 5,
    )]
    protected string $groupField = '';

    #[Inline(
        customControls: [
            'preview',
        ],
        disableMovingChildrenWithParent: true,
        enableCascadingDelete: true,
        foreignLabel: 'title',
        foreignTable: 'sys_category',
        foreignUnique: 'uid',
        minItems: 1,
        size: 10,
    )]
    protected int $inlineField = 0;

    #[Category(
        foreignTableWhere: 'AND deleted = 0',
        itemGroups: [
            [
                'label' => 'Group A',
                'items' => [
                    1,
                    2,
                ],
            ],
        ],
        maxItems: 10,
        minItems: 1,
        size: 5,
    )]
    protected int $categoryField = 0;

    #[File(
        appearance: [
            'showRecalculateLink' => false,
        ],
        disallowed: 'jpg',
        enableCascadingDelete: true,
    )]
    protected int $fileField = 0;

    #[Datetime(placeholder: 'Please pick a date')]
    protected ?\DateTime $datetimeField = null;

    #[Link(
        appearance: [
            'enableBrowser' => true,
        ],
        placeholder: 'Please enter URL',
        size: 50,
    )]
    protected string $linkField = '';

    #[Slug(
        appearance: [
            'prefix' => 'test/',
        ],
        fields: [
            'mapped_field',
        ],
        fieldSeparator: '/',
        prefixParentPageSlug: true,
        postModifiers: [
            [
                'name'      => 'substr',
                'arguments' => [
                    0,
                    1,
                ],
            ],
        ],
        prependSlash: true,
        replacements: [
            '/' => '',
        ],
    )]
    protected string $slugField = '';

    #[Color(mode: 'rgb', opacity: '0.5', placeholder: '#000', size: 20)]
    protected string $colorField = '';

    #[Enum(BackedEnum::class)]
    protected string $enumField = '';

    #[Column(databaseDefinition: 'varchar(64) DEFAULT \'\'')]
    #[PassThrough]
    protected string $passThroughField = '';

    #[Column(databaseDefinition: 'varchar(255) DEFAULT \'\'')]
    #[User(renderType: 'testUserRenderType')]
    protected string $userField = '';

    #[Email]
    protected string $emailField = '';

    #[Json(enableCodeEditor: true)]
    protected string $jsonField = '';

    #[Radio(items: [
        [
            'label' => 'One',
            'value' => 1,
        ],
    ])]
    protected string $radioField = '';

    #[Password(hashed: true)]
    protected string $passwordField = '';

    #[Uuid(version: 4)]
    protected string $uuidField = '';

    #[Column(databaseDefinition: 'int DEFAULT 0 NOT NULL')]
    #[None]
    protected int $noneField = 0;

    #[Folder]
    protected string $folderField = '';

    #[Flex(ds: [
        'default' => 'FILE:EXT:core/Configuration/FlexForms/FlexForm.xml',
    ])]
    protected string $flexField = '';

    #[ImageManipulation(fileField: 'fileField')]
    protected int $imageManipulationField = 0;
}
