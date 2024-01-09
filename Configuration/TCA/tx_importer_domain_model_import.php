<?php

return [
    'ctrl' => [
        'title' => 'Import',
        'label' => 'import_type',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:basicdistribution/Resources/Public/Icons/logo.png',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
                'readOnly' => true,
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'readOnly' => true,
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_importer_domain_model_job',
                'foreign_table_where' => 'AND {#tx_importer_domain_model_job}.{#pid}=###CURRENT_PID###' . ' AND {#tx_importer_domain_model_job}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        't3ver_label' => [
            'displayCond' => 'FIELD:t3ver_label:REQ:true',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'none',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'start_time' => [
            'exclude' => true,
            'label' => 'Starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'end_time' => [
            'exclude' => true,
            'label' => 'Endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'import_type' => [
            'exclude' => true,
            'label' => 'Import Type',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'status' => [
            'exclude' => true,
            'label' => 'Import Status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'readOnly' => true,
                'items' => [
                    [
                        'Running',
                        'RUNNING',
                    ],
                    [
                        'Completed',
                        'COMPLETED',
                    ],
                    [
                        'Failed',
                        'FAILED',
                    ],
                ],
            ],
        ],
        'failed_jobs' => [
            'exclude' => true,
            'label' => 'Failed Jobs',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
                'readOnly' => true,
            ],
        ],
        'completed_jobs' => [
            'exclude' => true,
            'label' => 'Completed Jobs',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
                'readOnly' => true,
            ],
        ],
        'total_jobs' => [
            'exclude' => true,
            'label' => 'Total Jobs',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
                'readOnly' => true,
            ],
        ],
        'statistics' => [
            'exclude' => true,
            'label' => 'Statistics',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_importer_domain_model_statistic',
                'foreign_field' => 'import',
                'maxitems' => 999999,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
                'readOnly' => true,
            ],
        ]
    ],
    'types' => [
        0 => ['showitem' => 'sys_language_uid, l10n_parent, hidden, import_type, status, start_time, end_time, failed_jobs, completed_jobs, total_jobs, statistics'],
    ],
];
