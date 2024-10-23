<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__, 3);
require $baseDir . '/.Build/vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->in($baseDir . '/Classes')
    ->in($baseDir . '/Tests')
    ->in($baseDir . '/Resources/Private/Build')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        '@DoctrineAnnotation' => true,
        '@PSR2' => true,
        '@PHP81Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;
