<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'ThumbHash',
    'description' => 'Generate ThumbHash for images in TYPO3. Enhance user experience with fast image thumbnails.',
    'category' => 'fe',
    'author' => 'Wazum',
    'author_email' => '',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.99.99',
        ],
    ],
];
