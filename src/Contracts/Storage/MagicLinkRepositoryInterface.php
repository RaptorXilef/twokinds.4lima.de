<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\MagicLink;

interface MagicLinkRepositoryInterface
{
    /**
     * @return array<string, MagicLink>
     */
    public function loadAll(): array;

    /**
     * @param array<string, MagicLink> $links
     */
    public function saveAll(array $links, bool $forceSql = false): void;

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function import(array $data): void;

    // Löscht abgelaufene Tokens direkt per SQL
    public function deleteExpired(): int;
}
