<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface JsonHelperInterface
{
    public function read(string $path): array;

    public function decode(string $json): array;
}
