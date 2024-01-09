<?php

/**
 * Extension Manager/Repository config file for ext "importer".
 * @phpstan-ignore-next-line
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Importer',
    'description' => '',
    'category' => 'extensions',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'fluid_styled_content' => '11.5.0-11.5.99',
            'rte_ckeditor' => '11.5.0-11.5.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Itx\\Importer\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Itx-Typo3-Team',
    'author_email' => 'Patrick.Heinz@itx.de',
    'author_company' => 'Itx',
    'version' => '1.0.0',
];
