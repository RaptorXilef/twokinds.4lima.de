<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;

#[ActionRoute('api_save_user')]
final readonly class ApiSaveUserAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private UserRepositoryInterface $userRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.users.manage')) {
            return JsonResponse::error('Zugriff verweigert. Fehlende Berechtigung: system.users.manage', 403);
        }

        try {
            $id          = Sanitizer::string($request->post['user_id'] ?? '');
            $usernameStr = Sanitizer::string($request->post['username'] ?? '');
            $emailStr    = Sanitizer::email($request->post['email'] ?? '');
            $roleId      = Sanitizer::string($request->post['role_id'] ?? 'user');
            $password    = (string) ($request->post['password'] ?? '');

            if ($id === '' || $usernameStr === '' || $emailStr === '') {
                return JsonResponse::error('ID, Name und E-Mail sind Pflichtfelder.', 400);
            }

            $isConfigAdmin = \str_starts_with($this->auth->getUserId(), 'sys_');
            $existingUser  = $this->userRepo->findById($id);

            // ADMIN-SCHUTZ:
            if ($existingUser && $existingUser->roleId === 'admin' && ! $isConfigAdmin) {
                if ($this->auth->getUserId() !== $id) {
                    return JsonResponse::error('Du darfst keine anderen Administratoren bearbeiten. Dies obliegt dem Systembetreuer.', 403);
                }
                if ($roleId !== 'admin') {
                    return JsonResponse::error('Du darfst dich nicht selbst degradieren.', 403);
                }
            }

            $hash = $existingUser ? $existingUser->passwordHash : '';

            // Wenn ein neues Passwort eingegeben wurde
            if ($password !== '') {
                if (\strlen($password) < 8) {
                    return JsonResponse::error('Das Passwort muss mindestens 8 Zeichen lang sein.', 400);
                }
                $hash = \password_hash($password, \PASSWORD_DEFAULT);
            } elseif (! $existingUser) {
                return JsonResponse::error('Bei neuen Benutzern muss ein Passwort vergeben werden.', 400);
            }

            // Duplikats-Prüfung
            $checkName = $this->userRepo->findByUsername($usernameStr);
            if ($checkName && $checkName->id !== $id) {
                return JsonResponse::error('Dieser Benutzername ist bereits vergeben.', 400);
            }
            $checkEmail = $this->userRepo->findByEmail($emailStr);
            if ($checkEmail && $checkEmail->id !== $id) {
                return JsonResponse::error('Diese E-Mail wird bereits verwendet.', 400);
            }

            $user = new User(
                $id,
                new Username($usernameStr),
                new EmailAddress($emailStr),
                $hash,
                $roleId,
                $existingUser ? $existingUser->createdAt : new \DateTimeImmutable(),
                $existingUser ? $existingUser->wantsNewsletter : false,
                $existingUser ? $existingUser->wantsNewsletterTranscript : false,
                $existingUser ? $existingUser->wantsNotificationReport : false,
            );

            $this->userRepo->save($user);

            return JsonResponse::success(['message' => "Benutzer '{$usernameStr}' erfolgreich gespeichert."]);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Speichern: ' . $e->getMessage(), 500);
        }
    }
}
