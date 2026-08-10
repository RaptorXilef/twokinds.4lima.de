<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\AuthService;

\uses()->group('core', 'service', 'auth');

\test('backdoor login fails completely when disabled in config', function (): void {
    // Arrange
    $configMock = $this->createMock(ConfigInterface::class);
    $configMock->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
        return match ($key) {
            'disable_backdoor' => true,
            'disable_superadmin' => false,
            'superadmin' => null,
            default => $default,
        };
    });

    $rateLimiterMock = $this->createMock(RateLimiterInterface::class);
    $rateLimiterMock->expects($this->once())->method('isBlocked')->willReturn(false);
    $rateLimiterMock->expects($this->once())->method('recordFailedAttempt');

    $userRepoMock = $this->createStub(UserRepositoryInterface::class);
    $userRepoMock->method('findByEmail')->willReturn(null);
    $userRepoMock->method('findByUsername')->willReturn(null);

    $authService = new AuthService(
        $configMock,
        $this->createStub(RoleRepositoryInterface::class),
        $rateLimiterMock,
        $this->createStub(AuthSessionInterface::class),
        $userRepoMock,
    );

    // Act
    $result = $authService->login('RaptorXilef', 'anypassword');

    // Assert
    \expect($result)->toBeFalse();
})->covers(AuthService::class);

\test('superadmin dev account login fails completely when disabled in config', function (): void {
    // Arrange
    $configMock = $this->createMock(ConfigInterface::class);
    $configMock->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
        return match ($key) {
            'disable_backdoor' => false,
            'backdoor' => null,
            'disable_superadmin' => true,
            default => $default,
        };
    });

    $rateLimiterMock = $this->createMock(RateLimiterInterface::class);
    $rateLimiterMock->expects($this->once())->method('isBlocked')->willReturn(false);
    $rateLimiterMock->expects($this->once())->method('recordFailedAttempt');

    $userRepoMock = $this->createStub(UserRepositoryInterface::class);
    $userRepoMock->method('findByEmail')->willReturn(null);
    $userRepoMock->method('findByUsername')->willReturn(null);

    $authService = new AuthService(
        $configMock,
        $this->createStub(RoleRepositoryInterface::class),
        $rateLimiterMock,
        $this->createStub(AuthSessionInterface::class),
        $userRepoMock,
    );

    // Act
    $result = $authService->login('Systembetreuer', 'anypassword');

    // Assert
    \expect($result)->toBeFalse();
})->covers(AuthService::class);
