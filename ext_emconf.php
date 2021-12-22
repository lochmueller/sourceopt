<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Source Optimization',
    'description' => 'Optimization of the final page: reformatting the (x)html output, removal of new lines, quotes and optional combine referenced SVGs into one symbol-file via <use/>.',
    'category' => 'fe',
    'version' => '4.0.3',
    'state' => 'stable',
    'author' => 'Dr. Ronald P. Steiner, Boris Nicolai, Tim Lochmueller, Marcus Förster',
    'author_email' => 'ronald.steiner@googlemail.com, boris.nicolai@andavida.com, tim@fruit-lab.de',
    'author_company' => null,
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.17-11.5.99',
            'php' => '7.3.0-8.0.99',
        ],
    ],
];
