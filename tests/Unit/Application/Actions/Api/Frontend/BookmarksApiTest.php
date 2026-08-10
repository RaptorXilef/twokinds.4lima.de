<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Frontend;

use App\Application\Actions\Api\Frontend\SyncBookmarksAction;
use App\Application\Actions\Api\Frontend\ToggleBookmarkAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\Bookmark;
use App\Core\Entity\User;
use App\Core\Service\AuthService;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'frontend');

function setupBookmarkApiTest(mixed $test, bool $isLoggedIn = true, string $userId = 'usr_123'): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
    if ($isLoggedIn) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['auth_hash'] = 'hash123';
        $_SESSION['admin_group'] = 'user';
    }

    $session = new SessionManager(new SystemClock());
    $userRepo = $stub(UserRepositoryInterface::class);

    // User muss gefunden werden, damit AuthService::isLoggedIn() keinen Fehler wirft
    if ($isLoggedIn && !\str_starts_with($userId, 'sys_')) {
        $user = new User($userId, new Username('Test'), new EmailAddress('t@t.de'), 'hash123', 'user', new DateTimeImmutable());
        $userRepo->method('findById')->willReturn($user);
    }

    $auth = new AuthService(
        $stub(ConfigInterface::class),
        $stub(RoleRepositoryInterface::class),
        $stub(RateLimiterInterface::class),
        $session,
        $userRepo,
    );

    $repo = $stub(BookmarkRepositoryInterface::class);

    return new class($auth, $session, $repo) {
        public function __construct(
            public AuthService $auth,
            public SessionManager $session,
            public Stub&BookmarkRepositoryInterface $repo,
        ) {
        }
    };
}

// TOGGLE BOOKMARKS
\it('ToggleBookmarkAction returns 401 if not logged in', function (): void {
    $app = setupBookmarkApiTest($this, false);
    $action = new ToggleBookmarkAction($app->auth, $app->session, $app->repo);
    $res = $action->execute(new ServerRequest());
    \expect($res->statusCode)->toBe(401);
})->covers(ToggleBookmarkAction::class);

\it('ToggleBookmarkAction returns 403 for sys accounts', function (): void {
    $app = setupBookmarkApiTest($this, true, 'sys_admin');
    $action = new ToggleBookmarkAction($app->auth, $app->session, $app->repo);
    $res = $action->execute(new ServerRequest());
    \expect($res->statusCode)->toBe(403);
})->covers(ToggleBookmarkAction::class);

\it('ToggleBookmarkAction returns 400 for invalid action', function (): void {
    $app = setupBookmarkApiTest($this, true, 'usr_123');
    $action = new ToggleBookmarkAction($app->auth, $app->session, $app->repo);
    $res = $action->execute(new ServerRequest(post: ['comic_id' => '20260810', 'bookmark_action' => 'invalid']));
    \expect($res->statusCode)->toBe(400);
})->covers(ToggleBookmarkAction::class);

\it('ToggleBookmarkAction adds bookmark', function (): void {
    $app = setupBookmarkApiTest($this, true, 'usr_123');
    $action = new ToggleBookmarkAction($app->auth, $app->session, $app->repo);
    $res = $action->execute(new ServerRequest(post: ['comic_id' => '20260810', 'bookmark_action' => 'add']));
    \expect($res->statusCode)->toBe(200)->and($res->data['message'])->toContain('gespeichert');
})->covers(ToggleBookmarkAction::class);

// SYNC BOOKMARKS
\it('SyncBookmarksAction returns 401 if not logged in', function (): void {
    $app = setupBookmarkApiTest($this, false);
    $action = new SyncBookmarksAction($app->auth, $app->session, $app->repo);
    $res = $action->execute(new ServerRequest());
    \expect($res->statusCode)->toBe(401);
})->covers(SyncBookmarksAction::class);

\it('SyncBookmarksAction resolves merge correctly', function (): void {
    $app = setupBookmarkApiTest($this, true, 'usr_123');
    $app->repo->method('findByUser')->willReturn([
        new Bookmark('usr_123', '20260810', new DateTimeImmutable()),
    ]);

    $action = new SyncBookmarksAction($app->auth, $app->session, $app->repo);
    $res = $action->execute(new ServerRequest(post: ['local_ids' => '["20260811"]', 'resolution' => 'merge']));

    \expect($res->statusCode)->toBe(200)
        ->and($res->data['status'])->toBe('resolved')
        ->and($res->data['final_ids'])->toHaveCount(2);
})->covers(SyncBookmarksAction::class);
