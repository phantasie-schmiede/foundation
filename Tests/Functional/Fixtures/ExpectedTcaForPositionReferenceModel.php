<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

$baseLL = 'LLL:EXT:foundation/Resources/Private/Language/Backend/Configuration/TCA/positionReferenceModel.xlf:';

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
        'after_palette'  => [
            'label'  => $baseLL . 'afterPalette',
            'config' => $inputConfiguration,
        ],
        'after_tab'      => [
            'label'  => $baseLL . 'afterTab',
            'config' => $inputConfiguration,
        ],
        'before_palette' => [
            'label'  => $baseLL . 'beforePalette',
            'config' => $inputConfiguration,
        ],
        'in_palette'     => [
            'label'  => $baseLL . 'inPalette',
            'config' => $inputConfiguration,
        ],
        'in_tab'         => [
            'label'  => $baseLL . 'inTab',
            'config' => $inputConfiguration,
        ],
    ],
    'ctrl'     => [
        'default_sortby' => 'uid DESC',
        'label'          => 'uid',
        'title'          => $baseLL . 'ctrl.title',
    ],
    'palettes' => [
        'ref_palette' => [
            'showitem' => 'in_palette',
        ],
    ],
    'types'    => [
        '0' => [
            'showitem' => 'before_palette, --palette--;;ref_palette, after_palette, --div--;Ref Tab, after_tab, in_tab',
        ],
    ],
];
