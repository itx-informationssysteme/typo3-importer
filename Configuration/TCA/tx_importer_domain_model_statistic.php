<?php

return [
    'ctrl' => [
        'title' => 'Statistic',
        'label' => '',
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
        'record_name' => [
            'exclude' => true,
            'label' => 'Record name',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'record_table' => [
            'exclude' => true,
            'label' => 'Record table',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'number_added' => [
            'exclude' => true,
            'label' => 'Number of added records',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'eval' => 'int',
            ],
        ],
        'number_updated' => [
            'exclude' => true,
            'label' => 'Number of updated records',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'eval' => 'int',
            ],
        ],
        'number_deleted' => [
            'exclude' => true,
            'label' => 'Number of deleted records',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'eval' => 'int',
            ],
        ],
        'number_unchanged' => [
            'exclude' => true,
            'label' => 'Number of unchanged records',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'eval' => 'int',
            ],
        ],
        'import' => [
            'exclude' => true,
            'label' => 'Import',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'readOnly' => true,
                'foreign_table' => 'tx_importer_domain_model_import',
                'default' => 0,
            ],
        ]
    ],
    'types' => [
        0 => ['showitem' => 'sys_language_uid, l10n_parent, hidden, start_time, end_time, record_name, record_table, number_added, number_updated, number_deleted, number_unchanged, import'],
    ],
];
