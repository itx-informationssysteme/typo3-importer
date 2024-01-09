<?php

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

defined('TYPO3') or die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['LOG']['import']['writerConfiguration'] = [
    LogLevel::DEBUG => [
        FileWriter::class => [
            'logFile' => Environment::getVarPath() . '/log/import.log'
        ]
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][700] = 'EXT:importer/Resources/Private/Templates/Mail';
