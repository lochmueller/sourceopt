<?php

declare(strict_types=1);

$finder = (new \PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new \PhpCsFixer\Config())
    ->setCacheFile('.cache/php-cs-fixer')
    ->setFinder($finder)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules(['@PER-CS' => true]);

