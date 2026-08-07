<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Frontend;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Service\AuthService;

#[Route('POST', '/api/frontend_delete_account')]
#[RequiresAuth]
final readonly class DeleteAccountAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private SessionManager $sessionManager,
        private UserRepositoryInterface $userRepo,
        private BookmarkRepositoryInterface $bookmarkRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->isLoggedIn()) {
            return JsonResponse::error('Nicht eingeloggt.', 401);
        }

        $userId = $this->sessionManager->getUserId();
        if (\str_starts_with($userId, 'sys_')) {
            return JsonResponse::error('System-Accounts können nicht gelöscht werden.', 403);
        }

        $passwordRaw = $request->post['password'] ?? '';
        $password    = \is_scalar($passwordRaw) ? (string) $passwordRaw : '';

        $user = $this->userRepo->findById($userId);

        if (! $user instanceof User || ! \password_verify($password, $user->passwordHash)) {
            return JsonResponse::error('Das eingegebene Passwort ist nicht korrekt.', 400);
        }

        // Lesezeichen löschen
        $this->bookmarkRepo->replaceUserBookmarks($userId, []);

        // Account löschen
        $this->userRepo->delete($userId);

        // Session beenden
        $this->sessionManager->destroy();

        return JsonResponse::success([
            'message'  => 'Dein Konto und deine Lesezeichen wurden erfolgreich gelöscht.',
            'redirect' => '',
        ]);
    }
}
