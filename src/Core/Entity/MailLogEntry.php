<?php

declare(strict_types=1);

namespace App\Core\Entity;

final readonly class MailLogEntry
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $id,
        public \DateTimeImmutable $timestamp,
        public string $recipient,
        public string $subject,
        public string $template,
        public string $status,
        public array $data,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status === 'Erfolg';
    }
}
