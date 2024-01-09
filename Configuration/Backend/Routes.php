<?php

return [
    'goto_import' => [
        'path' => '/gotoImport',
        'access' => 'public',
        'target' => \Itx\Importer\Controller\ImportController::class . '::gotoAction'
    ],
    'import' => [
        'path' => '/import',
        'access' => 'public',
        'target' => \Itx\Importer\Controller\ImportController::class . '::showAction'
    ],
];
