<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Routing;

use App\Application\Actions\Frontend\Error404Action;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Routing\ActionRegistry;
use App\Application\Routing\UniversalActionFactory;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\DependencyInjection\ContainerInterface;
use App\Contracts\System\RouteCacheInterface;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'routing');

\it('creates action using DI container', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);
    $mock = Closure::bind(fn (string $c): MockObject => $this->createMock($c), $this, self::class);

    $config = $stub(ConfigInterface::class);
    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn(['exact' => [], 'dynamic' => []]);

    $registry = new ActionRegistry($config, $cache);

    // MOCK, da wir expects() darauf anwenden
    $container = $mock(ContainerInterface::class);

    $dummyAction = new class implements ActionInterface {
        public function execute(ServerRequest $request): mixed
        {
            return true;
        }
    };

    $targetClass = Error404Action::class;

    $container->expects($this->once())
        ->method('get')
        ->with($targetClass)
        ->willReturn($dummyAction);

    $factory = new UniversalActionFactory($registry, $container);
    $created = $factory->create($targetClass);

    \expect($created)->toBe($dummyAction);
})->covers(UniversalActionFactory::class);

\it('returns null if class does not exist', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);

    $config = $stub(ConfigInterface::class);
    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn(['exact' => [], 'dynamic' => []]);

    $registry = new ActionRegistry($config, $cache);

    // STUB, da wir hier keine Aufrufe erwarten
    $container = $stub(ContainerInterface::class);

    $factory = new UniversalActionFactory($registry, $container);
    $created = $factory->create('NonExistentClass12345');

    \expect($created)->toBeNull();
})->covers(UniversalActionFactory::class);
