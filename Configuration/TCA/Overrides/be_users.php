<?php
$newFields = [
    'importer_failed_notification' => [
        'exclude' => true,
        'label' => 'Send email when import fails',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'items' => [
                [
                    0 => 'false',
                    1 => 'true'
                ]
            ]
        ],
    ]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $newFields);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'importer_failed_notification', '', 'before:TSconfig');
