<?php

use HTML\Sourceopt\Middleware\CleanHtmlMiddleware;

return [
    'frontend' => [
        'html/sourceopt/clean-html' => [
            'target' => CleanHtmlMiddleware::class,
            'after' => [
                'typo3/cms-frontend/maintenance-mode',
            ],
        ],
        'html/sourceopt/svg-store' => [
            'target' => \HTML\Sourceopt\Middleware\SvgStoreMiddleware::class,
            'after' => [
                'html/sourceopt/clean-html',
            ]
        ]
    ]
];
