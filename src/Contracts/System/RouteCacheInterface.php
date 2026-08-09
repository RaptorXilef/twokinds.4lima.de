<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface RouteCacheInterface
{
    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @return array{exact: array<string, array<string, array{class: string, auth: bool}>>, dynamic: array<string, array<string, array{class: string, auth: bool}>>}|null
     */
    public function load(): ?array;

    /**
     * @param array{exact: array<string, array<string, array{class: string, auth: bool}>>, dynamic: array<string, array<string, array{class: string, auth: bool}>>} $routes
     */
    public function save(array $routes): void;
    // phpcs:enable Generic.Files.LineLength.TooLong

    public function clearOld(): void;
}
