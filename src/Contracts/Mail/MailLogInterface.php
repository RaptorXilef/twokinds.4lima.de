<?php

declare(strict_types=1);

namespace App\Contracts\Mail;

interface MailLogInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function loadLogs(): array;

    /**
     * @param array<int, array<string, mixed>> $logs
     */
    public function saveLogs(array $logs, bool $forceSql = false): void;

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function importLogs(array $data, bool $forceSql = false): void;

    /**
     * Für die E-Mail Vorschau
     *
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array;
}
