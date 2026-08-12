<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\RouteCacheInterface;

final readonly class FileRouteCache implements RouteCacheInterface
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function load(): ?array
    {
        $cacheFile = $this->config->getStoragePath('cache/routes_v2.php');
        if (\file_exists($cacheFile)) {
            /** @var array{exact: array<string, array<string, array{class: string, auth: bool}>>, dynamic: array<string, array<string, array{class: string, auth: bool}>>} $routes */
            $routes = require $cacheFile;

            return $routes;
        }

        return null;
    }

    public function save(array $routes): void
    {
        $cacheFile = $this->config->getStoragePath('cache/routes_v2.php');
        $cacheDir = \dirname($cacheFile);

        if (!\is_dir($cacheDir)) {
            \mkdir($cacheDir, 0o755, true);
        }

        \file_put_contents($cacheFile, '<?php return ' . \var_export($routes, true) . ';', \LOCK_EX);
    }

    public function clearOld(): void
    {
        $oldCache = $this->config->getStoragePath('cache/routes.php');
        if (!\file_exists($oldCache)) {
            return;
        }

        \unlink($oldCache);
    }

    public function clearAll(): void
    {
        $this->clearOld();
        $v2 = $this->config->getStoragePath('cache/routes_v2.php');
        if (!\file_exists($v2)) {
            return;
        }

        \unlink($v2);
    }
}
