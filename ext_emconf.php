<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Source Optimization',
    'description' => 'Optimization of the final page: reformatting the (x)HTML output & removal of new-lines, comments and generator-info including search and replace strings using your regular expressions. In addition combines all SVG selected within content-elements into one <symbol> file and replaces <img> by <use>.',
    'category' => 'fe',
    'version' => '5.2.1',
    'state' => 'stable',
    'author' => 'Dr. Ronald P. Steiner, Boris Nicolai, Tim Lochmueller, Marcus Förster',
    'author_email' => 'ronald.steiner@googlemail.com, boris.nicolai@andavida.com, tim@fruit-lab.de',
    'author_company' => null,
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.17-13.4.99',
            'php' => '7.4.0-8.99.99',
        ],
    ],
];
