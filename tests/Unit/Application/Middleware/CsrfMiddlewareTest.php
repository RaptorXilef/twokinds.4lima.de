<?php

declare(strict_types=1);

use App\Application\Http\ServerRequest;
use App\Application\Middleware\CsrfMiddleware;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use PHPUnit\Framework\MockObject\MockObject;

\uses()->group('application', 'middleware', 'security');

function setupCsrfTest(mixed $test): object
{
    $mock = \Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);

    return new class($mock(SessionManager::class)) {
        public CsrfMiddleware $middleware;

        public function __construct(
            public MockObject&SessionManager $sessionManager,
        ) {
            $this->middleware = new CsrfMiddleware($this->sessionManager, '/fallback-url');
        }
    };
}

\it('passes GET requests without checking CSRF token', function (): void {
    $app = \setupCsrfTest($this);
    $request = new ServerRequest(server: ['REQUEST_METHOD' => 'GET']);

    $app->sessionManager->expects($this->never())->method('getCsrfToken');

    $nextCalled = false;
    $next = function (ServerRequest $req) use (&$nextCalled): string {
        $nextCalled = true;

        return 'passed_get';
    };

    $result = $app->middleware->process($request, $next);

    \expect($nextCalled)->toBeTrue()
        ->and($result)->toBe('passed_get');
})->covers(CsrfMiddleware::class);

\it('passes POST request with valid CSRF token in POST body', function (): void {
    $app = \setupCsrfTest($this);
    $request = new ServerRequest(
        post: ['csrf_token' => 'valid_token_123'],
        server: ['REQUEST_METHOD' => 'POST'],
    );

    $app->sessionManager->expects($this->once())
        ->method('getCsrfToken')
        ->willReturn('valid_token_123');

    $next = fn (ServerRequest $req): string => 'passed_post';

    \expect($app->middleware->process($request, $next))->toBe('passed_post');
})->covers(CsrfMiddleware::class);

\it('passes POST request with valid CSRF token in HTTP Header', function (): void {
    $app = \setupCsrfTest($this);
    // Header keys mapped by the ServerRequest class follow 'HTTP_X_...' format.
    $request = new ServerRequest(
        server: [
            'REQUEST_METHOD' => 'POST',
            'HTTP_X_CSRF_TOKEN' => 'header_token_456',
        ],
    );

    $app->sessionManager->expects($this->once())
        ->method('getCsrfToken')
        ->willReturn('header_token_456');

    $next = fn (ServerRequest $req): string => 'passed_header';

    \expect($app->middleware->process($request, $next))->toBe('passed_header');
})->covers(CsrfMiddleware::class);

\it('blocks POST request with invalid CSRF token, saves form data and returns redirect', function (): void {
    $app = \setupCsrfTest($this);
    $request = new ServerRequest(
        post: ['csrf_token' => 'invalid_malicious_token', 'action' => 'save', 'username' => 'test_user'],
        server: ['REQUEST_METHOD' => 'POST'],
    );

    $app->sessionManager->expects($this->once())
        ->method('getCsrfToken')
        ->willReturn('valid_token_123');

    // Ensure 'csrf_token' and 'action' are unset before saving form data
    $app->sessionManager->expects($this->once())
        ->method('setFormData')
        ->with(['username' => 'test_user']);

    $app->sessionManager->expects($this->once())
        ->method('addFlash')
        ->with('error', $this->stringContains('Ihre Sitzung ist abgelaufen'));

    $next = function (): never {
        $this->fail('The $next closure should not be called on CSRF validation failure.');
    };

    $result = $app->middleware->process($request, $next);

    \expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->url)->toBe('/fallback-url')
        ->and($result->statusCode)->toBe(302);
})->covers(CsrfMiddleware::class);
