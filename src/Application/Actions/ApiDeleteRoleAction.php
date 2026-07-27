<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;

#[ActionRoute('api_delete_role')]
final readonly class ApiDeleteRoleAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private RoleRepositoryInterface $roleRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.roles.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $id = Sanitizer::string($request->post['role_id'] ?? '');

            if ($id === '') {
                return JsonResponse::error('Keine Rollen-ID übermittelt.', 400);
            }

            if (\in_array($id, ['admin', 'user', 'pending'], true)) {
                return JsonResponse::error('Diese System-Rolle darf nicht gelöscht werden.', 403);
            }

            $this->roleRepo->delete($id);

            return JsonResponse::success(['message' => 'Rolle erfolgreich gelöscht.']);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Löschen: ' . $e->getMessage(), 500);
        }
    }
}
