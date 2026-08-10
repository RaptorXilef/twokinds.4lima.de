<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Http\ServerRequest;
use App\Application\Middleware\AuthMiddleware;
use App\Application\Response\JsonResponse;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Infrastructure\Utils\SystemClock;
use RuntimeException;

\uses()->group('application', 'middleware', 'auth');

\beforeEach(function (): void {
    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
});

\it('calls next if user is logged in', function (): void {
    $_SESSION['user_id'] = 'usr_123';
    $sessionManager = new SessionManager(new SystemClock());

    $config = $this->createStub(ConfigInterface::class);
    $middleware = new AuthMiddleware($sessionManager, $config);

    $request = new ServerRequest();

    $nextCalled = false;
    $next = function (ServerRequest $req) use (&$nextCalled): string {
        $nextCalled = true;

        return 'success';
    };

    $result = $middleware->process($request, $next);

    \expect($nextCalled)->toBeTrue()
        ->and($result)->toBe('success');
})->covers(AuthMiddleware::class);

\it('returns 401 JsonResponse for API routes if not logged in', function (): void {
    $_SESSION['user_id'] = '';
    $sessionManager = new SessionManager(new SystemClock());

    $config = $this->createStub(ConfigInterface::class);
    $middleware = new AuthMiddleware($sessionManager, $config);

    $request = new ServerRequest(server: ['REQUEST_URI' => '/api/save_user']);

    $next = function (ServerRequest $req): string {
        throw new RuntimeException('Next should not be called');
    };

    $result = $middleware->process($request, $next);

    \expect($result)->toBeInstanceOf(JsonResponse::class)
        ->and($result->statusCode)->toBe(401)
        ->and($result->data['error'])->toContain('Sitzung abgelaufen');
})->covers(AuthMiddleware::class);

\it('returns 302 RedirectResponse for Admin routes if not logged in', function (): void {
    $_SESSION['user_id'] = '';
    $sessionManager = new SessionManager(new SystemClock());

    $config = $this->createStub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('https://test.local');

    $middleware = new AuthMiddleware($sessionManager, $config);

    $request = new ServerRequest(server: ['REQUEST_URI' => '/admin/dashboard']);

    $next = function (ServerRequest $req): string {
        throw new RuntimeException('Next should not be called');
    };

    $result = $middleware->process($request, $next);

    \expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->url)->toBe('https://test.local/admin/login')
        ->and($result->statusCode)->toBe(302);
})->covers(AuthMiddleware::class);
