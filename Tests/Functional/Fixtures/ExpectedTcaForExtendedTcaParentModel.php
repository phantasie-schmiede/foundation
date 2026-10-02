<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

$baseLL = 'LLL:EXT:foundation/Resources/Private/Language/Backend/Configuration/TCA/extendedTcaParentModel.xlf:';

return [
    'columns'  => [
        'base_field'        => [
            'label'  => $baseLL . 'baseField',
            'config' => [
                'eval' => 'trim',
                'max'  => 255,
                'size' => 20,
                'type' => 'input',
                'EXT'  => [
                    'foundation' => [
                        'databaseDefinition' => 'varchar(255) DEFAULT \'\' NOT NULL',
                    ],
                ],
            ],
        ],
        'parent_text'       => [
            'label'  => $baseLL . 'parentText',
            'config' => [
                'cols' => 32,
                'eval' => 'trim',
                'rows' => 5,
                'type' => 'text',
                'EXT'  => [
                    'foundation' => [
                        'databaseDefinition' => 'text NOT NULL',
                    ],
                ],
            ],
        ],
        'parent_inline'     => [
            'label'  => $baseLL . 'parentInline',
            'config' => [
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
                'foreign_field' => 'parent_uid',
                'foreign_table' => 'sys_category',
                'type'          => 'inline',
                'EXT'           => [
                    'foundation' => [
                        'databaseDefinition' => 'int unsigned DEFAULT 0 NOT NULL',
                    ],
                ],
            ],
        ],
        'hidden'            => [
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'exclude' => true,
            'config'  => [
                'items'      => [
                    [
                        'label'              => '',
                        'invertStateDisplay' => true,
                    ],
                ],
                'renderType' => 'checkboxToggle',
                'type'       => 'check',
            ],
        ],
        'starttime'         => [
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'exclude' => true,
            'config'  => [
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'default'   => 0,
                'type'      => 'datetime',
            ],
        ],
        'endtime'           => [
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'exclude' => true,
            'config'  => [
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'default'   => 0,
                'range'     => [
                    'upper' => 2145916800,
                ],
                'type'      => 'datetime',
            ],
        ],
        'sys_language_uid'  => [
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'exclude' => true,
            'config'  => [
                'type' => 'language',
            ],
        ],
        'l10n_parent'       => [
            'label'       => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'config'      => [
                'default'               => 0,
                'foreign_table'         => 'tx_foundation_extended_tca',
                'foreign_table_where'   => 'AND {#tx_foundation_extended_tca}.{#pid}=###CURRENT_PID### AND {#tx_foundation_extended_tca}.{#sys_language_uid} IN (-1,0)',
                'items'                 => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'renderType'            => 'selectSingle',
                'type'                  => 'select',
            ],
        ],
        'l10n_diffsource'   => [
            'config' => [
                'default' => '',
                'type'    => 'passthrough',
            ],
        ],
        'l10n_source'       => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'ctrl'     => [
        'enablecolumns'            => [
            'disabled'  => 'hidden',
            'endtime'   => 'endtime',
            'starttime' => 'starttime',
        ],
        'label'                    => 'base_field',
        'languageField'            => 'sys_language_uid',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'transOrigPointerField'    => 'l10n_parent',
        'translationSource'        => 'l10n_source',
        'title'                    => $baseLL . 'ctrl.title',
    ],
    'palettes' => [
        'timeRestriction' => [
            'showitem' => 'starttime, endtime',
        ],
        'language'        => [
            'showitem' => 'sys_language_uid, l10n_parent',
        ],
    ],
    'types'    => [
        '0' => [
            'showitem' => 'base_field, parent_text, parent_inline, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, --palette--;;language, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, --palette--;;timeRestriction',
        ],
    ],
];
