<?php

/**
 * Globaler Anwendungs-Bootstrap und Initialisierungs-Skript.
 *
 * Findet den Root-Pfad, lädt den Autoloader und initialisiert den Dependency Container.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 *
 * return App\Bootstrap\Container Gibt die fertig konfigurierte Dependency-Injection-Container-Instanz zurück.
 */

declare(strict_types=1);

use App\Bootstrap\SystemBootstrapper;

$appRoot = (function (): string {
    $dir = __DIR__;
    while ($dir !== \dirname($dir)) {
        if (\file_exists($dir . '/vendor/autoload.php')) {
            return $dir;
        }
        $dir = \dirname($dir);
    }

    return \dirname(__DIR__, 2);
})();

require_once $appRoot . '/vendor/autoload.php';

// Wir packen die Logik in eine sofort ausgeführte Funktion,
// um PHPCS SideEffects zu umgehen, da der Scope dadurch geschlossen wird.
return SystemBootstrapper::bootstrap($appRoot);
