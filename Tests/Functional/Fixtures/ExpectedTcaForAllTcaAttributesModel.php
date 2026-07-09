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
        'category_field'     => [
            'config' => [
                'EXT'          => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'relationship' => 'manyToMany',
                'type'         => 'category',
            ],
            'label'  => $baseLL . 'categoryField',
        ],
        'check_field'        => [
            'config' => [
                'EXT'                => [
                    'foundation' => [
                        'databaseDefinition' => 'tinyint unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'cols'               => 1,
                'invertStateDisplay' => false,
                'items'              => [],
                'type'               => 'check',
            ],
            'label'  => $baseLL . 'checkField',
        ],
        'color_field'        => [
            'config' => [
                'EXT'         => [
                    'foundation' => [
                        'databaseDefinition' => 'char(7) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'type'        => 'color',
                'valuePicker' => [
                    'items' => [],
                ],
            ],
            'label'  => $baseLL . 'colorField',
        ],
        'datetime_field'     => [
            'config' => [
                'dbType' => 'datetime',
                'format' => 'datetime',
                'type'   => 'datetime',
            ],
            'label'  => $baseLL . 'datetimeField',
        ],
        'enum_field'         => [
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
        'file_field'         => [
            'config' => [
                'EXT'     => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'allowed' => 'common-image-types',
                'type'    => 'file',
            ],
            'label'  => $baseLL . 'fileField',
        ],
        'group_field'        => [
            'config' => [
                'EXT'           => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'foreign_table' => 'sys_category',
                'type'          => 'group',
            ],
            'label'  => $baseLL . 'groupField',
        ],
        'inline_field'       => [
            'config' => [
                'EXT'           => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'appearance'    => [
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
                'foreign_table' => 'sys_category',
                'type'          => 'inline',
            ],
            'label'  => $baseLL . 'inlineField',
        ],
        'link_field'         => [
            'config' => [
                'EXT'          => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'autocomplete' => false,
                'type'         => 'link',
            ],
            'label'  => $baseLL . 'linkField',
        ],
        'mapped_field'       => [
            'config' => [
                'EXT'  => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(255) DEFAULT \'\' NOT NULL',
                    ],
                ],
                'eval' => 'trim',
                'max'  => 255,
                'size' => 20,
                'type' => 'input',
            ],
            'label'  => $baseLL . 'mappedField',
        ],
        'number_field'       => [
            'config' => [
                'EXT'    => [
                    'foundation' => [
                        'databaseDefinition' => 'int DEFAULT 0 NOT NULL',
                    ],
                ],
                'format' => 'integer',
                'type'   => 'number',
            ],
            'label'  => $baseLL . 'numberField',
        ],
        'pass_through_field' => [
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
        'select_field'       => [
            'config' => [
                'EXT'         => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
                'autoSizeMax' => 1,
                'items'       => [
                    [
                        'label' => 'One',
                        'value' => 1,
                    ],
                    [
                        'label' => 'Two',
                        'value' => 2,
                    ],
                ],
                'maxitems'    => 1,
                'renderType'  => 'selectSingle',
                'size'        => 1,
                'type'        => 'select',
            ],
            'label'  => $baseLL . 'selectField',
        ],
        'slug_field'         => [
            'config' => [
                'eval'              => 'uniqueInSite',
                'fallbackCharacter' => '-',
                'generatorOptions'  => [
                    'fields' => [
                        'mapped_field',
                    ],
                ],
                'type'              => 'slug',
            ],
            'label'  => $baseLL . 'slugField',
        ],
        'text_field'         => [
            'config' => [
                'EXT'  => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
                'cols' => 32,
                'eval' => 'trim',
                'rows' => 5,
                'type' => 'text',
            ],
            'label'  => $baseLL . 'textField',
        ],
        'user_field'         => [
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
    ],
    'ctrl'     => [
        'label'        => 'mapped_field',
        'searchFields' => 'mapped_field, text_field',
        'title'        => $baseLL . 'ctrl.title',
    ],
    'palettes' => [
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
            'showitem' => '--palette--;;main_palette, --div--;Extra Tab, text_field, --palette--;;labelled_palette, number_field, select_field, group_field, inline_field, category_field, file_field, datetime_field, link_field, slug_field, color_field, enum_field, pass_through_field, user_field',
        ],
    ],
];
