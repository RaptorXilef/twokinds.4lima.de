<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Http\ServerRequest;
use App\Application\Middleware\CsrfMiddleware;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Infrastructure\Utils\SystemClock;
use RuntimeException;

\uses()->group('application', 'middleware', 'security');

\beforeEach(function (): void {
    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
});

\it('passes GET requests without checking CSRF token', function (): void {
    $sessionManager = new SessionManager(new SystemClock());
    $middleware = new CsrfMiddleware($sessionManager, '/fallback-url');

    $request = new ServerRequest(server: ['REQUEST_METHOD' => 'GET']);

    $nextCalled = false;
    $next = function (ServerRequest $req) use (&$nextCalled): string {
        $nextCalled = true;

        return 'passed_get';
    };

    $result = $middleware->process($request, $next);

    \expect($nextCalled)->toBeTrue()
        ->and($result)->toBe('passed_get');
})->covers(CsrfMiddleware::class);

\it('passes POST request with valid CSRF token in POST body', function (): void {
    $sessionManager = new SessionManager(new SystemClock());
    $_SESSION['csrf_token'] = 'valid_token_123';

    $middleware = new CsrfMiddleware($sessionManager, '/fallback-url');
    $request = new ServerRequest(
        post: ['csrf_token' => 'valid_token_123'],
        server: ['REQUEST_METHOD' => 'POST'],
    );

    $next = fn (ServerRequest $req): string => 'passed_post';

    \expect($middleware->process($request, $next))->toBe('passed_post');
})->covers(CsrfMiddleware::class);

\it('passes POST request with valid CSRF token in HTTP Header', function (): void {
    $sessionManager = new SessionManager(new SystemClock());
    $_SESSION['csrf_token'] = 'header_token_456';

    $middleware = new CsrfMiddleware($sessionManager, '/fallback-url');
    $request = new ServerRequest(
        server: [
            'REQUEST_METHOD' => 'POST',
            'HTTP_X_CSRF_TOKEN' => 'header_token_456',
        ],
    );

    $next = fn (ServerRequest $req): string => 'passed_header';

    \expect($middleware->process($request, $next))->toBe('passed_header');
})->covers(CsrfMiddleware::class);

\it('blocks POST request with invalid CSRF token, saves form data and returns redirect', function (): void {
    $sessionManager = new SessionManager(new SystemClock());
    $_SESSION['csrf_token'] = 'valid_token_123';

    $middleware = new CsrfMiddleware($sessionManager, '/fallback-url');
    $request = new ServerRequest(
        post: ['csrf_token' => 'invalid_malicious_token', 'action' => 'save', 'username' => 'test_user'],
        server: ['REQUEST_METHOD' => 'POST'],
    );

    $next = function (ServerRequest $req): string {
        throw new RuntimeException('The $next closure should not be called on CSRF validation failure.');
    };

    $result = $middleware->process($request, $next);

    \expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->url)->toBe('/fallback-url')
        ->and($result->statusCode)->toBe(302)
        ->and($_SESSION['form_data'])->toBe(['username' => 'test_user'])
        ->and($_SESSION['flashes']['error'][0])->toContain('Ihre Sitzung ist abgelaufen');
})->covers(CsrfMiddleware::class);
