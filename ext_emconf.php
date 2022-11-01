<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Source Optimization',
    'description' => 'Optimization of the final page: reformatting the (x)html output, removal of new-lines, comments and generator-info. In addition combines all SVG selected within content-elements into one <symbol> file and replaces <img> by the corresponding <use>.',
    'category' => 'fe',
    'version' => '4.0.5',
    'state' => 'stable',
    'author' => 'Dr. Ronald P. Steiner, Boris Nicolai, Tim Lochmueller, Marcus Förster',
    'author_email' => 'ronald.steiner@googlemail.com, boris.nicolai@andavida.com, tim@fruit-lab.de',
    'author_company' => null,
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.17-12.4.99',
            'php' => '7.4.0-8.99.99',
        ],
    ],
];
