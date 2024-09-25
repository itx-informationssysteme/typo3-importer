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
    'author' => 'it.x informationssysteme gmbh',
    'author_email' => '',
    'version' => '0.9.0',
];
