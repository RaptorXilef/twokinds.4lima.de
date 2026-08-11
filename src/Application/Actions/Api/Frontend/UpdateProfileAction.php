<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Frontend;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;
use App\Core\Service\MagicLinkService;
use App\Core\ValueObject\Username;

#[Route('POST', '/api/frontend_update_profile')]
#[RequiresAuth]
final readonly class UpdateProfileAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private SessionManager $sessionManager,
        private UserRepositoryInterface $userRepository,
        private ConfigInterface $config,
        private MailServiceInterface $mailService,
        private MagicLinkService $magicLinkService,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->isLoggedIn()) {
            return JsonResponse::error('Nicht eingeloggt.', 401);
        }

        $userId = $this->sessionManager->getUserId();

        if (\str_starts_with($userId, 'sys_')) {
            return JsonResponse::error('System-Accounts können hier nicht bearbeitet werden.', 403);
        }

        $user = $this->userRepository->findById($userId);
        if (!$user instanceof User) {
            return JsonResponse::error('Benutzer nicht gefunden.', 404);
        }

        $actionTypeRaw = $request->post['update_type'] ?? '';
        $actionType = \is_scalar($actionTypeRaw) ? (string) $actionTypeRaw : '';

        return match ($actionType) {
            'newsletter' => $this->handleNewsletter($request, $user),
            'username' => $this->handleUsername($request, $user),
            'password' => $this->handlePassword($request, $user),
            'email' => $this->handleEmail($request, $user),
            'profile_details' => $this->handleProfileDetails($request, $user),
            default => JsonResponse::error('Ungültige Aktion.', 400),
        };
    }

    // =========================================================================
    // PRIVATE HANDLER HELPER
    // =========================================================================

    // Aktion: Newsletter
    private function handleNewsletter(ServerRequest $request, User $user): JsonResponse
    {
        $wnRaw = $request->post['wants_newsletter'] ?? false;
        $wantsNews = \in_array($wnRaw, [true, 1, '1', 'true', 'on'], true);

        $wtRaw = $request->post['wants_newsletter_transcript'] ?? false;
        $wantsTrans = \in_array($wtRaw, [true, 1, '1', 'true', 'on'], true);

        $wrRaw = $request->post['wants_notification_report'] ?? false;
        $wantsRep = \in_array($wrRaw, [true, 1, '1', 'true', 'on'], true);

        $updated = new User(
            $user->id,
            $user->username,
            $user->email,
            $user->passwordHash,
            $user->roleId,
            $user->createdAt,
            $wantsNews,
            $wantsTrans,
            $wantsRep,
        );

        $this->userRepository->save($updated);

        return JsonResponse::success(['message' => 'Benachrichtigungs-Einstellungen aktualisiert!']);
    }

    // Aktion: Benutzername ändern
    private function handleUsername(ServerRequest $request, User $user): JsonResponse
    {
        $newName = Sanitizer::string($request->post['new_username'] ?? '');

        if (\strlen($newName) < 3) {
            return JsonResponse::error('Der Name muss mindestens 3 Zeichen lang sein.', 400);
        }

        if ($this->isRestrictedUsername($newName)) {
            return JsonResponse::error('Dieser Benutzername ist reserviert.', 400);
        }

        if ($this->userRepository->findByUsername($newName) instanceof User) {
            return JsonResponse::error('Dieser Benutzername ist leider schon vergeben.', 400);
        }

        $updated = new User(
            $user->id,
            new Username($newName),
            $user->email,
            $user->passwordHash,
            $user->roleId,
            $user->createdAt,
            $user->wantsNewsletter,
            $user->wantsNewsletterTranscript,
            $user->wantsNotificationReport,
        );

        $this->userRepository->save($updated);
        $this->sessionManager->updateAdminUsername($newName);

        return JsonResponse::success(['message' => 'Benutzername erfolgreich geändert!']);
    }

    // Aktion: Passwort ändern
    private function handlePassword(ServerRequest $request, User $user): JsonResponse
    {
        $oldPassRaw = $request->post['old_password'] ?? '';
        $oldPass = \is_scalar($oldPassRaw) ? (string) $oldPassRaw : '';

        $newPassRaw = $request->post['new_password'] ?? '';
        $newPass = \is_scalar($newPassRaw) ? (string) $newPassRaw : '';

        $newPassConfirmRaw = $request->post['new_password_confirm'] ?? '';
        $newPassConfirm = \is_scalar($newPassConfirmRaw) ? (string) $newPassConfirmRaw : '';

        if (!\password_verify($oldPass, $user->passwordHash)) {
            return JsonResponse::error('Das alte Passwort ist nicht korrekt.', 400);
        }

        if ($newPass !== $newPassConfirm) {
            return JsonResponse::error('Die neuen Passwörter stimmen nicht überein.', 400);
        }

        if (\strlen($newPass) < 8) {
            return JsonResponse::error('Das neue Passwort muss mindestens 8 Zeichen lang sein.', 400);
        }

        $newHash = \password_hash($newPass, \PASSWORD_DEFAULT);

        $updated = new User(
            $user->id,
            $user->username,
            $user->email,
            $newHash,
            $user->roleId,
            $user->createdAt,
            $user->wantsNewsletter,
            $user->wantsNewsletterTranscript,
            $user->wantsNotificationReport,
        );

        $this->userRepository->save($updated);

        // Session mit neuem Hash versehen, sonst fliegt man beim nächsten Klick raus
        $this->sessionManager->setAuthSession($user->id, $user->roleId, $user->username->value, $newHash);

        return JsonResponse::success(['message' => 'Passwort erfolgreich geändert!']);
    }

    // E-Mail Logik
    private function handleEmail(ServerRequest $request, User $user): JsonResponse
    {
        $newEmailStr = Sanitizer::email($request->post['new_email'] ?? '');

        if ($newEmailStr === '') {
            return JsonResponse::error('Bitte eine gültige E-Mail-Adresse eingeben.', 400);
        }

        if ($newEmailStr === $user->email->value) {
            return JsonResponse::error('Das ist bereits deine aktuelle E-Mail-Adresse.', 400);
        }

        if ($this->userRepository->findByEmail($newEmailStr) instanceof User) {
            return JsonResponse::error('Diese E-Mail-Adresse wird bereits verwendet.', 400);
        }

        $tokenData = $this->magicLinkService->createToken($newEmailStr);
        $verifyUrl = \rtrim($this->config->getBaseUrl(), '/') . '/email-bestaetigen?token=' . $tokenData['token'];

        $this->mailService->sendTemplate(
            $newEmailStr,
            'Neue E-Mail-Adresse bestätigen',
            'verify_new_email',
            [
                'verifyUrl' => $verifyUrl,
                'username' => $user->username->value,
            ],
        );

        $this->mailService->processQueue(5, ['verify_new_email']);

        return JsonResponse::success([
            'message' => 'Bestätigungslink gesendet! Bitte prüfe den Posteingang deiner NEUEN E-Mail-Adresse.',
        ]);
    }

    private function handleProfileDetails(ServerRequest $request, User $user): JsonResponse
    {
        $bio = Sanitizer::html($request->post['bio'] ?? '');

        // Social Links verarbeiten (max 5)
        $socialLinksRaw = $request->post['social_links'] ?? [];

        $socialLinks = [];

        if (\is_array($socialLinksRaw)) {
            foreach ($socialLinksRaw as $link) {
                if (!\is_scalar($link)) {
                    continue;
                }

                $cleanLink = \filter_var(\trim((string) $link), \FILTER_SANITIZE_URL);
                if (\filter_var($cleanLink, \FILTER_VALIDATE_URL) === false) {
                    continue;
                }

                if (\count($socialLinks) >= 5) {
                    continue;
                }

                // Wir wissen jetzt sicher, dass es ein string ist.
                if (!\is_string($cleanLink)) {
                    continue;
                }

                $socialLinks[] = $cleanLink;
            }
        }

        $pbRaw = $request->post['public_bookmarks'] ?? false;
        $publicBookmarks = \in_array($pbRaw, [true, 1, '1', 'true', 'on'], true);

        $updated = new User(
            $user->id,
            $user->username,
            $user->email,
            $user->passwordHash,
            $user->roleId,
            $user->createdAt,
            $user->wantsNewsletter,
            $user->wantsNewsletterTranscript,
            $user->wantsNotificationReport,
            $user->avatarUrl,
            $bio,
            $socialLinks,
            $publicBookmarks,
        );

        $this->userRepository->save($updated);

        return JsonResponse::success(['message' => 'Profil-Details erfolgreich aktualisiert!']);
    }

    private function isRestrictedUsername(string $username): bool
    {
        $lowerName = \strtolower($username);
        $restricted = [];

        $backdoorCfg = $this->config->get('backdoor');
        if (\is_array($backdoorCfg)) {
            $bdUser = \is_string($backdoorCfg['user'] ?? null) ? $backdoorCfg['user'] : '';
            $restricted[] = \strtolower($bdUser);
        }

        $superAdmins = $this->config->get('superadmins');
        if (\is_array($superAdmins)) {
            foreach (\array_keys($superAdmins) as $saUser) {
                if (!\is_string($saUser)) {
                    continue;
                }

                $restricted[] = \strtolower($saUser);
            }
        }

        return \in_array($lowerName, $restricted, true);
    }
}
