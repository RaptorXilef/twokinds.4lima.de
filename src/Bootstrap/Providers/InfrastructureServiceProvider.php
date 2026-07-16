<?php

declare(strict_types=1);

namespace App\Bootstrap\Providers;

use App\Application\Session\SessionManager;
use App\Contracts\Bootstrap\ServiceProviderInterface;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\DependencyInjection\ContainerInterface;
use App\Contracts\Maintenance\MigrationServiceInterface;
use App\Contracts\Maintenance\UpdateMigrationServiceInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\AuditLogRepositoryInterface;
use App\Contracts\Storage\BackupServiceInterface;
use App\Contracts\Storage\CronStateRepositoryInterface;
use App\Contracts\Storage\GroupRepositoryInterface;
use App\Contracts\Storage\LockManagerInterface;
use App\Contracts\Storage\LoginAttemptRepositoryInterface;
use App\Contracts\Storage\StorageInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\ErrorLoggerInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\StorageBootstrapperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Contracts\System\SystemUpdaterInterface;
use App\Contracts\Utils\ClockInterface;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Maintenance\BackupService;
use App\Infrastructure\Maintenance\GitHubUpdaterService;
use App\Infrastructure\Maintenance\MigrationService;
use App\Infrastructure\Maintenance\StorageBootstrapper;
use App\Infrastructure\Maintenance\UpdateMigrationService;
use App\Infrastructure\Security\RateLimiter;
use App\Infrastructure\Storage\FileCronStateRepository;
use App\Infrastructure\Storage\FileLockManager;
use App\Infrastructure\Storage\ImageStorageService;
use App\Infrastructure\Storage\JsonAuditLogRepository;
use App\Infrastructure\Storage\JsonGroupRepository;
use App\Infrastructure\Storage\JsonHelper;
use App\Infrastructure\Storage\JsonLoginAttemptRepository;
use App\Infrastructure\Storage\JsonUserRepository;
use App\Infrastructure\Storage\MySqlAuditLogRepository;
use App\Infrastructure\Storage\MySqlGroupRepository;
use App\Infrastructure\Storage\MySqlLoginAttemptRepository;
use App\Infrastructure\Storage\MySqlUserRepository;
use App\Infrastructure\Storage\StorageFactory;
use App\Infrastructure\System\SystemInfoService;
use App\Infrastructure\Utils\SystemClock;

/**
 * Der InfrastructureServiceProvider.
 *
 * Registriert alle Hardware-, Netzwerk- und Dateisystem-nahen Komponenten
 * im Dependency Injection Container der Anwendung. Diese Schicht stellt
 * sicher, dass die Core-Logik ausschließlich mit Interfaces (Contracts)
 * kommuniziert, ohne die tatsächlichen Implementierungsdetails (z.B.
 * MySQL, JSON, PayPal, SMTP) zu kennen.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class InfrastructureServiceProvider implements ServiceProviderInterface
{
    /**
     * Bindet alle Infrastruktur-Dienste an ihre entsprechenden Interfaces im DI-Container.
     *
     * @param ContainerInterface $container Der Dependency Injection Container der Applikation.
     */
    public function register(ContainerInterface $container): void
    {
        /*
         |--------------------------------------------------------------------------
         | 1. CORE SYSTEM & DATABASE
         |--------------------------------------------------------------------------
         | Grundlegende Datenbankverbindungen und persistente Systemspeicher.
         */

        $container->bind(\PDO::class, fn (): ?\PDO => PdoFactory::create(
            $container->get(ConfigInterface::class),
        ));

        $container->bind(StorageInterface::class, fn (): StorageInterface => StorageFactory::create(
            $container->get(\PDO::class),
            $container->get(ConfigInterface::class),
            $container->get(JsonHelperInterface::class),
        ));

        // Mapping des System-Clocks für testbare Zeitstempel
        $container->bind(ClockInterface::class, fn () => $container->get(SystemClock::class));

        /*
         |--------------------------------------------------------------------------
         | 2. DATA REPOSITORIES (FACTORY PATTERN)
         |--------------------------------------------------------------------------
         | Entscheidet zur Laufzeit anhand der Konfiguration (storage_config),
         | ob die Daten im JSON-Flatfile oder in einer MySQL-Tabelle landen.
         */

        $container->bind(AuditLogRepositoryInterface::class, function () use ($container) {
            $config = $container->get(ConfigInterface::class);

            return ($config->get('storage_config')['audit_logs']['type'] ?? 'json') === 'mysql'
                ? new MySqlAuditLogRepository($container->get(\PDO::class), $config)
                : new JsonAuditLogRepository($config, $container->get(JsonHelperInterface::class));
        });

        $container->bind(GroupRepositoryInterface::class, function () use ($container) {
            $config = $container->get(ConfigInterface::class);

            return ($config->get('storage_config')['groups']['type'] ?? 'json') === 'mysql'
                ? new MySqlGroupRepository($container->get(\PDO::class), $config, $container->get(JsonHelperInterface::class))
                : new JsonGroupRepository($config, $container->get(JsonHelperInterface::class));
        });

        $container->bind(LoginAttemptRepositoryInterface::class, function () use ($container) {
            $config = $container->get(ConfigInterface::class);

            return ($config->get('storage_config')['login_attempts']['type'] ?? 'json') === 'mysql'
                ? new MySqlLoginAttemptRepository($container->get(\PDO::class), $config)
                : new JsonLoginAttemptRepository($config, $container->get(JsonHelperInterface::class));
        });

        $container->bind(UserRepositoryInterface::class, function () use ($container) {
            $config = $container->get(ConfigInterface::class);

            return ($config->get('storage_config')['users']['type'] ?? 'json') === 'mysql'
                ? new MySqlUserRepository($container->get(\PDO::class), $config)
                : new JsonUserRepository($config, $container->get(JsonHelperInterface::class));
        });



        /*
         |--------------------------------------------------------------------------
         | 3. NETWORK & THIRD-PARTY SERVICES
         |--------------------------------------------------------------------------
         | Externe APIs, Payment-Provider und E-Mail Versand.
         */

        /*
         |--------------------------------------------------------------------------
         | 4. SECURITY & SESSION MANAGEMENT
         |--------------------------------------------------------------------------
         | Schutzmechanismen gegen Brute-Force, Dateizugriff und Auth-Handling.
         */

        $container->bind(AuthSessionInterface::class, fn () => clone $container->get(
            SessionManager::class,
        ));

        $container->bind(LockManagerInterface::class, fn () => $container->get(
            FileLockManager::class,
        ));

        $container->bind(RateLimiterInterface::class, fn () => $container->get(
            RateLimiter::class,
        ));

        /*
         |--------------------------------------------------------------------------
         | 5. SYSTEM, MAINTENANCE & UTILS
         |--------------------------------------------------------------------------
         | Hardware- und System-Tools für Backups, Updates, Migrationen und I/O.
         */

        $container->bind(BackupServiceInterface::class, fn () => $container->get(
            BackupService::class,
        ));

        $container->bind(CronStateRepositoryInterface::class, fn () => $container->get(
            FileCronStateRepository::class,
        ));

        $container->bind(ErrorLoggerInterface::class, fn () => $container->get(
            ErrorLogger::class,
        ));

        $container->bind(ImageStorageInterface::class, fn () => $container->get(
            ImageStorageService::class,
        ));

        $container->bind(JsonHelperInterface::class, fn () => new JsonHelper());

        $container->bind(StorageBootstrapperInterface::class, fn () => $container->get(
            StorageBootstrapper::class,
        ));

        $container->bind(SystemInfoInterface::class, fn () => $container->get(
            SystemInfoService::class,
        ));

        $container->bind(SystemUpdaterInterface::class, fn () => $container->get(
            GitHubUpdaterService::class,
        ));

        $container->bind(UpdateMigrationServiceInterface::class, fn () => $container->get(
            UpdateMigrationService::class,
        ));

        // Haupt-Migrations-Dienst
        $container->bind(MigrationServiceInterface::class, fn () => new MigrationService(
            $container->get(\PDO::class),
            $container->get(BackupServiceInterface::class),
            $container->get(ConfigInterface::class),
            $container->get(JsonHelperInterface::class),
        ));
    }
}
