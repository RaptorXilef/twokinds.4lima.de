<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\DeleteChapterAction;
use App\Application\Actions\Api\Admin\SaveChapterAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\AuthService;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'admin');

function setupChapterApiTest(mixed $test, bool $hasPerm = true): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];

    if ($hasPerm) {
        $_SESSION['user_id'] = 'sys_admin';
    } else {
        $_SESSION['user_id'] = 'usr_1';
        $_SESSION['auth_hash'] = 'hash';
        $_SESSION['compiled_permissions'] = [];
    }

    $session = new SessionManager(new SystemClock());
    $auth = new AuthService(
        $stub(ConfigInterface::class),
        $stub(RoleRepositoryInterface::class),
        $stub(RateLimiterInterface::class),
        $session,
        $stub(UserRepositoryInterface::class),
    );

    return new class($auth, $stub(ChapterRepositoryInterface::class)) {
        public function __construct(
            public AuthService $auth,
            public Stub&ChapterRepositoryInterface $repo,
        ) {
        }
    };
}

\it('DeleteChapterAction 403 on no permission', function (): void {
    $app = setupChapterApiTest($this, false);
    $action = new DeleteChapterAction($app->repo, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(DeleteChapterAction::class);

\it('DeleteChapterAction 400 on missing id', function (): void {
    $app = setupChapterApiTest($this, true);
    $action = new DeleteChapterAction($app->repo, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(DeleteChapterAction::class);

\it('SaveChapterAction 400 on missing data', function (): void {
    $app = setupChapterApiTest($this, true);
    $action = new SaveChapterAction($app->repo, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(SaveChapterAction::class);

\it('SaveChapterAction 200 on valid data', function (): void {
    $app = setupChapterApiTest($this, true);
    $action = new SaveChapterAction($app->repo, $app->auth);
    $res = $action->execute(new ServerRequest(post: ['chapter_id' => '1', 'title' => 'Prologue']));
    \expect($res->statusCode)->toBe(200);
})->covers(SaveChapterAction::class);
