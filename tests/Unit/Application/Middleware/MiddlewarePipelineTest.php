<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;
use App\Application\Middleware\MiddlewarePipeline;
use RuntimeException;

\uses()->group('application', 'middleware');

\it('processes empty pipeline by directly calling core action', function (): void {
    $pipeline = new MiddlewarePipeline();
    $request = new ServerRequest();

    $core = fn (ServerRequest $req): string => 'core_result';

    \expect($pipeline->process($request, $core))->toBe('core_result');
})->covers(MiddlewarePipeline::class);

\it('processes multiple middlewares in correct FIFO order', function (): void {
    $pipeline = new MiddlewarePipeline();
    $request = new ServerRequest();

    $middleware1 = new class implements MiddlewareInterface {
        public function process(ServerRequest $req, callable $next): mixed
        {
            return 'M1_' . $next($req);
        }
    };

    $middleware2 = new class implements MiddlewareInterface {
        public function process(ServerRequest $req, callable $next): mixed
        {
            return 'M2_' . $next($req);
        }
    };

    $pipeline->add($middleware1)->add($middleware2);

    $core = fn (ServerRequest $req): string => 'CORE';

    // Output should be M1_M2_CORE
    \expect($pipeline->process($request, $core))->toBe('M1_M2_CORE');
})->covers(MiddlewarePipeline::class);

\it('halts pipeline if a middleware does not call next', function (): void {
    $pipeline = new MiddlewarePipeline();
    $request = new ServerRequest();

    $haltingMiddleware = new class implements MiddlewareInterface {
        public function process(ServerRequest $req, callable $next): mixed
        {
            return 'HALTED'; // Does not call $next
        }
    };

    $pipeline->add($haltingMiddleware);

    $core = function (ServerRequest $req): string {
        throw new RuntimeException('Should never be reached');
    };

    \expect($pipeline->process($request, $core))->toBe('HALTED');
})->covers(MiddlewarePipeline::class);
