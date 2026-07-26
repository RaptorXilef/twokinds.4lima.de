<?php

// Auch die Config-Datei selbst sollte strikt sein
declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixerCustomFixers\Fixers;

// Der Autoloader ist zwingend für die Custom Fixers erforderlich
require_once __DIR__ . '/vendor/autoload.php';

$finder = (new Finder())
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/public',
        __DIR__ . '/config',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true) // Erlaubt strengere Regeln (wie strict_types)
    ->registerCustomFixers(new Fixers()) // Registriert das installierte Zusatz-Paket
    ->setRules([
        // --- BASIS-STANDARDS ---
        '@PER-CS'         => true, // Der aktuelle Standard (Nachfolger von PSR-12)
        '@PHP83Migration' => true, // Erzwingt PHP 8.3 Features (z.B. Konstanten-Typen)
        '@Symfony'        => true, // Bewährter Basis-Standard für Clean Code

        // --- MODERNISIERUNG (Custom Fixers) ---
        // Macht aus private $x; __construct($x) { $this->x = $x; } -> __construct(private $x)
        'PhpCsFixerCustomFixers/promoted_constructor_property' => true,

        // Automatisches readonly für beförderte Eigenschaften (Vorerst deaktiviert)
        // 'PhpCsFixerCustomFixers/readonly_promoted_properties' => true,

        // --- WEITERE HELFER (Zusatz-Paket) ---
        // 'PhpCsFixerCustomFixers/phpdoc_single_line_var' => true, // Einzeilige @var PHPDocs
        'PhpCsFixerCustomFixers/no_useless_strlen'      => true,    // Optimiert strlen() Vergleiche
        'PhpCsFixerCustomFixers/no_useless_parenthesis' => true,    // Entfernt unnötige Klammern
        'PhpCsFixerCustomFixers/trim_key'               => true,    // Säubert Array-Schlüssel

        // --- STRUKTUR & LESBARKEIT ---
        'binary_operator_spaces' => [
            'default'   => 'single_space', // Standard bleibt ein einfaches Leerzeichen
            'operators' => [
                '='  => 'align_single_space_minimal', // NEU: Zuweisungen ausrichten
                '=>' => 'align_single_space_minimal', // ERZWINGT DIE AUSRICHTUNG VON PFEILEN (für Match & Arrays)
            ],
        ],
        'yoda_style' => [
            'equal'            => false,
            'identical'        => false,
            'less_and_greater' => false,
        ], // Deaktiviert den Yoda-Style (zwingt Variable auf die linke Seite)
        'concat_space'                           => ['spacing' => 'one'], // Erzwingt ' . ' statt '.'
        'single_line_throw'                      => false,                // Exceptions nicht in eine Zeile quetschen
        'array_indentation'                      => true,                 // Korrekte Einrückung für Arrays
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'not_operator_with_successor_space'      => true, // Leerzeichen nach '!' für bessere Sichtbarkeit

        // --- STRIKTE TYPEN & SICHERHEIT ---
        'declare_strict_types'    => true,
        'strict_param'            => true,
        'void_return'             => true,
        'modernize_types_casting' => true,

        // NEU: Der Friedensvertrag zwischen PHPMD und PHP-CS-Fixer
        // 1. Das hier zwingt PHP-CS-Fixer, den Backslash bei globalen Klassen zu lassen/hinzuzufügen
        'fully_qualified_strict_types' => [
            'leading_backslash_in_global_namespace' => true,
        ],

        // 2. Das hier sorgt dafür, dass auch Konstanten wie \PHP_VERSION oder \JSON_THROW_ON_ERROR Backslashes bekommen
        'native_constant_invocation' => [
            'fix_built_in' => true,
            'include'      => ['@all'],
            'scope'        => 'all',
        ],

        // 3. Dein bereits korrekter Block (muss so bleiben)
        'global_namespace_import' => [
            'import_classes'   => false,  // Klassen NICHT importieren -> \Exception bleibt inline
            'import_constants' => false,  // Konstanten NICHT importieren -> \PHP_VERSION bleibt inline
            'import_functions' => false,  // Funktionen NICHT importieren -> \is_int() bleibt inline (Performance)
        ],

        /**
         * Native Funktionsaufrufe optimieren.
         * Erzwingt den Backslash (\) vor globalen PHP-Funktionen.
         * Vorteil: Schnellerer Funktions-Lookup & Schutz vor Shadowing.
         */
        'native_function_invocation' => [
            'include' => ['@all'], // Betrifft alle internen PHP-Funktionen
            'scope'   => 'all',    // Überall im Code anwenden
            'strict'  => true,
        ],

        // --- CLEANUP & ORDNUNG ---
        'array_syntax'    => ['syntax' => 'short'],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order'  => ['class', 'function', 'const'], // Erzwingt die richtige Gruppen-Reihenfolge
        ],
        'no_unused_imports'           => true,
        'combine_consecutive_issets'  => true,
        'combine_consecutive_unsets'  => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
        'blank_line_before_statement' => ['statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try']],

        // --- PARAMETER & KOMMAS ---
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'method_argument_space'       => [
            'on_multiline'                     => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],

        // --- PHPDOC ---
        // 'phpdoc_align' => ['align' => 'left'],
        'phpdoc_align' => [
            'align' => 'vertical',
            'tags'  => ['param', 'return', 'throws', 'type', 'var'],
            // 'file', 'copyright', 'license', 'link', 'author', 'since'
        ],

        // Wandelt einzeilige DocBlocks (wie @var) in mehrzeilige um
        'phpdoc_line_span' => [
            'const'    => 'multi',
            'method'   => 'multi',
            'property' => 'multi',
        ],
        'phpdoc_indent'                  => true,  // Stellt sicher, Einrücken innerhalb von PHPDocs konsistent
        'phpdoc_summary'                 => false, // Deutsch-Support: Kein Zwang für Groß/Punkt
        'phpdoc_annotation_without_dot'  => false,  // Deutsch-Support: Kein Punkt am Ende von Tags
        'phpdoc_separation'              => false, // Slevomat steuert die Leerzeilen
        'no_extra_blank_lines'           => ['tokens' => ['extra']], // Verhindert, zusätzliche Leerzeichen wegetrimmt
        'phpdoc_to_comment'              => false, // Verhindert die Umwandlung von /** zu //
        'phpdoc_scalar'                  => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name'        => true,

        'phpdoc_no_alias_tag' => [
            'replacements' => [
                'type' => 'var', // Standard-Ersetzung (wichtig!)
                'link' => 'link', // Hier zwinge ich, link als link zu belassen (kein Replace zu see)
            ],
        ],

        'phpdoc_types'       => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm'  => 'none', // 'none' lässt die Reihenfolge und Form deines Types (Array Shape) in Ruhe
        ],

        // --- PHPUNIT ---
        // Ersetzt $this->assert* durch self::assert* (Best Practice)
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],

        /*'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true, // Verhindert das Löschen von @var mixed
            'remove_inheritdoc' => false,
        ],*/
    ])
    ->setFinder($finder)
    // Zentraler Cache-Ordner
    ->setCacheFile(__DIR__ . '/.cache/php-cs-fixer/cache.json');
