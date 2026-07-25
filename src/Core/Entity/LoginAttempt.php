<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\IpAddress;

final readonly class LoginAttempt
{
    public function __construct(
        public IpAddress $ipAddress,
        public int $attempts,
        public \DateTimeImmutable $lastAttempt,
    ) {
    }
}
