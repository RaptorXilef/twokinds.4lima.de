<?php

declare(strict_types=1);

namespace App\Bootstrap\Providers;

use App\Application\Session\SessionManager;
use App\Contracts\Bootstrap\ServiceProviderInterface;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\DependencyInjection\ContainerInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ErrorLoggerInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Service\SiteGeneratorService;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Storage\JsonHelper;
use App\Infrastructure\Storage\LocalImageStorage;
use App\Infrastructure\Storage\MySqlChapterRepository;
use App\Infrastructure\Storage\MySqlCharacterGroupRepository;
use App\Infrastructure\Storage\MySqlCharacterRepository;
use App\Infrastructure\Storage\MySqlComicRepository;
use App\Infrastructure\Storage\MySqlComicRevisionRepository;
use App\Infrastructure\Storage\MySqlReportRepository;
use App\Infrastructure\Storage\MySqlRoleRepository;
use App\Infrastructure\Storage\MySqlUserRepository;
use App\Infrastructure\System\LocalAssetHelper;
use App\Infrastructure\System\SystemInfoService;
use App\Infrastructure\Utils\SystemClock;

final class InfrastructureServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // 1. Core / System (Datenbank, Uhrzeit, Logging)
        $container->bind(\PDO::class, fn (): ?\PDO => PdoFactory::create(
            $container->get(ConfigInterface::class),
        ));

        $container->bind(ClockInterface::class, fn () => new SystemClock());

        $container->bind(JsonHelperInterface::class, fn () => new JsonHelper());

        $container->bind(ImageStorageInterface::class, fn () => new LocalImageStorage(
            $container->get(ConfigInterface::class),
        ));

        $container->bind(ErrorLoggerInterface::class, fn () => new ErrorLogger(
            $container->get(ConfigInterface::class),
        ));

        // 2. Security & Session
        $container->bind(AuthSessionInterface::class, fn () => clone $container->get(SessionManager::class));

        $container->bind(RoleRepositoryInterface::class, fn () => new MySqlRoleRepository(
            $container->get(\PDO::class),
            $container->get(JsonHelperInterface::class),
        ));

        $container->bind(UserRepositoryInterface::class, fn () => new MySqlUserRepository(
            $container->get(\PDO::class),
        ));

        // 3. Domain Repositories (TwoKinds MySQL Persistenz)
        $container->bind(ComicRepositoryInterface::class, fn () => new MySqlComicRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(ComicRevisionRepositoryInterface::class, fn () => new MySqlComicRevisionRepository(
            $container->get(\PDO::class),
            $container->get(ClockInterface::class),
            $container->get(ConfigInterface::class),
        ));

        $container->bind(CharacterRepositoryInterface::class, fn () => new MySqlCharacterRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(CharacterGroupRepositoryInterface::class, fn () => new MySqlCharacterGroupRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(ReportRepositoryInterface::class, fn () => new MySqlReportRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(ChapterRepositoryInterface::class, fn () => new MySqlChapterRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(SystemInfoInterface::class, fn () => new SystemInfoService(
            $container->get(ConfigInterface::class),
            $container->get(JsonHelperInterface::class),
        ));

        $container->bind(AssetHelperInterface::class, fn () => new LocalAssetHelper(
            $container->get(ConfigInterface::class),
        ));

        // TODO Prüfen ob definition überhaupt nötig, oder ob Magisch geladen
        $container->bind(SiteGeneratorService::class, fn () => new SiteGeneratorService(
            $container->get(ComicRepositoryInterface::class),
            $container->get(ChapterRepositoryInterface::class),
            $container->get(ConfigInterface::class),
        ));
    }
}
