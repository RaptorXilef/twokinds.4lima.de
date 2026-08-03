<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
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

#[ActionRoute('api_frontend_update_profile')]
final readonly class ApiFrontendUpdateProfileAction implements ActionInterface
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
        if (! $this->auth->isLoggedIn()) {
            return JsonResponse::error('Nicht eingeloggt.', 401);
        }

        $userId = $this->sessionManager->getUserId();
        if (\str_starts_with($userId, 'sys_')) {
            return JsonResponse::error('System-Accounts können hier nicht bearbeitet werden.', 403);
        }

        $user = $this->userRepository->findById($userId);
        if (! $user) {
            return JsonResponse::error('Benutzer nicht gefunden.', 404);
        }

        $actionType = $request->post['update_type'] ?? '';

        // Aktion: Newsletter
        if ($actionType === 'newsletter') {
            $wantsNews  = ! empty($request->post['wants_newsletter']);
            $wantsTrans = ! empty($request->post['wants_newsletter_transcript']);
            $wantsRep   = ! empty($request->post['wants_notification_report']);
            $updated    = new User($user->id, $user->username, $user->email, $user->passwordHash, $user->roleId, $user->createdAt, $wantsNews, $wantsTrans, $wantsRep);
            $this->userRepository->save($updated);

            return JsonResponse::success(['message' => 'Benachrichtigungs-Einstellungen aktualisiert!']);
        }

        // Aktion: Benutzername ändern
        if ($actionType === 'username') {
            $newName = Sanitizer::string($request->post['new_username'] ?? '');
            if (\strlen($newName) < 3) {
                return JsonResponse::error('Der Name muss mindestens 3 Zeichen lang sein.', 400);
            }

            $lowerName  = \strtolower($newName);
            $restricted = [];
            if ($bd = $this->config->get('backdoor')) {
                $restricted[] = \strtolower($bd['user'] ?? '');
            }
            if ($sa = $this->config->get('superadmin')) {
                $restricted[] = \strtolower($sa['user'] ?? '');
            }

            if (\in_array($lowerName, $restricted, true)) {
                return JsonResponse::error('Dieser Benutzername ist reserviert.', 400);
            }
            if ($this->userRepository->findByUsername($newName)) {
                return JsonResponse::error('Dieser Benutzername ist leider schon vergeben.', 400);
            }

            $updated = new User($user->id, new Username($newName), $user->email, $user->passwordHash, $user->roleId, $user->createdAt, $user->wantsNewsletter, $user->wantsNewsletterTranscript, $user->wantsNotificationReport);
            $this->userRepository->save($updated);
            $this->sessionManager->updateAdminUsername($newName); // Session updaten

            return JsonResponse::success(['message' => 'Benutzername erfolgreich geändert!']);
        }

        // Aktion: Passwort ändern
        if ($actionType === 'password') {
            $oldPass = (string) ($request->post['old_password'] ?? '');
            $newPass = (string) ($request->post['new_password'] ?? '');

            if (! \password_verify($oldPass, $user->passwordHash)) {
                return JsonResponse::error('Das alte Passwort ist nicht korrekt.', 400);
            }
            if (\strlen($newPass) < 8) {
                return JsonResponse::error('Das neue Passwort muss mindestens 8 Zeichen lang sein.', 400);
            }

            $newHash = \password_hash($newPass, \PASSWORD_DEFAULT);
            $updated = new User($user->id, $user->username, $user->email, $newHash, $user->roleId, $user->createdAt, $user->wantsNewsletter, $user->wantsNewsletterTranscript, $user->wantsNotificationReport);
            $this->userRepository->save($updated);

            // Session mit neuem Hash versehen, sonst fliegt man beim nächsten Klick raus
            $this->sessionManager->setAuthSession($user->id, $user->roleId, $user->username->value, $newHash);

            return JsonResponse::success(['message' => 'Passwort erfolgreich geändert!']);
        }

        // E-Mail Logik
        if ($actionType === 'email') {
            $newEmailStr = Sanitizer::email($request->post['new_email'] ?? '');
            if ($newEmailStr === '') {
                return JsonResponse::error('Bitte eine gültige E-Mail-Adresse eingeben.', 400);
            }
            if ($newEmailStr === $user->email->value) {
                return JsonResponse::error('Das ist bereits deine aktuelle E-Mail-Adresse.', 400);
            }
            if ($this->userRepository->findByEmail($newEmailStr)) {
                return JsonResponse::error('Diese E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet.', 400);
            }

            $tokenData = $this->magicLinkService->createToken($newEmailStr);
            $verifyUrl = \rtrim($this->config->getBaseUrl(), '/') . '/email-bestaetigen?token=' . $tokenData['token'];

            $this->mailService->sendTemplate($newEmailStr, 'Neue E-Mail-Adresse bestätigen', 'verify_new_email', [
                'verifyUrl' => $verifyUrl,
                'username'  => $user->username->value,
            ]);
            $this->mailService->processQueue(5, ['verify_new_email']);

            return JsonResponse::success(['message' => 'Bestätigungslink gesendet! Bitte prüfe den Posteingang deiner NEUEN E-Mail-Adresse.']);
        }

        return JsonResponse::error('Ungültige Aktion.', 400);
    }
}
