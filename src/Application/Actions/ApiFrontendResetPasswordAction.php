<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Service\AuthService;
use App\Core\Service\MagicLinkService;

#[ActionRoute('api_frontend_reset_password')]
final readonly class ApiFrontendResetPasswordAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private MagicLinkService $magicLinkService,
        private RateLimiterInterface $rateLimiter,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $ip = $request->getIp();
        if ($this->rateLimiter->isBlocked($ip)) {
            return JsonResponse::error('Zu viele Anfragen. Bitte warten.', 429);
        }

        $token    = $request->post['token'] ?? '';
        $password = (string) ($request->post['password'] ?? '');

        if ($token === '' || $password === '') {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Fehlende Eingaben.', 400);
        }

        if (\strlen($password) < 8) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Das Passwort muss mindestens 8 Zeichen lang sein.', 400);
        }

        // Konsumiert den Token
        $email = $this->magicLinkService->verifyAny($token);
        if (! $email) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Der Link ist ungültig oder abgelaufen.', 400);
        }

        $user = $this->userRepository->findByEmail($email);
        if (! $user) {
            return JsonResponse::error('Benutzer nicht gefunden.', 400);
        }

        $updatedUser = new User($user->id, $user->username, $user->email, \password_hash($password, \PASSWORD_DEFAULT), $user->roleId, $user->createdAt, $user->wantsNewsletter, $user->wantsNewsletterTranscript, $user->wantsNotificationReport);
        $this->userRepository->save($updatedUser);

        $this->rateLimiter->clearAttempts($ip);
        $this->auth->login($user->username, $password, $ip);

        return JsonResponse::success([
            'message'  => 'Passwort erfolgreich geändert! Du wirst eingeloggt...',
            'redirect' => 'lesezeichen',
        ]);
    }
}
