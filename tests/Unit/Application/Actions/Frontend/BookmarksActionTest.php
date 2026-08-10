<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\BookmarksAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Core\Service\AuthService;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'frontend', 'bookmarks');

function setupBookmarksPageTest(mixed $test, bool $isLoggedIn = true): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
    if ($isLoggedIn) {
        $_SESSION['user_id'] = 'usr_123';
        $_SESSION['auth_hash'] = 'hash';
    }

    $session = new SessionManager(new SystemClock());
    $comicRepo = $stub(ComicRepositoryInterface::class);
    $bmRepo = $stub(BookmarkRepositoryInterface::class);

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('http://localhost');
    $config->method('get')->willReturnMap([['root_path', null, \realpath(__DIR__ . '/../../../../../')]]);

    $auth = new AuthService(
        $config,
        $stub(RoleRepositoryInterface::class),
        $stub(RateLimiterInterface::class),
        $session,
        $stub(UserRepositoryInterface::class),
    );

    $renderer = new TemplateRenderer(
        $config,
        $stub(ImageStorageInterface::class),
        $stub(JsonHelperInterface::class),
        $session,
        $stub(SystemInfoInterface::class),
        $stub(AssetHelperInterface::class),
    );

    return new class($renderer, $comicRepo, $bmRepo, $auth, $session) {
        public function __construct(
            public TemplateRenderer $renderer,
            public Stub&ComicRepositoryInterface $comicRepo,
            public Stub&BookmarkRepositoryInterface $bmRepo,
            public AuthService $auth,
            public SessionManager $session,
        ) {
        }
    };
}

\it('renders bookmarks page for guest users', function (): void {
    $app = setupBookmarksPageTest($this, false);
    $action = new BookmarksAction($app->renderer, $app->comicRepo, $app->bmRepo, $app->auth, $app->session);

    $res = $action->execute(new ServerRequest());
    \expect($res)->toBeInstanceOf(HtmlResponse::class)
        ->and($res->statusCode)->toBe(200)
        ->and($res->html)->toContain('Meine Lesezeichen');
})->covers(BookmarksAction::class);

\it('renders bookmarks page and fetches cloud bookmarks for logged in users', function (): void {
    $app = setupBookmarksPageTest($this, true);

    // We expect findByUser to be called because the user is logged in
    $app->bmRepo->method('findByUser')->willReturn([]);

    $action = new BookmarksAction($app->renderer, $app->comicRepo, $app->bmRepo, $app->auth, $app->session);
    $res = $action->execute(new ServerRequest());

    \expect($res->statusCode)->toBe(200);
})->covers(BookmarksAction::class);
