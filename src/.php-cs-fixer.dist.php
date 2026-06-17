<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/app')
    ->in(__DIR__ . '/bootstrap')
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/public')
    ->in(__DIR__ . '/routes')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/tools')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
