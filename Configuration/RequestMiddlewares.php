<?php

return [
    'frontend' => [
        'html/sourceopt/clean-html' => [
            'target' => \HTML\Sourceopt\Middleware\CleanHtmlMiddleware::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ]
        ],
        'html/sourceopt/svg-store' => [
            'target' => \HTML\Sourceopt\Middleware\SvgStoreMiddleware::class,
            'after' => [
                'html/sourceopt/clean-html'
            ]
        ]
    ]
];
