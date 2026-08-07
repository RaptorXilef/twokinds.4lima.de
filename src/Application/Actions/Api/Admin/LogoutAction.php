<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;

#[Route('POST', '/api/admin_logout')]
#[RequiresAuth]
final readonly class LogoutAction implements ActionInterface
{
    public function __construct(private SessionManager $sessionManager)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        $this->sessionManager->destroy();

        return JsonResponse::success(['message' => 'Erfolgreich abgemeldet.', 'redirect' => 'admin/login']);
    }
}
