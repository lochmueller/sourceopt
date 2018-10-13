<?php

return [
    'frontend' => [
        'html/sourceopt/clean-html' => [
            'target' => \HTML\Sourceopt\Middleware\CleanHtmlMiddleware::class,
            'after' => [
                'typo3/cms-frontend/maintenance-mode',
            ],
        ]
    ]
];
