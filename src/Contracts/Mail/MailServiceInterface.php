<?php

declare(strict_types=1);

namespace App\Contracts\Mail;

interface MailServiceInterface
{
    public function sendTemplate(string $recipient, string $subject, string $template, array $data): bool|string;

    // Array Parameter für erlaubte Templates
    public function processQueue(int $limit = 5, array $allowedTemplates = []): int;
}
