<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Foundation.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

$childLL = 'LLL:EXT:foundation/Resources/Private/Language/Backend/Configuration/TCA/Overrides/extendedTcaChildModel.xlf:';

$expected = require __DIR__ . '/ExpectedTcaForExtendedTcaParentModel.php';

$expected['columns']['override_field'] = [
    'label'  => $childLL . 'overrideField',
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
];
$expected['columns']['status_field'] = [
    'label'  => $childLL . 'statusField',
    'config' => [
        'format'   => 'integer',
        'type'     => 'number',
        'EXT'      => [
            'foundation' => [
                'databaseDefinition' => 'int DEFAULT 0 NOT NULL',
            ],
        ],
        'required' => true,
    ],
];
$expected['ctrl']['label'] = 'overrideField';
$expected['types']['0']['showitem'] .= ', override_field, status_field';

return $expected;
