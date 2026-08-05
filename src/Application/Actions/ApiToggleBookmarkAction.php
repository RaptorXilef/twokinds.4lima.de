<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Core\Service\AuthService;

#[ActionRoute('api_toggle_bookmark')]
final readonly class ApiToggleBookmarkAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private SessionManager $sessionManager,
        private BookmarkRepositoryInterface $bookmarkRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->isLoggedIn()) {
            return JsonResponse::error('Nicht eingeloggt.', 401);
        }

        $userId = $this->sessionManager->getUserId();
        // Götter blocken
        if (\str_starts_with($userId, 'sys_')) {
            return JsonResponse::error('System-Accounts unterstützen keine Cloud-Lesezeichen.', 403);
        }

        $comicId = \trim((string) ($request->post['comic_id'] ?? ''));
        $action  = \trim((string) ($request->post['bookmark_action'] ?? '')); // 'add' oder 'remove'

        // SECURITY FIX: Prüfe die Comic-ID strikt auf das Format
        if (! \preg_match('/^\d{8}[a-z]?$/i', $comicId) || ! \in_array($action, ['add', 'remove'], true)) {
            return JsonResponse::error('Ungültige Daten manipuliert.', 400);
        }

        if (! \in_array($action, ['add', 'remove'], true)) {
            return JsonResponse::error('Ungültige Daten.', 400);
        }

        if ($action === 'add') {
            $this->bookmarkRepo->add($userId, $comicId);

            return JsonResponse::success(['message' => 'Lesezeichen in der Cloud gespeichert.']);
        }

        $this->bookmarkRepo->remove($userId, $comicId);

        return JsonResponse::success(['message' => 'Lesezeichen aus der Cloud entfernt.']);
    }
}
