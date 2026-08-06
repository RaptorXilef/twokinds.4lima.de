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
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\BackupServiceInterface;
use App\Contracts\System\ErrorLoggerInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Contracts\System\RemoteResourceProberInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Service\SiteGeneratorService;
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
use App\Infrastructure\System\LocalAssetHelper;
use App\Infrastructure\System\SystemBackupService;
use App\Infrastructure\System\SystemInfoService;
use App\Infrastructure\Utils\SystemClock;

final class InfrastructureServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // 1. Core / System (Datenbank, Uhrzeit, Logging)
        $container->bind(\PDO::class, fn (): \PDO => PdoFactory::create(
            $container->get(ConfigInterface::class),
        ));

        $container->bind(ClockInterface::class, fn (): SystemClock => new SystemClock());

        $container->bind(JsonHelperInterface::class, fn (): JsonHelper => new JsonHelper());

        $container->bind(ImageStorageInterface::class, fn (): LocalImageStorage => new LocalImageStorage(
            $container->get(ConfigInterface::class),
        ));

        $container->bind(ErrorLoggerInterface::class, fn (): ErrorLogger => new ErrorLogger(
            $container->get(ConfigInterface::class),
        ));

        // 2. Security & Session
        $container->bind(AuthSessionInterface::class, fn (): object => clone $container->get(SessionManager::class));

        $container->bind(RoleRepositoryInterface::class, fn (): MySqlRoleRepository => new MySqlRoleRepository(
            $container->get(\PDO::class),
            $container->get(JsonHelperInterface::class),
        ));

        $container->bind(UserRepositoryInterface::class, fn (): MySqlUserRepository => new MySqlUserRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(LoginAttemptRepositoryInterface::class, fn (): MySqlLoginAttemptRepository => new MySqlLoginAttemptRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(RateLimiterInterface::class, fn (): RateLimiter => new RateLimiter(
            $container->get(ClockInterface::class),
            $container->get(LoginAttemptRepositoryInterface::class),
        ));

        // 3. E-Mail
        $container->bind(MagicLinkRepositoryInterface::class, fn (): MySqlMagicLinkRepository => new MySqlMagicLinkRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(MailQueueRepositoryInterface::class, fn (): MySqlMailQueueRepository => new MySqlMailQueueRepository(
            $container->get(\PDO::class),
            $container->get(JsonHelperInterface::class),
        ));

        // Mail Services (SMTP als interne Instanz, MailQueue als das Interface für den Rest der App)
        $container->bind('mail.smtp', fn (): SmtpMailService => new SmtpMailService(
            $container->get(\PDO::class),
            $container->get(ConfigInterface::class),
        ));

        $container->bind(MailLogInterface::class, fn (): mixed => $container->get('mail.smtp'));

        $container->bind(MailServiceInterface::class, fn (): MailQueueService => new MailQueueService(
            $container->get(MailQueueRepositoryInterface::class),
            $container->get('mail.smtp'),
        ));

        // Direkter Mailer für Sofort-Versand ohne Warteschlange
        $container->bind(
            DirectMailServiceInterface::class,
            fn (): SmtpMailService => $container->get('mail.smtp'),
        );

        // Bookmarks / Lesezeichen
        $container->bind(BookmarkRepositoryInterface::class, fn (): MySqlBookmarkRepository => new MySqlBookmarkRepository(
            $container->get(\PDO::class),
        ));

        // 4. Domain Repositories (TwoKinds MySQL Persistenz)
        $container->bind(ComicRepositoryInterface::class, fn (): MySqlComicRepository => new MySqlComicRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(ComicRevisionRepositoryInterface::class, fn (): MySqlComicRevisionRepository => new MySqlComicRevisionRepository(
            $container->get(\PDO::class),
            $container->get(ClockInterface::class),
            $container->get(ConfigInterface::class),
        ));

        $container->bind(CharacterRepositoryInterface::class, fn (): MySqlCharacterRepository => new MySqlCharacterRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(CharacterGroupRepositoryInterface::class, fn (): MySqlCharacterGroupRepository => new MySqlCharacterGroupRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(ReportRepositoryInterface::class, fn (): MySqlReportRepository => new MySqlReportRepository(
            $container->get(\PDO::class),
        ));

        $container->bind(ChapterRepositoryInterface::class, fn (): MySqlChapterRepository => new MySqlChapterRepository(
            $container->get(\PDO::class),
        ));

        // Backup Service als Infrastruktur-Dienst gebunden
        $container->bind(BackupServiceInterface::class, fn (): SystemBackupService => new SystemBackupService(
            $container->get(\PDO::class),
            $container->get(ConfigInterface::class),
            $container->get(JsonHelperInterface::class),
        ));

        // Andere
        $container->bind(MediaServiceInterface::class, fn (): GdMediaService => new GdMediaService(
            $container->get(ConfigInterface::class),
        ));

        $container->bind(RemoteResourceProberInterface::class, fn (): CurlRemoteResourceProber => new CurlRemoteResourceProber());

        $container->bind(SystemInfoInterface::class, fn (): SystemInfoService => new SystemInfoService(
            $container->get(ConfigInterface::class),
            $container->get(JsonHelperInterface::class),
        ));

        $container->bind(AssetHelperInterface::class, fn (): LocalAssetHelper => new LocalAssetHelper(
            $container->get(ConfigInterface::class),
        ));

        // Sitemap und RSS
        $container->bind(SiteGeneratorService::class, fn (): SiteGeneratorService => new SiteGeneratorService(
            $container->get(ComicRepositoryInterface::class),
            $container->get(ChapterRepositoryInterface::class),
            $container->get(ConfigInterface::class),
            $container->get(CharacterRepositoryInterface::class),
        ));
    }
}
