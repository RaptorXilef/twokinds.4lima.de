<?php

declare(strict_types=1);

namespace App\Application\Routing;

use App\Application\Attribute\ActionRoute;
use App\Contracts\Config\ConfigInterface;

final class ActionRegistry
{
    private array $routes = [];

    public function __construct(private ConfigInterface $config)
    {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $cacheFile = $this->config->getStoragePath('cache/routes.php');

        // Im Dev-Modus scannen wir immer live, produktiv wird gecacht (pfeilschnell!)
        if (\file_exists($cacheFile) && ! $this->config->get('admin_dev_mode', false)) {
            $this->routes = require $cacheFile;

            return;
        }

        $baseDir      = \rtrim((string) $this->config->get('root_path'), '/\\') . '/src/Application/Actions';
        $this->routes = $this->scanDirectoryRecursively($baseDir);

        $cacheDir = \dirname($cacheFile);
        if (! \is_dir($cacheDir)) {
            @\mkdir($cacheDir, 0o755, true);
        }

        \file_put_contents($cacheFile, '<?php return ' . \var_export($this->routes, true) . ';', \LOCK_EX);
    }

    private function scanDirectoryRecursively(string $dir): array
    {
        $map = [];
        if (! \is_dir($dir)) {
            return $map;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = \str_replace($dir . \DIRECTORY_SEPARATOR, '', $file->getPathname());
                $classSuffix  = \str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);
                $className    = 'App\\Application\\Actions\\' . $classSuffix;

                if (\class_exists($className)) {
                    $reflection = new \ReflectionClass($className);
                    foreach ($reflection->getAttributes(ActionRoute::class) as $attribute) {
                        $route            = $attribute->newInstance();
                        $map[$route->key] = $className;
                    }
                }
            }
        }

        return $map;
    }

    public function getActionClass(string $key): ?string
    {
        return $this->routes[$key] ?? null;
    }
}
