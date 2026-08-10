<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Frontend;

use App\Application\Actions\Api\Frontend\LogoutAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Infrastructure\Utils\SystemClock;

\uses()->group('application', 'actions', 'api');

\beforeEach(function (): void {
    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
});

\it('Frontend LogoutAction destroys session and returns redirect json', function (): void {
    $_SESSION['user_id'] = 'usr_1';
    $session = new SessionManager(new SystemClock());

    $action = new LogoutAction($session);
    $response = $action->execute(new ServerRequest());

    \expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->statusCode)->toBe(200)
        ->and($response->data['redirect'])->toBe('')
        ->and($session->getUserId())->toBe(''); // Verifies session was destroyed
})->covers(LogoutAction::class);
