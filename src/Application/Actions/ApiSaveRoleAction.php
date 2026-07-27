<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Core\Entity\Role;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;

#[ActionRoute('api_save_role')]
final readonly class ApiSaveRoleAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private RoleRepositoryInterface $roleRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        // Sicherheits-Check: Nur wer das Recht hat, darf Rollen bearbeiten
        if (! $this->auth->hasPermission('system.roles.manage')) {
            return JsonResponse::error('Zugriff verweigert. Fehlende Berechtigung: system.roles.manage', 403);
        }

        try {
            $id   = Sanitizer::string($request->post['role_id'] ?? '');
            $name = Sanitizer::string($request->post['name'] ?? '');

            // Die Rechte kommen als JSON-String aus dem Frontend-Baum
            $permissionsRaw = $request->post['permissions'] ?? '[]';
            $permissions    = \json_decode($permissionsRaw, true);

            if (! \is_array($permissions)) {
                $permissions = [];
            }

            if ($id === '' || $name === '') {
                return JsonResponse::error('ID und Name sind Pflichtfelder.', 400);
            }

            // Schutz vor Überschreiben von Systemrollen
            if (\in_array($id, ['admin', 'user', 'pending'], true) && $this->auth->getRole() !== 'admin') {
                return JsonResponse::error('System-Rollen können nur von Haupt-Administratoren bearbeitet werden.', 403);
            }

            $role = new Role($id, $name, $permissions);
            $this->roleRepo->save($role);

            return JsonResponse::success(['message' => "Rolle '{$name}' erfolgreich gespeichert."]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Speichern: ' . $e->getMessage(), 500);
        }
    }
}
