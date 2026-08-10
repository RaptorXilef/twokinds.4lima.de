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
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'routing');

function setupUniversalActionFactoryTest(mixed $test): UniversalActionFactory
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    $config = $stub(ConfigInterface::class);
    $cache = $stub(RouteCacheInterface::class);
    $cache->method('load')->willReturn(['exact' => [], 'dynamic' => []]);

    $registry = new ActionRegistry($config, $cache);

    // Wir nutzen hier einen Stub, da wir keine expects() Methoden darauf anwenden
    $container = $stub(ContainerInterface::class);
    $test->containerStub = $container;

    return new UniversalActionFactory($registry, $container);
}

\it('creates action using DI container', function (): void {
    $factory = setupUniversalActionFactoryTest($this);

    $dummyAction = new class implements ActionInterface {
        public function execute(ServerRequest $request): mixed
        {
            return true;
        }
    };

    // class_exists() muss greifen, deshalb nutzen wir eine ECHTE Klasse für den Test-Namen
    $targetClass = Error404Action::class;

    $this->containerStub->method('get')
        ->willReturnMap([
            [$targetClass, $dummyAction],
        ]);

    $created = $factory->create($targetClass);

    \expect($created)->toBe($dummyAction);
})->covers(UniversalActionFactory::class);

\it('returns null if class does not exist', function (): void {
    $factory = setupUniversalActionFactoryTest($this);

    // class_exists() schlägt hier fehl, Container wird nie gerufen
    $created = $factory->create('NonExistentClass12345');

    \expect($created)->toBeNull();
})->covers(UniversalActionFactory::class);
