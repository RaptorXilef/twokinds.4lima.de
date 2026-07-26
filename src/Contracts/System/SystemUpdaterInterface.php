<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface SystemUpdaterInterface
{
    public function checkForUpdate(string $currentVersion, bool $force = false): ?array;

    public function performUpdate(string $zipUrl): bool;
}
