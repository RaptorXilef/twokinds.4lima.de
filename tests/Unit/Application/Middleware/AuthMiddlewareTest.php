<?php

declare(strict_types=1);

use App\Application\Http\ServerRequest;
use App\Application\Middleware\AuthMiddleware;
use App\Application\Response\JsonResponse;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'middleware', 'auth');

function setupAuthMiddlewareTest(mixed $test): object
{
    $mock = \Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);
    $stub = \Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    return new class($mock(SessionManager::class), $stub(ConfigInterface::class)) {
        public AuthMiddleware $middleware;

        public function __construct(
            public MockObject&SessionManager $sessionManager,
            public Stub&ConfigInterface $config,
        ) {
            $this->middleware = new AuthMiddleware($this->sessionManager, $this->config);
        }
    };
}

\it('calls next if user is logged in', function (): void {
    $app = \setupAuthMiddlewareTest($this);

    $app->sessionManager->expects($this->once())
        ->method('getUserId')
        ->willReturn('usr_123');

    $request = new ServerRequest();

    $nextCalled = false;
    $next = function (ServerRequest $req) use (&$nextCalled): string {
        $nextCalled = true;

        return 'success';
    };

    $result = $app->middleware->process($request, $next);

    \expect($nextCalled)->toBeTrue()
        ->and($result)->toBe('success');
})->covers(AuthMiddleware::class);

\it('returns 401 JsonResponse for API routes if not logged in', function (): void {
    $app = \setupAuthMiddlewareTest($this);

    $app->sessionManager->expects($this->once())
        ->method('getUserId')
        ->willReturn('');

    $request = new ServerRequest(server: ['REQUEST_URI' => '/api/save_user']);

    $next = function (ServerRequest $req): string {
        throw new \RuntimeException('Next should not be called');
    };

    $result = $app->middleware->process($request, $next);

    \expect($result)->toBeInstanceOf(JsonResponse::class)
        ->and($result->statusCode)->toBe(401)
        ->and($result->data['error'])->toContain('Sitzung abgelaufen');
})->covers(AuthMiddleware::class);

\it('returns 302 RedirectResponse for Admin routes if not logged in', function (): void {
    $app = \setupAuthMiddlewareTest($this);

    $app->sessionManager->expects($this->once())
        ->method('getUserId')
        ->willReturn('');

    $app->config->method('getBaseUrl')->willReturn('https://test.local');

    $request = new ServerRequest(server: ['REQUEST_URI' => '/admin/dashboard']);

    $next = function (ServerRequest $req): string {
        throw new \RuntimeException('Next should not be called');
    };

    $result = $app->middleware->process($request, $next);

    \expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->url)->toBe('https://test.local/admin/login')
        ->and($result->statusCode)->toBe(302);
})->covers(AuthMiddleware::class);
