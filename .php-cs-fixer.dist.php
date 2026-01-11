<?php

declare(strict_types=1); // Auch die Config-Datei selbst sollte strikt sein

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/public',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true) // Erlaubt strengere Regeln (wie strict_types)
    ->setRules([
        '@PER-CS' => true, // Der neue Standard (Nachfolger von PSR-12)
        '@PHP83Migration' => true,
        '@Symfony' => true, // Ein sehr guter Basis-Standard für Clean Code
        'concat_space' => ['spacing' => 'one'], // DAS HIER ERZWINGT ' . ' STATT '.'

        // --- STRIKTE TYPEN & SICHERHEIT ---
        'declare_strict_types' => true,
        'strict_param' => true,
        'void_return' => true,
        'modernize_types_casting' => true,

        // --- STRUKTUR & SAUBERKEIT ---
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'fully_qualified_strict_types' => true, // Verhindert unnötige FQCNs
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,

        // --- ABSTÄNDE & LESBARKEIT ---
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false, // Sauberer Standard
        ],
        'class_attributes_separation' => [
            'elements' => ['method' => 'one', 'property' => 'one'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],

        // --- PHPDOC ---
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.cache/php-cs-fixer/cache.json');
