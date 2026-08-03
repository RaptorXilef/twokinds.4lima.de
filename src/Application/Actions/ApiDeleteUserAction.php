<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;

#[ActionRoute('api_delete_user')]
final readonly class ApiDeleteUserAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private UserRepositoryInterface $userRepo,
        private BookmarkRepositoryInterface $bookmarkRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.users.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $id = Sanitizer::string($request->post['user_id'] ?? '');

            if ($id === '') {
                return JsonResponse::error('Keine Benutzer-ID übermittelt.', 400);
            }

            if ($id === $this->auth->getUserId()) {
                return JsonResponse::error('Du kannst dich nicht selbst löschen!', 403);
            }

            $userToDelete = $this->userRepo->findById($id);
            if ($userToDelete && $userToDelete->roleId === 'admin' && ! \str_starts_with($this->auth->getUserId(), 'sys_')) {
                return JsonResponse::error('Nur der Systembetreuer darf Administratoren löschen.', 403);
            }

            // Bookmarks des Benutzers komplett entfernen
            $this->bookmarkRepo->replaceUserBookmarks($id, []);

            // Benutzer löschen
            $this->userRepo->delete($id);

            return JsonResponse::success(['message' => 'Benutzer erfolgreich gelöscht.']);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Löschen: ' . $e->getMessage(), 500);
        }
    }
}
