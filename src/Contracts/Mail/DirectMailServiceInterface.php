<?php

declare(strict_types=1);

namespace App\Contracts\Mail;

interface DirectMailServiceInterface
{
    public function sendTemplate(string $recipient, string $subject, string $template, array $data): bool|string;
}
