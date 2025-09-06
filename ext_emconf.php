<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'ThumbHash',
    'description' => 'Generate ThumbHash for images in TYPO3. Enhance user experience with fast image thumbnails.',
    'author' => 'Wolfgang Klinger',
    'author_email' => 'wolfgang@wazum.com',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.99.99',
        ],
    ],
];
