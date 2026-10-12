<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

$baseLL = 'LLL:EXT:foundation/Resources/Private/Language/Backend/Configuration/TCA/coreFieldPositionModel.xlf:';

$inputConfiguration = [
    'eval' => 'trim',
    'max'  => 255,
    'size' => 20,
    'type' => 'input',
    'EXT'  => [
        'foundation' => [
            'databaseDefinition' => 'varchar(255) DEFAULT \'\' NOT NULL',
        ],
    ],
];

return [
    'columns'  => [
        'after_hidden'                => [
            'label'  => $baseLL . 'afterHidden',
            'config' => $inputConfiguration,
        ],
        'after_language'              => [
            'label'  => $baseLL . 'afterLanguage',
            'config' => $inputConfiguration,
        ],
        'before_access'               => [
            'label'  => $baseLL . 'beforeAccess',
            'config' => $inputConfiguration,
        ],
        'endtime'                     => [
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
        'hidden'                      => [
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
        'l10n_diffsource'             => [
            'config' => [
                'default' => '',
                'type'    => 'passthrough',
            ],
        ],
        'l10n_parent'                 => [
            'label'       => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'config'      => [
                'default'               => 0,
                'foreign_table'         => 'tx_foundation_core_field_position',
                'foreign_table_where'   => 'AND {#tx_foundation_core_field_position}.{#pid}=###CURRENT_PID### AND {#tx_foundation_core_field_position}.{#sys_language_uid} IN (-1,0)',
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
        'l10n_source'                 => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'name'                        => [
            'label'  => $baseLL . 'name',
            'config' => $inputConfiguration,
        ],
        'new_line_time_restriction'   => [
            'label'  => $baseLL . 'newLineTimeRestriction',
            'config' => $inputConfiguration,
        ],
        'starttime'                   => [
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
        'sys_language_uid'            => [
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'exclude' => true,
            'config'  => [
                'type' => 'language',
            ],
        ],
    ],
    'ctrl'     => [
        'crdate'                     => 'crdate',
        'default_sortby'             => 'uid DESC',
        'delete'                     => 'deleted',
        'enablecolumns'              => [
            'disabled'  => 'hidden',
            'endtime'   => 'endtime',
            'starttime' => 'starttime',
        ],
        'iconfile'                   => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/mimetypes/mimetypes-x-sys_action.svg',
        'label'                      => 'uid',
        'languageField'              => 'sys_language_uid',
        'origUid'                    => 't3_origuid',
        'tstamp'                     => 'tstamp',
        'transOrigDiffSourceField'   => 'l10n_diffsource',
        'transOrigPointerField'      => 'l10n_parent',
        'translationSource'          => 'l10n_source',
        'title'                      => $baseLL . 'ctrl.title',
    ],
    'palettes' => [
        'language'        => [
            'showitem' => 'sys_language_uid, l10n_parent',
        ],
        'timeRestriction' => [
            'showitem' => 'starttime, endtime',
        ],
    ],
    'types'    => [
        '0' => [
            'showitem' => 'name, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, after_language, --palette--;;language, before_access, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, after_hidden, new_line_time_restriction, --linebreak--, --palette--;;timeRestriction',
        ],
    ],
];
