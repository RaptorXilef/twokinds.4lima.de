<?php
declare(strict_types = 1);

namespace Tests\Unit\Application\Routing;

use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Routing\ActionRegistry;
use App\Application\Routing\UniversalActionFactory;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\DependencyInjection\ContainerInterface;
use App\Contracts\System\RouteCacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'routing');

function setupUniversalActionFactoryTest(mixed $test): UniversalActionFactory {
    $stub = \Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);
    $mock = \Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);

    $config = $stub(ConfigInterface::class);
    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn(['exact' => [], 'dynamic' => []]);

    // Echte Instanz verwenden, da final!
    $registry = new ActionRegistry($config, $cache);

    // Container als echten Mock anlegen, damit wir expects() aufrufen können
    $container = $mock(ContainerInterface::class);

    $test->containerMock = $container;

    return new UniversalActionFactory($registry, $container);
}

\it('creates action using DI container', function (): void {
    $factory = setupUniversalActionFactoryTest($this);

    $dummyAction = new class implements ActionInterface {
        public function execute(ServerRequest $request): mixed { return true; }
    };

    $this->containerMock->expects($this->once())
        ->method('get')
        ->with('DummyActionClass')
        ->willReturn($dummyAction);

    $created = $factory->create('DummyActionClass');

    \expect($created)->toBe($dummyAction);
})->covers(UniversalActionFactory::class);

\it('returns null if class does not exist', function (): void {
    $factory = setupUniversalActionFactoryTest($this);
    $created = $factory->create('NonExistentClass12345');

    \expect($created)->toBeNull();
})->covers(UniversalActionFactory::class);
