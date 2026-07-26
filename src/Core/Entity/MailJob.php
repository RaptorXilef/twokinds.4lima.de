<?php

declare(strict_types=1);

namespace App\Core\Entity;

final readonly class MailJob
{
    public function __construct(
        public string $id,
        public string $recipient,
        public string $subject,
        public string $template, // <--- Kein TemplateKey mehr! Nur String.
        public array $data,
        public int $attempts,
        public int $priority,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
