<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\DeleteComicMediaAction;
use App\Application\Actions\Api\Admin\DeleteMediaAction;
use App\Application\Actions\Api\Admin\ListComicMediaAction;
use App\Application\Actions\Api\Admin\MediaListAction;
use App\Application\Actions\Api\Admin\UploadMediaAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Core\Service\AuthService;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'admin', 'media');

function setupMediaApiTest(mixed $test, bool $hasPerm = true): object
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

    $storage = $stub(ImageStorageInterface::class);
    $mediaService = $stub(MediaServiceInterface::class);

    return new class($auth, $storage, $mediaService) {
        public function __construct(
            public AuthService $auth,
            public Stub&ImageStorageInterface $storage,
            public Stub&MediaServiceInterface $mediaService,
        ) {
        }
    };
}

\it('MediaListAction 403 on no permission', function (): void {
    $app = setupMediaApiTest($this, false);
    $action = new MediaListAction($app->auth, $app->storage);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(MediaListAction::class);

\it('MediaListAction 200 on success', function (): void {
    $app = setupMediaApiTest($this, true);
    $action = new MediaListAction($app->auth, $app->storage);
    \expect($action->execute(new ServerRequest(get: ['folder' => 'profiles']))->statusCode)->toBe(200);
})->covers(MediaListAction::class);

\it('DeleteMediaAction 400 if filename is missing', function (): void {
    $app = setupMediaApiTest($this, true);
    $action = new DeleteMediaAction($app->storage, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(404);
})->covers(DeleteMediaAction::class);

\it('UploadMediaAction 400 if no files uploaded', function (): void {
    $app = setupMediaApiTest($this, true);
    $action = new UploadMediaAction($app->mediaService, $app->auth);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(UploadMediaAction::class);

\it('ListComicMediaAction 200 on success', function (): void {
    $app = setupMediaApiTest($this, true);
    $action = new ListComicMediaAction($app->auth, $app->storage);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(200);
})->covers(ListComicMediaAction::class);

\it('DeleteComicMediaAction 404 if files do not exist', function (): void {
    $app = setupMediaApiTest($this, true);
    $app->storage->method('deleteComicMedia')->willReturn(0);
    $action = new DeleteComicMediaAction($app->storage, $app->auth);

    $res = $action->execute(new ServerRequest(post: ['comic_id' => '20260810']));
    \expect($res->statusCode)->toBe(404);
})->covers(DeleteComicMediaAction::class);
