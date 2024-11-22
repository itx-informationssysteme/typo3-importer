<?php

/**
 * Extension Manager/Repository config file for ext "importer".
 * @phpstan-ignore-next-line
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Importer',
    'description' => 'A TYPO3 extension which contains a failure safe framework for building structured import processes. It features a backend module and queue worker commands.',
    'category' => 'extensions',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.9.99',
            'fluid_styled_content' => '13.4.0-13.9.99',
            'rte_ckeditor' => '13.4.0-13.9.99',
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
    'author' => 'it.x informationssysteme gmbh',
    'author_email' => '',
    'version' => '2.0.0',
];
