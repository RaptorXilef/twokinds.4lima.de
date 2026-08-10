<?php

declare(strict_types=1);

use App\Application\Routing\ActionRegistry;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\RouteCacheInterface;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'routing');

function setupRegistryTest(mixed $test): object
{
    $stub = \Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    return new class($stub(ConfigInterface::class), $stub(RouteCacheInterface::class)) {
        public function __construct(
            public Stub&ConfigInterface $config,
            public Stub&RouteCacheInterface $cache,
        ) {
        }
    };
}

\it('loads routes from cache if not in dev mode', function (): void {
    $app = \setupRegistryTest($this);

    $app->config->method('get')->willReturnMap([
        ['admin_dev_mode', false, false],
    ]);

    $app->cache->method('load')->willReturn([
        'exact' => ['GET' => ['/test' => ['class' => 'TestClass', 'auth' => false]]],
        'dynamic' => [],
    ]);

    $registry = new ActionRegistry($app->config, $app->cache);
    $match = $registry->match('GET', '/test');

    \expect($match)->toBeArray()
        ->and($match['class'])->toBe('TestClass');
})->covers(ActionRegistry::class);

\it('matches exact routes correctly', function (): void {
    $app = \setupRegistryTest($this);

    $app->config->method('get')->willReturn(false);
    $app->cache->method('load')->willReturn([
        'exact' => ['POST' => ['/api/login' => ['class' => 'LoginAction', 'auth' => false]]],
        'dynamic' => [],
    ]);

    $registry = new ActionRegistry($app->config, $app->cache);

    \expect($registry->match('POST', '/api/login'))->toBeArray()
        ->and($registry->match('GET', '/api/login'))->toBeNull()
        ->and($registry->match('POST', '/api/unknown'))->toBeNull();
})->covers(ActionRegistry::class);

\it('matches dynamic routes and extracts parameters', function (): void {
    $app = \setupRegistryTest($this);

    $app->config->method('get')->willReturn(false);
    $app->cache->method('load')->willReturn([
        'exact' => [],
        'dynamic' => ['GET' => ['#^/comic/(?P<id>[^/]+)$#' => ['class' => 'ComicAction', 'auth' => false]]],
    ]);

    $registry = new ActionRegistry($app->config, $app->cache);
    $match = $registry->match('GET', '/comic/20260810');

    \expect($match)->not->toBeNull()
        ->and($match['class'])->toBe('ComicAction')
        ->and($match['params']['id'])->toBe('20260810');
})->covers(ActionRegistry::class);

\it('returns null if no route matches at all', function (): void {
    $app = \setupRegistryTest($this);

    $app->config->method('get')->willReturn(false);
    $app->cache->method('load')->willReturn(['exact' => [], 'dynamic' => []]);

    $registry = new ActionRegistry($app->config, $app->cache);

    \expect($registry->match('GET', '/nowhere'))->toBeNull();
})->covers(ActionRegistry::class);
