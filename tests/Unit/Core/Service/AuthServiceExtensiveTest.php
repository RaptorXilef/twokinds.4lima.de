<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Service;

use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\AuthService;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('core', 'service', 'auth');

function setupExtensiveAuthTest(mixed $test, array $sessionData = []): AuthService
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = $sessionData;

    $config = $stub(ConfigInterface::class);
    $config->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
        return match ($key) {
            'backdoor' => ['label' => 'System-Inhaber'],
            'admin_dev_mode' => false,
            default => $default,
        };
    });

    return new AuthService(
        $config,
        $stub(RoleRepositoryInterface::class),
        $stub(RateLimiterInterface::class),
        new SessionManager(new SystemClock()),
        $stub(UserRepositoryInterface::class),
    );
}

\it('returns true for isLoggedIn if normal user id exists', function (): void {
    $auth = setupExtensiveAuthTest($this, ['user_id' => 'usr_1']);
    \expect($auth->isLoggedIn())->toBeTrue();
})->covers(AuthService::class);

\it('returns false for isLoggedIn if completely empty', function (): void {
    $auth = setupExtensiveAuthTest($this, []);
    \expect($auth->isLoggedIn())->toBeFalse();
})->covers(AuthService::class);

\it('hasPermission bypasses checks for sys_ users', function (): void {
    $auth = setupExtensiveAuthTest($this, ['user_id' => 'sys_admin']);
    \expect($auth->hasPermission('any.random.permission'))->toBeTrue();
})->covers(AuthService::class);

\it('hasPermission returns true if permission is in compiled session array', function (): void {
    $auth = setupExtensiveAuthTest($this, [
        'user_id' => 'usr_1',
        'compiled_permissions' => ['comics.edit' => true, 'comics.delete' => false],
    ]);

    \expect($auth->hasPermission('comics.edit'))->toBeTrue()
        ->and($auth->hasPermission('comics.delete'))->toBeFalse()
        ->and($auth->hasPermission('unknown'))->toBeFalse();
})->covers(AuthService::class);

\it('generates secure random id with prefix', function (): void {
    $auth = setupExtensiveAuthTest($this);
    $id = $auth->generateId('test_');

    \expect($id)->toStartWith('test_')
        ->and(\strlen($id))->toBeGreaterThan(10);
})->covers(AuthService::class);
