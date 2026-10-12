<?php

$EM_CONF[$_EXTKEY] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'misc',
    'clearCacheOnLoad' => true,
    'constraints'      => [
        'conflicts' => [],
        'depends'   => [
            'php'   => '8.3.0-8.5.99',
            'typo3' => '13.4.0-14.99.99',
        ],
        'suggests'  => [],
    ],
    'description'      => 'Configuration framework for TYPO3 extension development',
    'state'            => 'stable',
    'title'            => 'PSBits | Foundation',
    'version'          => '4.0.0',
];
