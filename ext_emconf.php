<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Source Optimization',
    'description' => 'Optimization of the final page: reformatting the (x)html output, removal of new lines, and quotes. Move development repository to https://github.com/lochmueller/sourceopt',
    'category' => 'fe',
    'version' => '3.1.0',
    'state' => 'stable',
    'author' => 'Dr. Ronald P. Steiner, Boris Nicolai, Tim Lochmueller',
    'author_email' => 'ronald.steiner@googlemail.com, boris.nicolai@andavida.com, tim@fruit-lab.de',
    'author_company' => null,
    'constraints' =>[
        'depends' => [
            'typo3' => '9.5.17-11.5.99',
            'php' => '7.2.0-7.4.99',
        ],
    ],
];

