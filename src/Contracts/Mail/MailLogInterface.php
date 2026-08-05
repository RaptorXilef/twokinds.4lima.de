<?php

declare(strict_types=1);

namespace App\Contracts\Mail;

interface MailLogInterface
{
    public function loadLogs(): array;

    public function saveLogs(array $logs, bool $forceSql = false): void;

    public function importLogs(array $data, bool $forceSql = false): void;

    // Für die E-Mail Vorschau
    public function findById(string $id): ?array;
}
