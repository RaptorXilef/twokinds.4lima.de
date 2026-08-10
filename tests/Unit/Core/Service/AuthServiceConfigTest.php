<?php

declare(strict_types=1);

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\AuthService;

\test('backdoor login fails completely when disabled in config', function (): void {
    // Arrange
    $configMock = \Mockery::mock(ConfigInterface::class);
    $configMock->shouldReceive('get')->with('disable_backdoor', false)->andReturn(true);
    $configMock->shouldReceive('get')->with('disable_superadmin', false)->andReturn(false);
    $configMock->shouldReceive('get')->with('superadmin')->andReturn(null);

    $rateLimiterMock = \Mockery::mock(RateLimiterInterface::class);
    $rateLimiterMock->shouldReceive('isBlocked')->andReturn(false);
    $rateLimiterMock->shouldReceive('recordFailedAttempt')->once();

    $userRepoMock = \Mockery::mock(UserRepositoryInterface::class);
    $userRepoMock->shouldReceive('findByEmail')->andReturn(null);
    $userRepoMock->shouldReceive('findByUsername')->andReturn(null);

    $authService = new AuthService(
        $configMock,
        \Mockery::mock(RoleRepositoryInterface::class),
        $rateLimiterMock,
        \Mockery::mock(AuthSessionInterface::class),
        $userRepoMock,
    );

    // Act
    $result = $authService->login('RaptorXilef', 'anypassword');

    // Assert
    \expect($result)->toBeFalse();
});

\test('superadmin dev account login fails completely when disabled in config', function (): void {
    // Arrange
    $configMock = \Mockery::mock(ConfigInterface::class);
    $configMock->shouldReceive('get')->with('disable_backdoor', false)->andReturn(false);
    $configMock->shouldReceive('get')->with('backdoor')->andReturn(null);
    $configMock->shouldReceive('get')->with('disable_superadmin', false)->andReturn(true);

    $rateLimiterMock = \Mockery::mock(RateLimiterInterface::class);
    $rateLimiterMock->shouldReceive('isBlocked')->andReturn(false);
    $rateLimiterMock->shouldReceive('recordFailedAttempt')->once();

    $userRepoMock = \Mockery::mock(UserRepositoryInterface::class);
    $userRepoMock->shouldReceive('findByEmail')->andReturn(null);
    $userRepoMock->shouldReceive('findByUsername')->andReturn(null);

    $authService = new AuthService(
        $configMock,
        \Mockery::mock(RoleRepositoryInterface::class),
        $rateLimiterMock,
        \Mockery::mock(AuthSessionInterface::class),
        $userRepoMock,
    );

    // Act
    $result = $authService->login('Systembetreuer', 'anypassword');

    // Assert
    \expect($result)->toBeFalse();
});
