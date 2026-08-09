<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Response;

use App\Application\Response\EmptyResponse;
use App\Application\Response\FileDownloadResponse;
use App\Application\Response\HtmlResponse;
use App\Application\Response\JsonResponse;
use App\Application\Response\RedirectResponse;
use App\Application\Response\TextResponse;

\uses()->group('application', 'response');

\it('creates EmptyResponse with default 204 status', function (): void {
    $res = new EmptyResponse();
    \expect($res->status)->toBe(204);
})->covers(EmptyResponse::class);

\it('creates HtmlResponse with content and status', function (): void {
    $res = new HtmlResponse('<h1>Hi</h1>', 201);
    \expect($res->html)->toBe('<h1>Hi</h1>')
        ->and($res->statusCode)->toBe(201);
})->covers(HtmlResponse::class);

\it('creates JsonResponse using success factory', function (): void {
    $res = JsonResponse::success(['id' => 5]);
    \expect($res->statusCode)->toBe(200)
        ->and($res->data['success'])->toBeTrue()
        ->and($res->data['id'])->toBe(5);
})->covers(JsonResponse::class);

\it('creates JsonResponse using error factory', function (): void {
    $res = JsonResponse::error('Not found', 404);
    \expect($res->statusCode)->toBe(404)
        ->and($res->data['success'])->toBeFalse()
        ->and($res->data['error'])->toBe('Not found');
})->covers(JsonResponse::class);

\it('creates JsonResponse using unauthorized factory', function (): void {
    $res = JsonResponse::unauthorized();
    \expect($res->statusCode)->toBe(401)
        ->and($res->data['success'])->toBeFalse()
        ->and($res->data['error'])->toContain('Unauthorized');
})->covers(JsonResponse::class);

\it('creates RedirectResponse with url and status', function (): void {
    $res = new RedirectResponse('/home', 301);
    \expect($res->url)->toBe('/home')
        ->and($res->statusCode)->toBe(301);
})->covers(RedirectResponse::class);

\it('creates TextResponse with text and status', function (): void {
    $res = new TextResponse('Plain text', 200);
    \expect($res->content)->toBe('Plain text')
        ->and($res->status)->toBe(200);
})->covers(TextResponse::class);

\it('creates FileDownloadResponse with attributes', function (): void {
    $res = new FileDownloadResponse('binary_data', 'backup.zip', 'application/zip');
    \expect($res->content)->toBe('binary_data')
        ->and($res->filename)->toBe('backup.zip')
        ->and($res->contentType)->toBe('application/zip');
})->covers(FileDownloadResponse::class);
