<?php

declare(strict_types=1);

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Service\AuthService;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

function setupAuthTest(mixed $test): object
{
    $mock = \Closure::bind(fn (string $c) => $test->createMock($c), $test, $test::class);
    $stub = \Closure::bind(fn (string $c) => $test->createStub($c), $test, $test::class);

    return new class(
        $stub(ConfigInterface::class), // Config ist hier nur ein Stub (liefert Daten)
        $stub(RoleRepositoryInterface::class), // Geändert zu Stub
        $mock(RateLimiterInterface::class),
        $mock(AuthSessionInterface::class),
        $stub(UserRepositoryInterface::class), // Geändert zu Stub
    ) {
        public AuthService $service;

        public function __construct(
            public Stub&ConfigInterface $config,
            public Stub&RoleRepositoryInterface $roleRepo,
            public MockObject&RateLimiterInterface $rateLimiter,
            public MockObject&AuthSessionInterface $session,
            public Stub&UserRepositoryInterface $userRepo,
        ) {
            $this->service = new AuthService(
                $this->config,
                $this->roleRepo,
                $this->rateLimiter,
                $this->session,
                $this->userRepo,
            );
        }
    };
}

\it('throws exception if IP is rate limited', function (): void {
    $app = \setupAuthTest($this);

    $app->session->expects($this->never())->method('setAuthSession');

    $app->rateLimiter->expects($this->once())
        ->method('isBlocked')
        ->with('127.0.0.1')
        ->willReturn(true);

    $app->service->login('user', 'pass', '127.0.0.1');
})->throws(\RuntimeException::class, 'Zu viele fehlgeschlagene Login-Versuche')->covers(AuthService::class);

\it('allows backdoor login bypassing database', function (): void {
    $app = \setupAuthTest($this);

    // Hash für "secret"
    $hash = \password_hash('secret', \PASSWORD_DEFAULT);

    $app->rateLimiter->expects($this->once())->method('isBlocked')->willReturn(false);
    $app->rateLimiter->expects($this->once())->method('clearAttempts');

    $app->config->method('get')->willReturnMap([
        ['backdoor', null, ['user' => 'admin_backdoor', 'pass' => $hash, 'label' => 'System-Inhaber']],
    ]);

    // Wir erwarten, dass die Session gesetzt wird
    $app->session->expects($this->once())
        ->method('setAuthSession')
        ->with('sys_backdoor', 'admin', 'System-Inhaber', null);

    $result = $app->service->login('admin_backdoor', 'secret', '192.168.0.1');
    \expect($result)->toBeTrue();
})->covers(AuthService::class);

\it('fails login with wrong password and triggers rate limit', function (): void {
    $app = \setupAuthTest($this);

    $app->session->expects($this->never())->method('setAuthSession');

    $app->rateLimiter->expects($this->once())->method('isBlocked')->willReturn(false);
    $app->rateLimiter->expects($this->once())
        ->method('recordFailedAttempt')
        ->with('127.0.0.1');

    $user = new User(
        'usr_1',
        new Username('testuser'),
        new EmailAddress('test@test.de'),
        \password_hash('correct_password', \PASSWORD_DEFAULT),
        'user',
        new \DateTimeImmutable(),
    );

    $app->userRepo->method('findByEmail')->willReturn($user);

    $result = $app->service->login('test@test.de', 'WRONG_PASSWORD', '127.0.0.1');
    \expect($result)->toBeFalse();
})->covers(AuthService::class);

\it('prevents login for pending users', function (): void {
    $app = \setupAuthTest($this);

    $app->session->expects($this->never())->method('setAuthSession');

    $app->rateLimiter->expects($this->once())->method('isBlocked')->willReturn(false);
    $app->rateLimiter->expects($this->once())->method('recordFailedAttempt');

    $user = new User(
        'usr_2',
        new Username('pendinguser'),
        new EmailAddress('wait@test.de'),
        \password_hash('mypassword', \PASSWORD_DEFAULT),
        'pending',
        new \DateTimeImmutable(),
    );

    $app->userRepo->method('findByEmail')->willReturn($user);

    $app->service->login('wait@test.de', 'mypassword', '127.0.0.1');
})->throws(\DomainException::class, 'Dein Konto wurde noch nicht bestätigt')->covers(AuthService::class);

\it('successfully logs in a valid user and compiles permissions', function (): void {
    $app = \setupAuthTest($this);

    $app->rateLimiter->expects($this->once())->method('isBlocked')->willReturn(false);
    $app->rateLimiter->expects($this->once())->method('clearAttempts');

    $app->config->method('get')->willReturnCallback(fn ($key, $default = null) => $key === 'structure' ? [] : $default);

    $user = new User(
        'usr_3',
        new Username('validuser'),
        new EmailAddress('valid@test.de'),
        \password_hash('mypassword', \PASSWORD_DEFAULT),
        'editor',
        new \DateTimeImmutable(),
    );

    $app->userRepo->method('findByEmail')->willReturn($user);

    $app->session->expects($this->once())->method('regenerate');
    $app->session->expects($this->once())->method('rotateCsrfToken');
    $app->session->expects($this->once())
        ->method('setAuthSession')
        ->with('usr_3', 'editor', 'validuser', $user->passwordHash);

    $app->roleRepo->method('loadAll')->willReturn([]);

    $result = $app->service->login('valid@test.de', 'mypassword', '10.0.0.1');
    \expect($result)->toBeTrue();
})->covers(AuthService::class);
