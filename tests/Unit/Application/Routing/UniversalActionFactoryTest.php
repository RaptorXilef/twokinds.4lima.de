<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Routing;

use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Routing\ActionRegistry;
use App\Application\Routing\UniversalActionFactory;
use App\Contracts\DependencyInjection\ContainerInterface;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'routing');

\it('creates action using DI container', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);
    $mock = Closure::bind(fn (string $c): MockObject => $this->createMock($c), $this, self::class);

    $registry = $stub(ActionRegistry::class);
    $container = $mock(ContainerInterface::class);

    $dummyAction = new class implements ActionInterface {
        public function execute(ServerRequest $request): mixed
        {
            return true;
        }
    };

    $container->expects($this->once())
        ->method('get')
        ->with('DummyActionClass')
        ->willReturn($dummyAction);

    $factory = new UniversalActionFactory($registry, $container);
    $created = $factory->create('DummyActionClass');

    \expect($created)->toBe($dummyAction);
})->covers(UniversalActionFactory::class);

\it('returns null if class does not exist', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);
    $registry = $stub(ActionRegistry::class);
    $container = $stub(ContainerInterface::class);

    $factory = new UniversalActionFactory($registry, $container);
    $created = $factory->create('NonExistentClass12345');

    \expect($created)->toBeNull();
})->covers(UniversalActionFactory::class);
