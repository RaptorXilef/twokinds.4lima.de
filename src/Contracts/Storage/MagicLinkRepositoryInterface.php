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

    public function saveAll(array $links, bool $forceSql = false): void;

    public function import(array $data): void;
}
