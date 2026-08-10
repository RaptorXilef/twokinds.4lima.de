<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\CreateBackupAction;
use App\Application\Actions\Api\Admin\DeleteBackupAction;
use App\Application\Actions\Api\Admin\ListBackupsAction;
use App\Application\Actions\Api\Admin\RestoreBackupAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\BackupServiceInterface;
use App\Core\Service\AuthService;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'admin');

function setupBackupApiTest(mixed $test, bool $hasPerm = true): object
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

    return new class($auth, $stub(BackupServiceInterface::class)) {
        public function __construct(
            public AuthService $auth,
            public Stub&BackupServiceInterface $service,
        ) {
        }
    };
}

\it('ListBackupsAction 403 on no permission', function (): void {
    $app = setupBackupApiTest($this, false);
    \expect((new ListBackupsAction($app->service, $app->auth))->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(ListBackupsAction::class);

\it('ListBackupsAction 200 on success', function (): void {
    $app = setupBackupApiTest($this, true);
    \expect((new ListBackupsAction($app->service, $app->auth))->execute(new ServerRequest())->statusCode)->toBe(200);
})->covers(ListBackupsAction::class);

\it('CreateBackupAction 403 on no permission', function (): void {
    $app = setupBackupApiTest($this, false);
    \expect((new CreateBackupAction($app->service, $app->auth))->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(CreateBackupAction::class);

\it('DeleteBackupAction 400 on missing filename', function (): void {
    $app = setupBackupApiTest($this, true);
    \expect((new DeleteBackupAction($app->service, $app->auth))->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(DeleteBackupAction::class);

\it('RestoreBackupAction 400 on invalid params', function (): void {
    $app = setupBackupApiTest($this, true);
    \expect((new RestoreBackupAction($app->service, $app->auth))->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(RestoreBackupAction::class);

\it('RestoreBackupAction 200 on success', function (): void {
    $app = setupBackupApiTest($this, true);
    $res = (new RestoreBackupAction($app->service, $app->auth))->execute(new ServerRequest(post: [
        'filename' => 'backup.zip', 'mode' => '1',
    ]));
    \expect($res->statusCode)->toBe(200);
})->covers(RestoreBackupAction::class);
