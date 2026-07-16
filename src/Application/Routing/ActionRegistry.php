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

        $this->routes = $this->scanDirectory(\rtrim((string) $this->config->get('root_path'), '/\\') . '/src/Application/Actions');

        $cacheDir = \dirname($cacheFile);
        if (! \is_dir($cacheDir)) {
            @\mkdir($cacheDir, 0o755, true);
        }

        \file_put_contents($cacheFile, '<?php return ' . \var_export($this->routes, true) . ';', \LOCK_EX);
    }

    private function scanDirectory(string $dir): array
    {
        $map = [];
        if (! \is_dir($dir)) {
            return $map;
        }

        foreach (\glob($dir . '/*.php') as $file) {
            $className = 'App\\Application\\Actions\\' . \basename($file, '.php');
            if (\class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                foreach ($reflection->getAttributes(ActionRoute::class) as $attribute) {
                    $route            = $attribute->newInstance();
                    $map[$route->key] = $className;
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
