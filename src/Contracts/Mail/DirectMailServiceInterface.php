<?php

declare(strict_types=1);

namespace App\Contracts\Mail;

interface DirectMailServiceInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function sendTemplate(string $recipient, string $subject, string $template, array $data): bool|string;
}
