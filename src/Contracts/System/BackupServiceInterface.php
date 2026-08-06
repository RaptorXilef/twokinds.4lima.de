<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface BackupServiceInterface
{
    public function createBackup(?string $tableName = null): string;

    public function restoreBackup(string $filename, int $mode, ?string $tableName = null, ?string $customPassword = null): void;

    public function listBackups(): array;

    public function deleteBackup(string $filename): void;

    public function getAllTables(): array;
}
