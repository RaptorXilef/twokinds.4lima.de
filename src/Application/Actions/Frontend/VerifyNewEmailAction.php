<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Service\AuthService;
use App\Core\Service\MagicLinkService;
use App\Core\ValueObject\EmailAddress;

#[Route('GET', '/email-bestaetigen')]
final readonly class VerifyNewEmailAction implements ActionInterface
{
    public function __construct(
        private MagicLinkService $magicLinkService,
        private UserRepositoryInterface $userRepository,
        private SessionManager $sessionManager,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->isLoggedIn()) {
            $this->sessionManager->addFlash('error', 'Bitte logge dich zuerst ein, um deine E-Mail-Adresse zu ändern. Öffne den Link anschließend erneut im selben Browser.');

            return new RedirectResponse('/login');
        }

        $tokenRaw = $request->get['token'] ?? '';
        $token = \is_scalar($tokenRaw) ? (string) $tokenRaw : '';
        $newEmailStr = $this->magicLinkService->verifyAny($token);

        if ($newEmailStr === null) {
            $this->sessionManager->addFlash('error', 'Der Bestätigungslink ist ungültig oder abgelaufen.');

            return new RedirectResponse('/profil');
        }

        if ($this->userRepository->findByEmail($newEmailStr) instanceof User) {
            $this->sessionManager->addFlash('error', 'Diese E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet.');

            return new RedirectResponse('/profil');
        }

        $userId = $this->sessionManager->getUserId();
        $user = $this->userRepository->findById($userId);

        if ($user instanceof User) {
            $updatedUser = new User(
                $user->id,
                $user->username,
                new EmailAddress($newEmailStr),
                $user->passwordHash,
                $user->roleId,
                $user->createdAt,
                $user->wantsNewsletter,
                $user->wantsNewsletterTranscript,
                $user->wantsNotificationReport,
            );
            $this->userRepository->save($updatedUser);
            $this->sessionManager->addFlash('success', 'Deine E-Mail-Adresse wurde erfolgreich geändert!');
        }

        return new RedirectResponse('/profil');
    }
}
