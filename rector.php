<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    // 1. Pfade definieren
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/public', // Auch hier kann PHP-Logik stecken
    ]);

    // 2. Regel-Sets für High-End Qualität
    $rectorConfig->sets([
        // Aktualisiert Code auf PHP 8.3/8.4 Standard (Attributes, Readonly, etc.)
        LevelSetList::UP_TO_PHP_84,           // Volle PHP 8.4 Power
        SetList::DEAD_CODE,                   // Entfernt unnötigen Ballast
        SetList::CODE_QUALITY,                // Schreibt sauberen Code
        SetList::TYPE_DECLARATION,            // Maximale Typsicherheit (hilft PHPStan Level max)
        SetList::PRIVATIZATION,               // Macht alles privat, was nicht öffentlich sein muss
        SetList::INSTANCEOF,                  // Modernisiert instanceof-Prüfungen

        // PHPUnit 11 & Attribute-Migration
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);

    // 3. Einzelregeln (Explizit für strikte Konstruktoren)
    $rectorConfig->rule(TypedPropertyFromStrictConstructorRector::class);

    // 4. Code-Stil während des Umbaus
    $rectorConfig->importNames();             // Ersetzt \App\Service\Cool durch use App\Service\Cool;
    $rectorConfig->importShortClasses(false); // Verhindert Namenskollisionen

    // 5. Performance & Cache
    $rectorConfig->parallel();                // Nutzt alle Kerne (wie dein PHPCS/PHPStan)
    $rectorConfig->cacheDirectory('.cache/rector');

    // 6. Überspringe spezifische Regeln (falls nötig)
    $rectorConfig->skip([
        // Hier könntest du Regeln eintragen, die in deinem Projekt stören
    ]);
};
