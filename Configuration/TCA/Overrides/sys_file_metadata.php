<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$columns = [
    'thumb_hash' => [
        'exclude' => true,
        'label' => 'LLL:EXT:thumb_hash/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.thumb_hash',
        'description' => 'LLL:EXT:thumb_hash/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.thumb_hash.description',
        'config' => [
            'type' => 'input',
            'size' => 50,
            'eval' => 'trim',
            'readOnly' => true,
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $columns);
ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    'thumb_hash',
    '',
    'after:alternative'
);
