<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\MailJob;

interface MailQueueRepositoryInterface
{
    public function enqueue(MailJob $job): void;

    /**
     * @param array<int, string> $allowedTemplates Array Parameter
     */
    public function processBatch(int $limit, callable $processor, array $allowedTemplates = []): int;

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function import(array $data): void;

    /**
     * Für das Dashboard und die E-Mail Vorschau
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllQueue(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array;

    public function delete(string $id): void;
}
