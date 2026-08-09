<?php

declare(strict_types=1);

use App\Contracts\Config\ConfigInterface;
use App\Infrastructure\System\FileRouteCache;

\uses()->group('infrastructure', 'system', 'routing');

\afterEach(function (): void {
    $cacheFile = \sys_get_temp_dir() . '/cache/routes_v2.php';
    if (\file_exists($cacheFile)) {
        \unlink($cacheFile);
    }
});

\it('returns null if cache file does not exist', function (): void {
    $config = $this->createStub(ConfigInterface::class);
    $config->method('getStoragePath')->willReturn(\sys_get_temp_dir() . '/cache/does_not_exist.php');

    $cache = new FileRouteCache($config);
    \expect($cache->load())->toBeNull();
})->covers(FileRouteCache::class);

\it('saves and loads routes from file', function (): void {
    $config = $this->createStub(ConfigInterface::class);
    $cachePath = \sys_get_temp_dir() . '/cache/routes_v2.php';
    $config->method('getStoragePath')->willReturn($cachePath);

    $cache = new FileRouteCache($config);

    $testRoutes = [
        'exact' => ['GET' => ['/home' => ['class' => 'HomeAction', 'auth' => false]]],
        'dynamic' => [],
    ];

    $cache->save($testRoutes);

    \expect(\file_exists($cachePath))->toBeTrue();

    $loadedRoutes = $cache->load();
    \expect($loadedRoutes)->toBe($testRoutes);
})->covers(FileRouteCache::class);
