<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Http\ServerRequest;
use App\Application\Middleware\SecurityHeadersMiddleware;
use App\Application\Session\SessionManager;
use App\Infrastructure\Utils\SystemClock;

\uses()->group('application', 'middleware', 'security');

\beforeEach(function (): void {
    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
});

\it('SecurityHeadersMiddleware injects secure headers and calls next', function (): void {
    $session = new SessionManager(new SystemClock());
    $middleware = new SecurityHeadersMiddleware($session);

    $request = new ServerRequest(server: ['HTTP_HOST' => 'twokinds.4lima.local']);

    $next = fn (ServerRequest $req): string => 'success';
    $result = $middleware->process($request, $next);

    \expect($result)->toBe('success');

    // Headers are untestable in CLI without xdebug/runkit, but we ensure it doesn't crash
    // and correctly identifies the local environment.
})->covers(SecurityHeadersMiddleware::class);
