<?php
declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\Foundation\Utility\Configuration;

/**
 * Class TcaUtility
 *
 * @package PSBits\Foundation\Utility\Configuration
 */
class TcaUtility
{
    public const array CORE_FIELD_LABELS = [
        'ENABLED'     => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
        'END_TIME'    => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
        'L18N_PARENT' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
        'LANGUAGE'    => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
        'START_TIME'  => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
    ];

    public const array CORE_TAB_LABELS = [
        'ACCESS'   => 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access',
        'LANGUAGE' => 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
    ];

    /*
     * This array constant compensates inconsistencies in TCA key naming. All keys that are not listed here will be
     * kept unchanged.
     */
    private const array PROPERTY_KEY_MAPPING = [
        'defaultSortBy'                 => 'default_sortby',
        'editLock'                      => 'editlock',
        'enableColumns'                 => 'enablecolumns',
        'enableRichText'                => 'enableRichtext',
        'foreignDefaultSortBy'          => 'foreign_default_sortby',
        'foreignField'                  => 'foreign_field',
        'foreignMatchFields'            => 'foreign_match_fields',
        'foreignSortBy'                 => 'foreign_sortby',
        'foreignTable'                  => 'foreign_table',
        'foreignTableWhere'             => 'foreign_table_where',
        'formattedLabelUserFunc'        => 'formattedLabel_userFunc',
        'formattedLabelUserFuncOptions' => 'formattedLabel_userFunc_options',
        'iconFile'                      => 'iconfile',
        'isStatic'                      => 'is_static',
        'l10nDisplay'                   => 'l10n_display',
        'l10nMode'                      => 'l10n_mode',
        'labelAlt'                      => 'label_alt',
        'labelAltForce'                 => 'label_alt_force',
        'labelUserFunc'                 => 'label_userFunc',
        'maxItems'                      => 'maxitems',
        'minItems'                      => 'minitems',
        'mm'                            => 'MM',
        'mmHasUidField'                 => 'MM_hasUidField',
        'mmInsertFields'                => 'MM_insert_fields',
        'mmMatchFields'                 => 'MM_match_fields',
        'mmOppositeField'               => 'MM_opposite_field',
        'mmOppositeUsage'               => 'MM_oppositeUsage',
        'selIconField'                  => 'selicon_field',
        'sortBy'                        => 'sortby',
        'typeIconClasses'               => 'typeicon_classes',
        'typeIconColumn'                => 'typeicon_column',
    ];

    public static function convertKey(string $key): string
    {
        return self::PROPERTY_KEY_MAPPING[$key] ?? $key;
    }

    public static function getDefaultConfigurationForDisabledField(): array
    {
        return [
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
            'exclude' => true,
            'label'   => self::CORE_FIELD_LABELS['ENABLED'],
        ];
    }

    public static function getDefaultConfigurationForEndTimeField(): array
    {
        return [
            'config'  => [
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'default'   => 0,
                'range'     => [
                    'upper' => gmmktime(0, 0, 0, 1, 1, 2038),
                ],
                'type'      => 'datetime',
            ],
            'exclude' => true,
            'label'   => self::CORE_FIELD_LABELS['END_TIME'],
        ];
    }

    public static function getDefaultConfigurationForLanguageField(): array
    {
        return [
            'config'  => [
                'type' => 'language',
            ],
            'exclude' => true,
            'label'   => self::CORE_FIELD_LABELS['LANGUAGE'],
        ];
    }

    public static function getDefaultConfigurationForStartTimeField(): array
    {
        return [
            'config'  => [
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'default'   => 0,
                'type'      => 'datetime',
            ],
            'exclude' => true,
            'label'   => self::CORE_FIELD_LABELS['START_TIME'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function getDefaultConfigurationForTransOrigDiffSourceField(): array
    {
        return [
            'config' => [
                'default' => '',
                'type'    => 'passthrough',
            ],
        ];
    }

    public static function getDefaultConfigurationForTransOrigPointerField(string $tableName): array
    {
        return [
            'config'      => [
                'default'             => 0,
                'foreign_table'       => $tableName,
                'foreign_table_where' => 'AND {#' . $tableName . '}.{#pid}=###CURRENT_PID### AND {#' . $tableName . '}.{#sys_language_uid} IN (-1,0)',
                'items'               => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'renderType'          => 'selectSingle',
                'type'                => 'select',
            ],
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label'       => self::CORE_FIELD_LABELS['L18N_PARENT'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function getDefaultConfigurationForTranslationSourceField(): array
    {
        return [
            'config' => [
                'type' => 'passthrough',
            ],
        ];
    }
}
