<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Admin;

use App\Application\Actions\Api\Admin\LogoutAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;

\uses()->group('application', 'actions', 'api');

\it('Admin LogoutAction destroys session and returns redirect json', function (): void {
    $session = $this->createMock(SessionManager::class);
    $session->expects($this->once())->method('destroy');

    $action = new LogoutAction($session);
    $response = $action->execute(new ServerRequest());

    \expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->statusCode)->toBe(200)
        ->and($response->data['redirect'])->toBe('admin/login');
})->covers(LogoutAction::class);
