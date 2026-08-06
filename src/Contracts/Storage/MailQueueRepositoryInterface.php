<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\MailJob;

interface MailQueueRepositoryInterface
{
    public function enqueue(MailJob $job): void;

    // Array Parameter
    public function processBatch(int $limit, callable $processor, array $allowedTemplates = []): int;

    public function import(array $data): void;

    // Für das Dashboard und die E-Mail Vorschau
    public function findAllQueue(): array;

    public function findById(string $id): ?array;

    public function delete(string $id): void;
}
