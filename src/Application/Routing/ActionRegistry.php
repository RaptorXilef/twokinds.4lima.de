<?php

declare(strict_types=1);

namespace App\Application\Routing;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\RouteCacheInterface;

final class ActionRegistry
{
    /**
     * @var array{exact: array<string, array<string, array{class: string, auth: bool}>>, dynamic: array<string, array<string, array{class: string, auth: bool}>>}
     */
    private array $routes = ['exact' => [], 'dynamic' => []];

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly RouteCacheInterface $cache,
    ) {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $this->cache->clearOld();

        if ($this->config->get('admin_dev_mode', false) !== true) {
            $cached = $this->cache->load();
            if ($cached !== null) {
                // PHPStan zwingt uns, die Struktur blind zu vertrauen oder umständlich zu prüfen.
                // Da der Cache aus der eigenen App generiert wird, ist @var hier legitim.
                /** @var array{exact: array<string, array<string, array{class: string, auth: bool}>>, dynamic: array<string, array<string, array{class: string, auth: bool}>>} $cachedArr */
                $cachedArr    = $cached;
                $this->routes = $cachedArr;

                return;
            }
        }

        $rootPath = $this->config->get('root_path');
        $baseDir  = \rtrim(\is_string($rootPath) ? $rootPath : '', '/\\') . '/src/Application/Actions';
        $this->scanDirectoryRecursively($baseDir);

        $this->cache->save($this->routes);
    }

    private function scanDirectoryRecursively(string $dir): void
    {
        if (! \is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = \str_replace($dir . \DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classSuffix  = \str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);
            $className    = 'App\\Application\\Actions\\' . $classSuffix;

            if (! \class_exists($className)) {
                continue;
            }

            $reflection   = new \ReflectionClass($className);
            $requiresAuth = $reflection->getAttributes(RequiresAuth::class) !== [];

            foreach ($reflection->getAttributes(Route::class) as $attribute) {
                $route = $attribute->newInstance();
                $this->registerRoute($route->method, $route->path, $className, $requiresAuth);
            }
        }
    }

    private function registerRoute(string $method, string $path, string $className, bool $requiresAuth): void
    {
        if (\str_contains($path, '{')) {
            // Wandelt "/comic/{id}" in "#^/comic/(?P<id>[^/]+)$#" um
            $replaced                                               = \preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $path);
            $regex                                                  = \is_string($replaced) ? $replaced : '';
            $this->routes['dynamic'][$method]['#^' . $regex . '$#'] = ['class' => $className, 'auth' => $requiresAuth];
        } else {
            $this->routes['exact'][$method][$path] = ['class' => $className, 'auth' => $requiresAuth];
        }
    }

    /**
     * @return array{class: string, params: array<string, string>, requiresAuth: bool}|null
     */
    public function match(string $method, string $path): ?array
    {
        // Exact Match
        if (isset($this->routes['exact'][$method][$path])) {
            $r = $this->routes['exact'][$method][$path];

            return ['class' => $r['class'], 'params' => [], 'requiresAuth' => $r['auth']];
        }

        // Dynamic Parameter Match (Regex)
        foreach ($this->routes['dynamic'][$method] ?? [] as $regex => $r) {
            if (\preg_match($regex, $path, $matches) === 1) {
                /** @var array<string, string> $params */
                $params = \array_filter($matches, \is_string(...), \ARRAY_FILTER_USE_KEY);

                return ['class' => $r['class'], 'params' => $params, 'requiresAuth' => $r['auth']];
            }
        }

        return null;
    }
}
