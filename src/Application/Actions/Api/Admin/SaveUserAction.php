<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

#[Route('POST', '/api/save_user')]
#[RequiresAuth]
final readonly class SaveUserAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private UserRepositoryInterface $userRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('system.users.manage')) {
            return JsonResponse::error('Zugriff verweigert. Fehlende Berechtigung: system.users.manage', 403);
        }

        try {
            $id = Sanitizer::string($request->post['user_id'] ?? '');
            $usernameStr = Sanitizer::string($request->post['username'] ?? '');
            $emailStr = Sanitizer::email($request->post['email'] ?? '');
            $roleId = Sanitizer::string($request->post['role_id'] ?? 'user');

            $passRaw = $request->post['password'] ?? '';
            $password = \is_scalar($passRaw) ? (string) $passRaw : '';

            $confirmRaw = $request->post['password_confirm'] ?? '';
            $passwordConfirm = \is_scalar($confirmRaw) ? (string) $confirmRaw : '';

            // ID wird nicht mehr als Pflichtfeld beim POST erwartet (wichtig für NEUE Benutzer)
            if ($usernameStr === '' || $emailStr === '') {
                return JsonResponse::error('Name und E-Mail sind Pflichtfelder.', 400);
            }

            // Generiere eine neue ID, wenn der Benutzer neu angelegt wird
            if ($id === '' || $id === 'new') {
                $id = $this->auth->generateId('usr_');
            }

            $isConfigAdmin = \str_starts_with($this->auth->getUserId(), 'sys_');
            $existingUser = $this->userRepo->findById($id);

            // Admin-Schutz: Verhindere, dass normale Admins andere Administratoren bearbeiten/degradieren
            if ($existingUser instanceof User && $existingUser->roleId === 'admin' && !$isConfigAdmin) {
                if ($this->auth->getUserId() !== $id) {
                    return JsonResponse::error('Du darfst keine anderen Administratoren bearbeiten. Dies obliegt dem Systembetreuer.', 403);
                }
                if ($roleId !== 'admin') {
                    return JsonResponse::error('Du darfst dich nicht selbst degradieren.', 403);
                }
            }

            $hash = $existingUser->passwordHash ?? '';

            // Passwort-Logik
            if ($password !== '') {
                // Abgleich
                if ($password !== $passwordConfirm) {
                    return JsonResponse::error('Die Passwörter stimmen nicht überein.', 400);
                }

                if (\strlen($password) < 8) {
                    return JsonResponse::error('Das Passwort muss mindestens 8 Zeichen lang sein.', 400);
                }
                $hash = \password_hash($password, \PASSWORD_DEFAULT);
            } elseif (!$existingUser instanceof User) {
                return JsonResponse::error('Bei neuen Benutzern muss ein Passwort vergeben werden.', 400);
            }

            // Prüfe auf Namens- und E-Mail-Duplikate
            $checkName = $this->userRepo->findByUsername($usernameStr);
            if ($checkName instanceof User && $checkName->id !== $id) {
                return JsonResponse::error('Dieser Benutzername ist bereits vergeben.', 400);
            }

            $checkEmail = $this->userRepo->findByEmail($emailStr);
            if ($checkEmail instanceof User && $checkEmail->id !== $id) {
                return JsonResponse::error('Diese E-Mail wird bereits verwendet.', 400);
            }

            // Neues Benutzer-Objekt aufbauen
            $user = new User(
                $id,
                new Username($usernameStr),
                new EmailAddress($emailStr),
                $hash,
                $roleId,
                $existingUser->createdAt ?? new DateTimeImmutable(),
                $existingUser instanceof User && $existingUser->wantsNewsletter,
                $existingUser instanceof User && $existingUser->wantsNewsletterTranscript,
                $existingUser instanceof User && $existingUser->wantsNotificationReport,
            );

            // Speichern
            $this->userRepo->save($user);

            return JsonResponse::success([
                'message' => "Benutzer '{$usernameStr}' erfolgreich gespeichert.",
            ]);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            return JsonResponse::error('Fehler beim Speichern: ' . $e->getMessage(), 500);
        }
    }
}
