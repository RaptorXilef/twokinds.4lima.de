<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;

#[ActionRoute('api_admin_logout')]
final readonly class ApiAdminLogoutAction implements ActionInterface
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
