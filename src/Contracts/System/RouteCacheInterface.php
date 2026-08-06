<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface RouteCacheInterface
{
    public function load(): ?array;

    public function save(array $routes): void;

    public function clearOld(): void;
}
