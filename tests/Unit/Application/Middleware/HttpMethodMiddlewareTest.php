<?php

declare(strict_types=1);

use App\Application\Http\ServerRequest;
use App\Application\Middleware\HttpMethodMiddleware;
use App\Application\Response\JsonResponse;

\uses()->group('application', 'middleware');

\it('allows request if method is in allowed methods', function (): void {
    $middleware = new HttpMethodMiddleware(['POST', 'PUT']);
    $request = new ServerRequest(server: ['REQUEST_METHOD' => 'POST']);

    $nextCalled = false;
    $next = function (ServerRequest $req) use (&$nextCalled, $request): string {
        $nextCalled = true;
        \expect($req)->toBe($request);

        return 'success_payload';
    };

    $result = $middleware->process($request, $next);

    \expect($nextCalled)->toBeTrue()
        ->and($result)->toBe('success_payload');
})->covers(HttpMethodMiddleware::class);

\it('blocks request and returns JsonResponse 405 if method is not allowed', function (): void {
    $middleware = new HttpMethodMiddleware(['POST']);
    $request = new ServerRequest(server: ['REQUEST_METHOD' => 'GET']);

    $next = function (): never {
        $this->fail('The $next closure should not be called if the method is blocked.');
    };

    $result = $middleware->process($request, $next);

    \expect($result)->toBeInstanceOf(JsonResponse::class)
        ->and($result->statusCode)->toBe(405)
        ->and($result->data)->toBe(['success' => false, 'error' => 'Methode nicht erlaubt.']);
})->covers(HttpMethodMiddleware::class);
