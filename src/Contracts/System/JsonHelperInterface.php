<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface JsonHelperInterface
{
    /**
     * @return array<array-key, mixed>
     */
    public function read(string $path): array;

    /**
     * @return array<array-key, mixed>
     */
    public function decode(string $json): array;
}
