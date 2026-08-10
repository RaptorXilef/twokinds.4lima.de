<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\CreateBackupAction;
use App\Application\Actions\Api\Admin\DeleteBackupAction;
use App\Application\Actions\Api\Admin\ListBackupsAction;
use App\Application\Actions\Api\Admin\RestoreBackupAction;
use App\Application\Http\ServerRequest;
use App\Contracts\System\BackupServiceInterface;
use App\Core\Service\AuthService;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'admin');

function setupBackupApiTest(mixed $test, bool $hasPerm = true): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);
    $auth = $stub(AuthService::class);
    $auth->method('hasPermission')->willReturn($hasPerm);

    return new class($auth, $stub(BackupServiceInterface::class)) {
        public function __construct(
            public Stub&AuthService $auth,
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
