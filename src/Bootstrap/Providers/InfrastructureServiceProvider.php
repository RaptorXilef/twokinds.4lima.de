<?php

declare(strict_types=1);

namespace App\Bootstrap\Providers;

use App\Application\Session\SessionManager;
use App\Contracts\Bootstrap\ServiceProviderInterface;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\DependencyInjection\ContainerInterface;
use App\Contracts\Mail\DirectMailServiceInterface;
use App\Contracts\Mail\MailLogInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\Storage\LoginAttemptRepositoryInterface;
use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\AnalyticsClientInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\BackupServiceInterface;
use App\Contracts\System\ErrorLoggerInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Contracts\System\RemoteResourceProberInterface;
use App\Contracts\System\RouteCacheInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Contracts\Utils\ClockInterface;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Mail\MailQueueService;
use App\Infrastructure\Mail\SmtpMailService;
use App\Infrastructure\Media\GdMediaService;
use App\Infrastructure\Security\RateLimiter;
use App\Infrastructure\Storage\JsonHelper;
use App\Infrastructure\Storage\LocalImageStorage;
use App\Infrastructure\Storage\MySqlBookmarkRepository;
use App\Infrastructure\Storage\MySqlChapterRepository;
use App\Infrastructure\Storage\MySqlCharacterGroupRepository;
use App\Infrastructure\Storage\MySqlCharacterRepository;
use App\Infrastructure\Storage\MySqlComicRepository;
use App\Infrastructure\Storage\MySqlComicRevisionRepository;
use App\Infrastructure\Storage\MySqlLoginAttemptRepository;
use App\Infrastructure\Storage\MySqlMagicLinkRepository;
use App\Infrastructure\Storage\MySqlMailQueueRepository;
use App\Infrastructure\Storage\MySqlReportRepository;
use App\Infrastructure\Storage\MySqlRoleRepository;
use App\Infrastructure\Storage\MySqlUserRepository;
use App\Infrastructure\System\CurlRemoteResourceProber;
use App\Infrastructure\System\FileRouteCache;
use App\Infrastructure\System\GoogleAnalyticsClient;
use App\Infrastructure\System\LocalAssetHelper;
use App\Infrastructure\System\StaticSiteGenerator;
use App\Infrastructure\System\SystemBackupService;
use App\Infrastructure\System\SystemInfoService;
use App\Infrastructure\Utils\SystemClock;

final class InfrastructureServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // 1. Core / System (Datenbank, Uhrzeit, Logging)
        $container->bind(\PDO::class, function () use ($container): \PDO {
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return PdoFactory::create($config);
        });

        $container->bind(ClockInterface::class, fn (): SystemClock => new SystemClock());

        $container->bind(JsonHelperInterface::class, fn (): JsonHelper => new JsonHelper());

        $container->bind(ImageStorageInterface::class, function () use ($container): LocalImageStorage {
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return new LocalImageStorage($config);
        });

        $container->bind(ErrorLoggerInterface::class, function () use ($container): ErrorLogger {
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return new ErrorLogger($config);
        });

        // 2. Security & Session
        $container->bind(AuthSessionInterface::class, function () use ($container): object {
            $sessionManager = $container->get(SessionManager::class);
            \assert(\is_object($sessionManager));

            return clone $sessionManager;
        });

        $container->bind(RoleRepositoryInterface::class, function () use ($container): MySqlRoleRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);
            $jsonHelper = $container->get(JsonHelperInterface::class);
            \assert($jsonHelper instanceof JsonHelperInterface);

            return new MySqlRoleRepository($pdo, $jsonHelper);
        });

        $container->bind(UserRepositoryInterface::class, function () use ($container): MySqlUserRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlUserRepository($pdo);
        });

        $container->bind(LoginAttemptRepositoryInterface::class, function () use ($container): MySqlLoginAttemptRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlLoginAttemptRepository($pdo);
        });

        $container->bind(RateLimiterInterface::class, function () use ($container): RateLimiter {
            $clock = $container->get(ClockInterface::class);
            \assert($clock instanceof ClockInterface);
            $repo = $container->get(LoginAttemptRepositoryInterface::class);
            \assert($repo instanceof LoginAttemptRepositoryInterface);

            return new RateLimiter($clock, $repo);
        });

        // 3. E-Mail
        $container->bind(MagicLinkRepositoryInterface::class, function () use ($container): MySqlMagicLinkRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlMagicLinkRepository($pdo);
        });

        $container->bind(MailQueueRepositoryInterface::class, function () use ($container): MySqlMailQueueRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);
            $jsonHelper = $container->get(JsonHelperInterface::class);
            \assert($jsonHelper instanceof JsonHelperInterface);

            return new MySqlMailQueueRepository($pdo, $jsonHelper);
        });

        // Mail Services (SMTP als interne Instanz, MailQueue als das Interface für den Rest der App)
        $container->bind('mail.smtp', function () use ($container): SmtpMailService {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return new SmtpMailService($pdo, $config);
        });

        $container->bind(MailLogInterface::class, fn (): mixed => $container->get('mail.smtp'));

        $container->bind(MailServiceInterface::class, function () use ($container): MailQueueService {
            $repo = $container->get(MailQueueRepositoryInterface::class);
            \assert($repo instanceof MailQueueRepositoryInterface);
            $smtp = $container->get('mail.smtp');
            \assert($smtp instanceof MailServiceInterface);

            return new MailQueueService($repo, $smtp);
        });

        // Direkter Mailer für Sofort-Versand ohne Warteschlange
        $container->bind(DirectMailServiceInterface::class, function () use ($container): SmtpMailService {
            $smtp = $container->get('mail.smtp');
            \assert($smtp instanceof SmtpMailService);

            return $smtp;
        });

        // Bookmarks / Lesezeichen
        $container->bind(BookmarkRepositoryInterface::class, function () use ($container): MySqlBookmarkRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlBookmarkRepository($pdo);
        });

        // 4. Domain Repositories (TwoKinds MySQL Persistenz)
        $container->bind(ComicRepositoryInterface::class, function () use ($container): MySqlComicRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlComicRepository($pdo);
        });

        $container->bind(ComicRevisionRepositoryInterface::class, function () use ($container): MySqlComicRevisionRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);
            $clock = $container->get(ClockInterface::class);
            \assert($clock instanceof ClockInterface);
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return new MySqlComicRevisionRepository($pdo, $clock, $config);
        });

        $container->bind(CharacterRepositoryInterface::class, function () use ($container): MySqlCharacterRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlCharacterRepository($pdo);
        });

        $container->bind(CharacterGroupRepositoryInterface::class, function () use ($container): MySqlCharacterGroupRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlCharacterGroupRepository($pdo);
        });

        $container->bind(ReportRepositoryInterface::class, function () use ($container): MySqlReportRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlReportRepository($pdo);
        });

        $container->bind(ChapterRepositoryInterface::class, function () use ($container): MySqlChapterRepository {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);

            return new MySqlChapterRepository($pdo);
        });

        // Backup Service als Infrastruktur-Dienst gebunden
        $container->bind(BackupServiceInterface::class, function () use ($container): SystemBackupService {
            $pdo = $container->get(\PDO::class);
            \assert($pdo instanceof \PDO);
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);
            $json = $container->get(JsonHelperInterface::class);
            \assert($json instanceof JsonHelperInterface);

            return new SystemBackupService($pdo, $config, $json);
        });

        // Andere
        $container->bind(MediaServiceInterface::class, function () use ($container): GdMediaService {
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return new GdMediaService($config);
        });

        $container->bind(RemoteResourceProberInterface::class, fn (): CurlRemoteResourceProber => new CurlRemoteResourceProber());

        $container->bind(SystemInfoInterface::class, function () use ($container): SystemInfoService {
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);
            $json = $container->get(JsonHelperInterface::class);
            \assert($json instanceof JsonHelperInterface);

            return new SystemInfoService($config, $json);
        });

        $container->bind(AssetHelperInterface::class, function () use ($container): LocalAssetHelper {
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return new LocalAssetHelper($config);
        });

        $container->bind(AnalyticsClientInterface::class, function () use ($container): GoogleAnalyticsClient {
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return new GoogleAnalyticsClient($config);
        });

        $container->bind(RouteCacheInterface::class, function () use ($container): FileRouteCache {
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);

            return new FileRouteCache($config);
        });

        // Sitemap und RSS
        $container->bind(SiteGeneratorInterface::class, function () use ($container): StaticSiteGenerator {
            $comicRepo = $container->get(ComicRepositoryInterface::class);
            \assert($comicRepo instanceof ComicRepositoryInterface);
            $chapterRepo = $container->get(ChapterRepositoryInterface::class);
            \assert($chapterRepo instanceof ChapterRepositoryInterface);
            $config = $container->get(ConfigInterface::class);
            \assert($config instanceof ConfigInterface);
            $charRepo = $container->get(CharacterRepositoryInterface::class);
            \assert($charRepo instanceof CharacterRepositoryInterface);

            return new StaticSiteGenerator($comicRepo, $chapterRepo, $config, $charRepo);
        });
    }
}
