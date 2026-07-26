<?php

declare(strict_types=1);

namespace App\Contracts\Config;

interface ConfigInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function getBaseUrl(): string;

    public function isTestMode(): bool;

    public function getMailSettings(): array;

    public function getStoragePath(string $fileName): string;
}
