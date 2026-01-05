<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList; // <--- WICHTIG: Importieren!
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    // 1. Wo liegt dein Code?
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // 2. Welche Sets sollen angewendet werden?
    $rectorConfig->sets([
        // Aktualisiert Code auf PHP 8.3/8.4 Standard (Attributes, Readonly, etc.)
        LevelSetList::UP_TO_PHP_84,

        // Entfernt toten Code (nie aufgerufene Methoden etc.)
        SetList::DEAD_CODE,

        // Verbessert die Code-Qualität allgemein
        SetList::CODE_QUALITY,

        // Fügt Typen hinzu (string, int, void) wo möglich
        SetList::TYPE_DECLARATION,

        // Wandelt /** @test */ in #[Test] um und fixt Deprecations// Wandelt /** @test */ in #[Test] um und fixt Deprecations
        PHPUnitSetList::PHPUNIT_110,

        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    // 3. Optional: Cache für Geschwindigkeit
    $rectorConfig->cacheDirectory('.cache/rector');
};
