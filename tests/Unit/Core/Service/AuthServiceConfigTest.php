<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\AuthService;

// \uses() haben wir hier für Infection weggelassen!

\test('backdoor login fails completely when disabled in config', function (): void {
    // Arrange
    // HIER WURDE createStub() STATT createMock() VERWENDET
    $configStub = $this->createStub(ConfigInterface::class);
    $configStub->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
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

    $userRepoStub = $this->createStub(UserRepositoryInterface::class);
    $userRepoStub->method('findByEmail')->willReturn(null);
    $userRepoStub->method('findByUsername')->willReturn(null);

    $authService = new AuthService(
        $configStub,
        $this->createStub(RoleRepositoryInterface::class),
        $rateLimiterMock,
        $this->createStub(AuthSessionInterface::class),
        $userRepoStub,
    );

    // Act
    $result = $authService->login('RaptorXilef', 'anypassword');

    // Assert
    \expect($result)->toBeFalse();
})->covers(AuthService::class);

\test('superadmin dev account login fails completely when disabled in config', function (): void {
    // Arrange
    // HIER WURDE createStub() STATT createMock() VERWENDET
    $configStub = $this->createStub(ConfigInterface::class);
    $configStub->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
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

    $userRepoStub = $this->createStub(UserRepositoryInterface::class);
    $userRepoStub->method('findByEmail')->willReturn(null);
    $userRepoStub->method('findByUsername')->willReturn(null);

    $authService = new AuthService(
        $configStub,
        $this->createStub(RoleRepositoryInterface::class),
        $rateLimiterMock,
        $this->createStub(AuthSessionInterface::class),
        $userRepoStub,
    );

    // Act
    $result = $authService->login('Systembetreuer', 'anypassword');

    // Assert
    \expect($result)->toBeFalse();
})->covers(AuthService::class);
