<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http;

use App\Application\Http\ServerRequest;

\uses()->group('application', 'http');

\it('initializes with default empty arrays', function (): void {
    $req = new ServerRequest();
    \expect($req->get)->toBeEmpty()
        ->and($req->post)->toBeEmpty()
        ->and($req->getMethod())->toBe('GET')
        ->and($req->getPath())->toBe('');
})->covers(ServerRequest::class);

\it('resolves correct HTTP method', function (): void {
    $req = new ServerRequest(server: ['REQUEST_METHOD' => 'POST']);
    \expect($req->getMethod())->toBe('POST');
})->covers(ServerRequest::class);

\it('resolves correct request path', function (): void {
    $req = new ServerRequest(server: ['REQUEST_URI' => '/api/test?foo=bar']);
    \expect($req->getPath())->toBe('/api/test?foo=bar');
})->covers(ServerRequest::class);

\it('extracts specific headers formatted correctly', function (): void {
    $req = new ServerRequest(server: ['HTTP_X_CSRF_TOKEN' => 'secret123']);
    \expect($req->getHeader('X-CSRF-Token'))->toBe('secret123')
        ->and($req->getHeader('Non-Existent'))->toBe('');
})->covers(ServerRequest::class);

\it('resolves client IP honoring proxies', function (): void {
    $req1 = new ServerRequest(server: ['HTTP_CF_CONNECTING_IP' => '1.1.1.1']);
    $req2 = new ServerRequest(server: ['HTTP_X_FORWARDED_FOR' => '2.2.2.2, 3.3.3.3']);
    $req3 = new ServerRequest(server: ['REMOTE_ADDR' => '127.0.0.1']);
    $req4 = new ServerRequest();

    \expect($req1->getIp())->toBe('1.1.1.1')
        ->and($req2->getIp())->toBe('2.2.2.2') // takes first IP from list
        ->and($req3->getIp())->toBe('127.0.0.1')
        ->and($req4->getIp())->toBe('unknown');
})->covers(ServerRequest::class);

\it('creates a new instance with mutated input via withInput', function (): void {
    $req = new ServerRequest(get: ['a' => 1]);
    $newReq = $req->withInput(['parsed' => true]);

    \expect($newReq)->not->toBe($req)
        ->and($newReq->get)->toBe(['a' => 1])
        ->and($newReq->input)->toBe(['parsed' => true]);
})->covers(ServerRequest::class);
