<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

$baseLL = 'LLL:EXT:foundation/Resources/Private/Language/Backend/Configuration/TCA/dataObjectModel.xlf:';

return [
    'columns'  => [
        'name' => [
            'label'  => $baseLL . 'name',
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
    ],
    'ctrl'     => [
        'default_sortby' => 'uid DESC',
        'delete'         => 'deleted',
        'iconfile'       => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/mimetypes/mimetypes-x-sys_action.svg',
        'label'          => 'uid',
        'origUid'        => 't3_origuid',
        'title'          => $baseLL . 'ctrl.title',
    ],
    'palettes' => [],
    'types'    => [
        '0' => [
            'showitem' => 'name',
        ],
    ],
];
