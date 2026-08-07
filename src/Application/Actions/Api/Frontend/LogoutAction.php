<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;

#[Route('POST', '/api/frontend_logout')]
final readonly class LogoutAction implements ActionInterface
{
    public function __construct(private SessionManager $sessionManager)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        $this->sessionManager->destroy();

        return JsonResponse::success(['message' => 'Erfolgreich abgemeldet.', 'redirect' => '']);
    }
}
