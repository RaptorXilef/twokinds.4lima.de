<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

interface MagicLinkRepositoryInterface
{
    public function loadAll(): array;

    public function saveAll(array $links, bool $forceSql = false): void;

    public function import(array $data): void;
}
