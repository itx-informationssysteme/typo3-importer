<?php

return [
    'ctrl' => [
        'title' => 'Queue Job',
        'label' => 'payload_type',
        'label_alt' => 'status',
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
        'readOnly' => true,
        'iconfile' => 'EXT:basicdistribution/Resources/Public/Icons/logo.png',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
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
        'import' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_importer_domain_model_import',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'start_time' => [
            'exclude' => true,
            'label' => 'Starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
            ],
        ],
        'end_time' => [
            'exclude' => true,
            'label' => 'Endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
            ],
        ],
        'is_finisher' => [
            'exclude' => true,
            'label' => 'Is Finisher?',
            'config' => [
                'type' => 'check',
            ],
        ],
        'import_type' => [
            'exclude' => true,
            'label' => 'Import Type',
            'config' => [
                'type' => 'input',
            ],
        ],
        'payload' => [
            'exclude' => true,
            'label' => 'Payload',
            'config' => [
                'type' => 'input',
            ],
        ],
        'payload_type' => [
            'exclude' => true,
            'label' => 'Payload Type',
            'config' => [
                'type' => 'input',
            ],
        ],
        'status' => [
            'exclude' => true,
            'label' => 'Status',
            'config' => [
                'type' => 'input',
            ],
        ],
        'failure_reason' => [
            'exclude' => true,
            'label' => 'Failure reason',
            'config' => [
                'type' => 'input',
            ],
        ],
        'sorting' => [
            'exclude' => true,
            'label' => 'Sorting',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
            ],
        ]
    ],
    'types' => [
        0 => ['showitem' => 'sys_language_uid, l10n_parent, hidden, start_time, end_time, is_finisher, status, payload, payload_type, sorting'],
    ],
];
