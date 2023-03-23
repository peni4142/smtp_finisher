<?php

/**
 * Extension Manager/Repository config file for ext "smtp_finisher".
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'SMTP-Finisher',
    'description' => '',
    'category' => 'templates',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'fluid_styled_content' => '11.5.0-11.5.99',
            'rte_ckeditor' => '11.5.0-11.5.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'PeerNissen\\SmtpFinisher\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'peni4142',
    'author_email' => 'business@peni4142.com',
    'author_company' => 'Peer Nissen',
    'version' => '1.0.0',
];
