<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Shared;

use App\Application\Actions\Api\Shared\KeepAliveAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;

\uses()->group('application', 'actions', 'api');

\it('KeepAliveAction returns success JsonResponse', function (): void {
    $action = new KeepAliveAction();
    $response = $action->execute(new ServerRequest());

    \expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->statusCode)->toBe(200)
        ->and($response->data['message'])->toBe('Sitzung verlängert.');
})->covers(KeepAliveAction::class);
