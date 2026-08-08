<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface SystemUpdaterInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function checkForUpdate(string $currentVersion, bool $force = false): ?array;

    public function performUpdate(string $zipUrl): bool;
}
