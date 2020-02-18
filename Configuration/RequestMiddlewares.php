<?php

use HTML\Sourceopt\Middleware\CleanHtmlMiddleware;

return [
    'frontend' => [
        'html/sourceopt/clean-html' => [
            'target' => CleanHtmlMiddleware::class,
            'after' => [
                'typo3/cms-frontend/maintenance-mode',
            ],
        ]
    ]
];
