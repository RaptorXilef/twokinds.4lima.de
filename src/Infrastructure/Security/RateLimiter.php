<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\LoginAttemptRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\LoginAttempt;
use App\Core\ValueObject\IpAddress;

final readonly class RateLimiter implements RateLimiterInterface
{
    private const int MAX_ATTEMPTS = 5;
    private const int LOCKOUT_MINUTES = 15;

    public function __construct(
        private ClockInterface $clock,
        private LoginAttemptRepositoryInterface $repository,
    ) {
    }

    public function isBlocked(string $ip): bool
    {
        $attempt = $this->repository->findByIp($ip);
        if (!$attempt instanceof LoginAttempt) {
            return false;
        }

        $now = $this->clock->now();
        $diffMinutes = ($now->getTimestamp() - $attempt->lastAttempt->getTimestamp()) / 60;

        if ($diffMinutes > self::LOCKOUT_MINUTES) {
            $this->clearAttempts($ip);

            return false;
        }

        return $attempt->attempts >= self::MAX_ATTEMPTS;
    }

    public function recordFailedAttempt(string $ip): void
    {
        $this->repository->deleteOlderThan(self::LOCKOUT_MINUTES);

        $attempt = $this->repository->findByIp($ip);
        $attempts = $attempt instanceof LoginAttempt ? $attempt->attempts + 1 : 1;

        $safeIp = $ip === 'unknown' || $ip === '' ? '0.0.0.0' : $ip;

        $this->repository->save(new LoginAttempt(
            new IpAddress($safeIp),
            $attempts,
            $this->clock->now(),
        ));
    }

    public function clearAttempts(string $ip): void
    {
        $this->repository->deleteByIp($ip);
    }
}
