<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\DeleteRoleAction;
use App\Application\Actions\Api\Admin\DeleteUserAction;
use App\Application\Actions\Api\Admin\SaveRoleAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\AuthService;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'admin', 'roles');

function setupRolesApiTest(mixed $test, bool $hasPerm = true): object
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

    return new class($auth, $stub(RoleRepositoryInterface::class), $stub(UserRepositoryInterface::class), $stub(BookmarkRepositoryInterface::class)) {
        public function __construct(
            public AuthService $auth,
            public Stub&RoleRepositoryInterface $roleRepo,
            public Stub&UserRepositoryInterface $userRepo,
            public Stub&BookmarkRepositoryInterface $bookmarkRepo,
        ) {
        }
    };
}

// ROLES
\it('DeleteRoleAction 403 on no permission', function (): void {
    $app = setupRolesApiTest($this, false);
    $action = new DeleteRoleAction($app->auth, $app->roleRepo);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(DeleteRoleAction::class);

\it('DeleteRoleAction 403 on system roles', function (): void {
    $app = setupRolesApiTest($this, true);
    $action = new DeleteRoleAction($app->auth, $app->roleRepo);
    \expect($action->execute(new ServerRequest(post: ['role_id' => 'admin']))->statusCode)->toBe(403)
        ->and($action->execute(new ServerRequest(post: ['role_id' => 'user']))->statusCode)->toBe(403);
})->covers(DeleteRoleAction::class);

\it('DeleteRoleAction 200 on valid custom role', function (): void {
    $app = setupRolesApiTest($this, true);
    $action = new DeleteRoleAction($app->auth, $app->roleRepo);
    \expect($action->execute(new ServerRequest(post: ['role_id' => 'editor']))->statusCode)->toBe(200);
})->covers(DeleteRoleAction::class);

\it('SaveRoleAction 400 on missing name', function (): void {
    $app = setupRolesApiTest($this, true);
    $action = new SaveRoleAction($app->auth, $app->roleRepo);
    \expect($action->execute(new ServerRequest(post: ['role_id' => 'editor', 'name' => '']))->statusCode)->toBe(400);
})->covers(SaveRoleAction::class);

\it('SaveRoleAction 200 on success', function (): void {
    $app = setupRolesApiTest($this, true);
    $action = new SaveRoleAction($app->auth, $app->roleRepo);
    $res = $action->execute(new ServerRequest(post: ['role_id' => 'editor', 'name' => 'Redakteur', 'permissions' => '["comics.edit"]']));
    \expect($res->statusCode)->toBe(200);
})->covers(SaveRoleAction::class);

// USERS
\it('DeleteUserAction 403 on no permission', function (): void {
    $app = setupRolesApiTest($this, false);
    $action = new DeleteUserAction($app->auth, $app->userRepo, $app->bookmarkRepo);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(DeleteUserAction::class);

\it('DeleteUserAction 403 on trying to delete self', function (): void {
    $app = setupRolesApiTest($this, true);
    $action = new DeleteUserAction($app->auth, $app->userRepo, $app->bookmarkRepo);
    // Logged in user is sys_admin
    \expect($action->execute(new ServerRequest(post: ['user_id' => 'sys_admin']))->statusCode)->toBe(403);
})->covers(DeleteUserAction::class);

\it('DeleteUserAction 200 on success', function (): void {
    $app = setupRolesApiTest($this, true);
    $action = new DeleteUserAction($app->auth, $app->userRepo, $app->bookmarkRepo);
    \expect($action->execute(new ServerRequest(post: ['user_id' => 'usr_999']))->statusCode)->toBe(200);
})->covers(DeleteUserAction::class);
