<?php

use Itx\Importer\Controller\ImportController;

return [
    'import_manager' => [
        'parent' => 'web',
        'position' => [],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/importer',
        'labels' => 'LLL:EXT:importer/Resources/Private/Language/locallang_be.xlf',
        'icon' => 'EXT:importer/Resources/Public/Icons/Extension.svg',
        'extensionName' => 'Importer',
        'controllerActions' => [
            ImportController::class => [
                'list', 'show', 'listAll', 'startImport',
            ],
        ],
    ],
];
