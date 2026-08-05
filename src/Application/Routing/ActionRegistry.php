<?php

declare(strict_types=1);

namespace App\Application\Routing;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Contracts\Config\ConfigInterface;

final class ActionRegistry
{
    private array $routes = ['exact' => [], 'dynamic' => []];

    public function __construct(private readonly ConfigInterface $config)
    {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $cacheFile = $this->config->getStoragePath('cache/routes_v2.php');

        // Altes Cache-File löschen falls vorhanden
        @\unlink($this->config->getStoragePath('cache/routes.php'));

        if (\file_exists($cacheFile) && ! $this->config->get('admin_dev_mode', false)) {
            $this->routes = require $cacheFile;

            return;
        }

        $baseDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/src/Application/Actions';
        $this->scanDirectoryRecursively($baseDir);

        $cacheDir = \dirname($cacheFile);
        if (! \is_dir($cacheDir)) {
            @\mkdir($cacheDir, 0o755, true);
        }

        \file_put_contents($cacheFile, '<?php return ' . \var_export($this->routes, true) . ';', \LOCK_EX);
    }

    private function scanDirectoryRecursively(string $dir): void
    {
        if (! \is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = \str_replace($dir . \DIRECTORY_SEPARATOR, '', $file->getPathname());
                $classSuffix  = \str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);
                $className    = 'App\\Application\\Actions\\' . $classSuffix;

                if (\class_exists($className)) {
                    $reflection   = new \ReflectionClass($className);
                    $requiresAuth = ! empty($reflection->getAttributes(RequiresAuth::class));

                    foreach ($reflection->getAttributes(Route::class) as $attribute) {
                        $route = $attribute->newInstance();
                        $this->registerRoute($route->method, $route->path, $className, $requiresAuth);
                    }
                }
            }
        }
    }

    private function registerRoute(string $method, string $path, string $className, bool $requiresAuth): void
    {
        if (\str_contains($path, '{')) {
            // Wandelt "/comic/{id}" in "#^/comic/(?P<id>[^/]+)$#" um
            $regex                                                  = \preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $path);
            $this->routes['dynamic'][$method]['#^' . $regex . '$#'] = ['class' => $className, 'auth' => $requiresAuth];
        } else {
            $this->routes['exact'][$method][$path] = ['class' => $className, 'auth' => $requiresAuth];
        }
    }

    public function match(string $method, string $path): ?array
    {
        // Exact Match
        if (isset($this->routes['exact'][$method][$path])) {
            $r = $this->routes['exact'][$method][$path];

            return ['class' => $r['class'], 'params' => [], 'requiresAuth' => $r['auth']];
        }

        // Dynamic Parameter Match (Regex)
        foreach ($this->routes['dynamic'][$method] ?? [] as $regex => $r) {
            if (\preg_match($regex, $path, $matches)) {
                $params = \array_filter($matches, fn ($k) => \is_string($k), \ARRAY_FILTER_USE_KEY);

                return ['class' => $r['class'], 'params' => $params, 'requiresAuth' => $r['auth']];
            }
        }

        return null;
    }
}
