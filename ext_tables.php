<?php
defined('TYPO3') or die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule('typo3_importer',
                                                            'web',
                                                            'import_manager',
                                                            'bottom',
                                                            [
                                                                \Itx\Importer\Controller\ImportController::class => 'list, show, listAll, startImport'
                                                            ],
                                                            [
                                                                'access' => 'admin',
                                                                'iconIdentifier' => 'importer-logo',
                                                                'labels' => 'LLL:EXT:typo3_importer/Resources/Private/Language/locallang_be.xlf',
                                                                'navigationComponentId' => '',
                                                                'inheritNavigationComponentFromMainModule' => false,
                                                            ]);
