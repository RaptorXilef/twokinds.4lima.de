<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\SaveUserAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Service\AuthService;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'admin', 'users');

function setupSaveUserTest(mixed $test, bool $hasPerm = true): object
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
    $config = $stub(ConfigInterface::class);
    $userRepo = $stub(UserRepositoryInterface::class);

    $auth = new AuthService(
        $config,
        $stub(RoleRepositoryInterface::class),
        $stub(RateLimiterInterface::class),
        $session,
        $userRepo,
    );

    return new class($auth, $userRepo) {
        public function __construct(
            public AuthService $auth,
            public Stub&UserRepositoryInterface $userRepo,
        ) {
        }
    };
}

\it('SaveUserAction 403 on no permission', function (): void {
    $app = setupSaveUserTest($this, false);
    $action = new SaveUserAction($app->auth, $app->userRepo);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(SaveUserAction::class);

\it('SaveUserAction 400 on missing email or username', function (): void {
    $app = setupSaveUserTest($this, true);
    $action = new SaveUserAction($app->auth, $app->userRepo);
    \expect($action->execute(new ServerRequest(post: ['username' => 'Test']))->statusCode)->toBe(400);
})->covers(SaveUserAction::class);

\it('SaveUserAction 400 on password mismatch', function (): void {
    $app = setupSaveUserTest($this, true);
    $action = new SaveUserAction($app->auth, $app->userRepo);
    $res = $action->execute(new ServerRequest(post: [
        'username' => 'TestUser', 'email' => 't@t.de', 'password' => '12345678', 'password_confirm' => '87654321',
    ]));
    \expect($res->statusCode)->toBe(400)->and($res->data['error'])->toContain('stimmen nicht überein');
})->covers(SaveUserAction::class);

\it('SaveUserAction 400 on duplicate email', function (): void {
    $app = setupSaveUserTest($this, true);
    $app->userRepo->method('findByEmail')->willReturn(
        new User('other_usr', new Username('Other'), new EmailAddress('t@t.de'), 'hash', 'user', new DateTimeImmutable()),
    );

    $action = new SaveUserAction($app->auth, $app->userRepo);
    $res = $action->execute(new ServerRequest(post: [
        'username' => 'TestUser', 'email' => 't@t.de', 'password' => '12345678', 'password_confirm' => '12345678',
    ]));

    \expect($res->statusCode)->toBe(400)->and($res->data['error'])->toContain('bereits verwendet');
})->covers(SaveUserAction::class);

\it('SaveUserAction 200 saves valid new user', function (): void {
    $app = setupSaveUserTest($this, true);
    $action = new SaveUserAction($app->auth, $app->userRepo);
    $res = $action->execute(new ServerRequest(post: [
        'username' => 'TestUser', 'email' => 't@t.de', 'password' => '12345678', 'password_confirm' => '12345678',
    ]));
    \expect($res->statusCode)->toBe(200);
})->covers(SaveUserAction::class);
