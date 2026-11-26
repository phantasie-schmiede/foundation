<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

$baseLL = 'LLL:EXT:foundation/Resources/Private/Language/Backend/Configuration/TCA/allTcaAttributesModel.xlf:';

return [
    'columns'  => [
        'category_field'           => [
            'config' => [
                'EXT'                 => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'foreign_table_where' => 'AND deleted = 0',
                'itemGroups'          => [
                    [
                        'label' => 'Group A',
                        'items' => [
                            1,
                            2,
                        ],
                    ],
                ],
                'maxitems'            => 10,
                'minitems'            => 1,
                'relationship'        => 'manyToMany',
                'size'                => 5,
                'type'                => 'category',
            ],
            'label'  => $baseLL . 'categoryField',
        ],
        'check_field'              => [
            'config' => [
                'cols'               => 1,
                'EXT'                => [
                    'foundation' => [
                        'databaseDefinition' => 'tinyint unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'invertStateDisplay' => false,
                'items'              => [],
                'type'               => 'check',
            ],
            'label'  => $baseLL . 'checkField',
        ],
        'color_field'              => [
            'config' => [
                'EXT'         => [
                    'foundation' => [
                        'databaseDefinition' => 'char(7) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'mode'        => 'rgb',
                'opacity'     => '0.5',
                'placeholder' => '#000',
                'size'        => 20,
                'type'        => 'color',
                'valuePicker' => [
                    'items' => [],
                ],
            ],
            'label'  => $baseLL . 'colorField',
        ],
        'datetime_field'           => [
            'config' => [
                'dbType'      => 'datetime',
                'format'      => 'datetime',
                'placeholder' => 'Please pick a date',
                'type'        => 'datetime',
            ],
            'label'  => $baseLL . 'datetimeField',
        ],
        'email_field'              => [
            'config' => [
                'eval' => 'trim',
                'EXT'  => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(255) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'type' => 'email',
            ],
            'label'  => $baseLL . 'emailField',
        ],
        'enum_field'               => [
            'config' => [
                'EXT'        => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(7) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'items'      => [
                    [
                        'label' => 'delta',
                        'value' => 'delta',
                    ],
                    [
                        'label' => 'epsilon',
                        'value' => 'epsilon',
                    ],
                    [
                        'label' => 'zeta',
                        'value' => 'zeta',
                    ],
                ],
                'renderType' => 'selectSingle',
                'type'       => 'select',
            ],
            'label'  => $baseLL . 'enumField',
        ],
        'file_field'               => [
            'config' => [
                'allowed'      => 'common-image-types',
                'appearance'   => [
                    'showRecalculateLink' => false,
                ],
                'behaviour'    => [
                    'enableCascadingDelete' => true,
                ],
                'disallowed'   => 'jpg',
                'EXT'          => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'type'         => 'file',
            ],
            'label'  => $baseLL . 'fileField',
        ],
        'flex_field'               => [
            'config' => [
                'ds'   => [
                    'default' => 'FILE:EXT:core/Configuration/FlexForms/FlexForm.xml',
                ],
                'EXT'  => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'type' => 'flex',
            ],
            'label'  => $baseLL . 'flexField',
        ],
        'folder_field'             => [
            'config' => [
                'EXT'          => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'type'         => 'folder',
            ],
            'label'  => $baseLL . 'folderField',
        ],
        'group_field'              => [
            'config' => [
                'allowed'        => 'sys_category',
                'autoSizeMax'    => 5,
                'EXT'            => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'hideDeleteIcon' => true,
                'minitems'       => 1,
                'MM_table_where' => 'AND 1=1',
                'multiple'       => true,
                'prepend_tname'  => '1',
                'relationship'   => 'oneToMany',
                'size'           => 5,
                'type'           => 'group',
            ],
            'label'  => $baseLL . 'groupField',
        ],
        'image_manipulation_field' => [
            'config' => [
                'EXT'        => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'file_field' => 'file_field',
                'type'       => 'imagemanipulation',
            ],
            'label'  => $baseLL . 'imageManipulationField',
        ],
        'inline_field'             => [
            'config' => [
                'EXT'            => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'appearance'     => [
                    'collapseAll'                     => true,
                    'enabledControls'                 => [
                        'dragdrop' => true,
                    ],
                    'expandSingle'                    => true,
                    'levelLinksPosition'              => 'bottom',
                    'showAllLocalizationLink'         => true,
                    'showPossibleLocalizationRecords' => true,
                    'showSynchronizationLink'         => true,
                    'useSortable'                     => true,
                ],
                'behaviour'      => [
                    'disableMovingChildrenWithParent' => true,
                    'enableCascadingDelete'           => true,
                ],
                'customControls' => [
                    'preview',
                ],
                'foreign_label'  => 'title',
                'foreign_table'  => 'sys_category',
                'foreign_unique' => 'uid',
                'minitems'       => 1,
                'relationship'   => 'oneToMany',
                'size'           => 10,
                'type'           => 'inline',
            ],
            'label'  => $baseLL . 'inlineField',
        ],
        'json_field'               => [
            'config' => [
                'enableCodeEditor'  => true,
                'EXT'               => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'placeholder'       => '',
                'type'              => 'json',
            ],
            'label'  => $baseLL . 'jsonField',
        ],
        'link_field'               => [
            'config' => [
                'appearance'   => [
                    'enableBrowser' => true,
                ],
                'autocomplete' => false,
                'EXT'          => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'placeholder'  => 'Please enter URL',
                'size'         => 50,
                'type'         => 'link',
            ],
            'label'  => $baseLL . 'linkField',
        ],
        'mapped_field'             => [
            'config' => [
                'autocomplete' => 'off',
                'behaviour'    => [
                    'allowLanguageSynchronization' => true,
                ],
                'eval'         => 'trim',
                'EXT'          => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(255) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'fieldControl' => [
                    'addRecord' => [
                        'disabled' => true,
                    ],
                ],
                'max'          => 255,
                'placeholder'  => 'Please enter...',
                'size'         => 20,
                'type'         => 'input',
            ],
            'label'  => $baseLL . 'mappedField',
        ],
        'none_field'               => [
            'config' => [
                'EXT'  => [
                    'foundation' => [
                        'databaseDefinition' => 'int DEFAULT 0 NOT NULL',
                    ],
                ],
                'size' => 1,
                'type' => 'none',
            ],
            'label'  => $baseLL . 'noneField',
        ],
        'number_field'             => [
            'config' => [
                'EXT'    => [
                    'foundation' => [
                        'databaseDefinition' => 'int DEFAULT 0 NOT NULL',
                    ],
                ],
                'format' => 'integer',
                'size'   => 10,
                'type'   => 'number',
            ],
            'label'  => $baseLL . 'numberField',
        ],
        'password_field'           => [
            'config' => [
                'EXT'         => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(255) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'hashed'      => true,
                'placeholder' => '',
                'type'        => 'password',
            ],
            'label'  => $baseLL . 'passwordField',
        ],
        'pass_through_field'       => [
            'config' => [
                'EXT'  => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(64) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'type' => 'passthrough',
            ],
            'label'  => $baseLL . 'passThroughField',
        ],
        'radio_field'              => [
            'config' => [
                'EXT'   => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(255) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'items' => [
                    [
                        'label' => 'One',
                        'value' => 1,
                    ],
                ],
                'type'  => 'radio',
            ],
            'label'  => $baseLL . 'radioField',
        ],
        'select_field'             => [
            'config' => [
                'EXT'                           => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'authMode'                      => 'strict',
                'autoSizeMax'                   => 1,
                'dbFieldLength'                 => 255,
                'disableNoMatchingValueElement' => true,
                'items'                         => [
                    [
                        'label' => 'One',
                        'value' => 1,
                    ],
                    [
                        'label' => 'Two',
                        'value' => 2,
                    ],
                ],
                'maxitems'                      => 1,
                'renderType'                    => 'selectSingle',
                'size'                          => 1,
                'sortItems'                     => 'value ASC',
                'type'                          => 'select',
            ],
            'label'  => $baseLL . 'selectField',
        ],
        'slug_field'               => [
            'config' => [
                'appearance'        => [
                    'prefix' => 'test/',
                ],
                'eval'              => 'uniqueInSite',
                'fallbackCharacter' => '-',
                'generatorOptions'  => [
                    'fields'               => [
                        'mapped_field',
                    ],
                    'fieldSeparator'       => '/',
                    'prefixParentPageSlug' => true,
                    'postModifiers'        => [
                        [
                            'name'      => 'substr',
                            'arguments' => [
                                0,
                                1,
                            ],
                        ],
                    ],
                    'replacements'         => [
                        '/' => '',
                    ],
                ],
                'prependSlash'      => true,
                'type'              => 'slug',
            ],
            'label'  => $baseLL . 'slugField',
        ],
        'text_field'               => [
            'config' => [
                'cols'            => 32,
                'enableTabulator' => true,
                'eval'            => 'trim',
                'EXT'             => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'placeholder'     => 'Please write...',
                'rows'            => 5,
                'type'            => 'text',
            ],
            'label'  => $baseLL . 'textField',
        ],
        'user_field'               => [
            'config' => [
                'EXT'        => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(255) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'renderType' => 'testUserRenderType',
                'type'       => 'user',
            ],
            'label'  => $baseLL . 'userField',
        ],
        'uuid_field'               => [
            'config' => [
                'enableCopyToClipboard' => false,
                'EXT'                   => [
                    'foundation' => [
                        'databaseDefinition' => 'char(36) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'size'                  => 36,
                'type'                  => 'uuid',
                'version'               => 4,
            ],
            'label'  => $baseLL . 'uuidField',
        ],
    ],
    'ctrl'     => [
        'label'           => 'mapped_field',
        'previewRenderer' => 'PSBits\\Foundation\\Tests\\Examples\\Utility\\DummyPreviewRenderer',
        'searchFields'    => 'mapped_field, text_field',
        'title'           => $baseLL . 'ctrl.title',
        'type'            => 'record_type',
    ],
    'palettes' => [
        'hidden_palette'   => [
            'isHiddenPalette' => true,
            'label'           => 'HiddenPalette',
            'showitem'        => '',
        ],
        'labelled_palette' => [
            'label'    => 'PaletteLabel',
            'showitem' => 'check_field',
        ],
        'main_palette'     => [
            'showitem' => 'mapped_field',
        ],
    ],
    'types'    => [
        '0' => [
            'showitem' => 'category_field, --palette--;;labelled_palette, number_field, color_field, datetime_field, email_field, enum_field, file_field, flex_field, folder_field, group_field, image_manipulation_field, inline_field, json_field, link_field, --palette--;;main_palette, none_field, pass_through_field, password_field, radio_field, select_field, slug_field, --div--;Extra Tab, text_field, user_field, uuid_field',
        ],
        '1' => [
            'columnsOverrides' => [
                'text_field' => [
                    'config' => [
                        'max' => 100,
                    ],
                ],
            ],
            'creationOptions'  => [
                'defaultValues' => [
                    'hidden' => 1,
                ],
                'saveAndClose'  => true,
            ],
            'previewRenderer'  => 'PSBits\\Foundation\\Tests\\Examples\\Utility\\DummyPreviewRenderer',
            'showitem'         => 'category_field, --palette--;;labelled_palette, number_field, color_field, datetime_field, email_field, enum_field, file_field, flex_field, folder_field, group_field, image_manipulation_field, inline_field, json_field, link_field, --palette--;;main_palette, none_field, pass_through_field, password_field, radio_field, select_field, slug_field, --div--;Extra Tab, text_field, user_field, uuid_field',
        ],
    ],
];
