<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\EmailAddress;

final readonly class MagicLink
{
    public function __construct(
        public string $token,
        public EmailAddress $email,
        public string $code,
        public \DateTimeImmutable $expires,
    ) {
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expires < $now;
    }
}
