<?php

return [
    'frontend' => [
        'html/sourceopt/clean-html' => [
            'target' => \HTML\Sourceopt\Middleware\CleanHtmlMiddleware::class,
            'before' => [
                'typo3/cms-frontend/content-length-headers'
            ],
            'after' => [
                'typo3/cms-frontend/maintenance-mode',
            ],
        ],
        'html/sourceopt/svg-store' => [
            'target' => \HTML\Sourceopt\Middleware\SvgStoreMiddleware::class,
            'before' => [
                'typo3/cms-frontend/content-length-headers'
            ],
            'after' => [
                'html/sourceopt/clean-html',
            ]
        ]
    ]
];
