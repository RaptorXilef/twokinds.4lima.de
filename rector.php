<?php

// SPDX-License-Identifier: UNLICENSED

/**
 * Konfigurationsdatei für Rector PHP.
 *
 * Automatisiert die Code-Modernisierung und Refactoring-Prozesse, um den
 * PHP 8.3 Standard (Produktion) sowie höchste Typsicherheit zu gewährleisten.
 *
 * Path: rector.php
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\PostRector\Rector\UnusedImportRemovingPostRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    // 1. Pfade definieren
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/public',
        __DIR__ . '/config',
    ]);

    // Expliziter Autoloader-Pfad für maximale Stabilität
    $rectorConfig->autoloadPaths([
        __DIR__ . '/vendor/autoload.php',
    ]);

    // WICHTIG: Zwingt Rector auf PHP 8.3, auch wenn es in meiner PHP 8.5 Umgebung läuft!
    // Schützt die Produktivumgebung vor Fatal Errors durch zu neue Syntax.
    $rectorConfig->phpVersion(PhpVersion::PHP_83);

    // 2. Regel-Sets für High-End Qualität
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,           // Volle PHP 8.3 Power
        SetList::DEAD_CODE,                   // Entfernt unnötigen Ballast
        SetList::CODE_QUALITY,                // Schreibt sauberen Code
        SetList::TYPE_DECLARATION,            // Maximale Typsicherheit (hilft PHPStan Level max)
        SetList::PRIVATIZATION,               // Macht alles privat, was nicht öffentlich sein muss
        SetList::INSTANCEOF,                  // Modernisiert instanceof-Prüfungen
        SetList::EARLY_RETURN, // Bouncer-Pattern. Löst tiefe Verschachtelungen auf und nutzt 'continue'/'return'

        // PHPUnit & Attribute-Migration
        PHPUnitSetList::PHPUNIT_100,          // Basis für v11/v12
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);

    // 3. Einzelregeln (Explizit für strikte Konstruktoren)
    $rectorConfig->rule(TypedPropertyFromStrictConstructorRector::class);
    $rectorConfig->rule(DeclareStrictTypesRector::class);

    // 4. Code-Stil & Import Management
    $rectorConfig->importNames();             // Nutzt 'use' Statements statt voller Pfade
    $rectorConfig->importShortClasses(false); // Verhindert Namenskollisionen

    // 5. Performance & Cache
    $rectorConfig->parallel();                // Nutzt alle Kerne (wie dein PHPCS/PHPStan)
    $rectorConfig->cacheDirectory('.cache/rector');

    // 6. Sicherheits-Skips (Deine "Battle-Scars" Schutzregeln)
    $rectorConfig->skip([
        // Korrekter Pfad für PHPUnit Regeln (verhindert Konflikte mit $this vs static)
        // PreferPHPUnitThisCallRector::class,
        //     use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;

        // Verhindert das Löschen der Konstruktor-Parameter (Named Arguments Fix)
        RemoveUnusedPromotedPropertyRector::class,

        // Verhindert das "Narrowing" (Löschen) von Properties in Tests
        // 'Rector\Php80\Rector\Class_\NarrowUnusedSetUpDefinedPropertyRector',

        // Verhindert das Löschen von eigentlich benötigten Imports
        UnusedImportRemovingPostRector::class,

        // SHADOWING-MOCKING SCHUTZ:
        // Verhindert, dass Rector 'use function' einfügt und das Mocking hebelt
        NameImportingPostRector::class => [
            // Beispiel: __DIR__ . '/src/Security/MyService.php',
        ],
    ]);
};
