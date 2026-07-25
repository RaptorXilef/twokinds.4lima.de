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

#[ActionRoute('api_frontend_register')]
final readonly class ApiFrontendRegisterAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private RateLimiterInterface $rateLimiter,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $ip = $request->getIp();

        // 1. Rate Limiting Check
        if ($this->rateLimiter->isBlocked($ip)) {
            return JsonResponse::error('Zu viele Anfragen. Bitte versuche es in 15 Minuten erneut.', 429);
        }

        // 2. Honeypot Check (Bot-Trap)
        if (! empty($request->post['middle_name'])) {
            // Fake Success for Bots
            return JsonResponse::success(['message' => 'Registrierung erfolgreich!', 'redirect' => 'login']);
        }

        $username = \trim((string) ($request->post['username'] ?? ''));
        $email    = \trim((string) ($request->post['email'] ?? ''));
        $password = (string) ($request->post['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Bitte alle Pflichtfelder ausfüllen.', 400);
        }

        if (! \filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Ungültige E-Mail-Adresse.', 400);
        }

        // 3. DNS MX Check (Stoppt Fake-Domains wie asdf123.xyz)
        $domain = \substr(\strrchr($email, '@'), 1);
        if ($domain === false || (! \checkdnsrr($domain, 'MX') && ! \checkdnsrr($domain, 'A'))) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Die angegebene E-Mail-Domain scheint keine E-Mails empfangen zu können.', 400);
        }

        if (\strlen($password) < 8) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Das Passwort muss mindestens 8 Zeichen lang sein.', 400);
        }

        if ($this->userRepository->findByUsername($username)) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Dieser Benutzername ist bereits vergeben.', 400);
        }

        if ($this->userRepository->findByEmail($email)) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Diese E-Mail-Adresse wird bereits verwendet.', 400);
        }

        // User anlegen
        $newId = $this->auth->generateId('usr_');
        $hash  = \password_hash($password, \PASSWORD_DEFAULT);

        // Standardrolle ist "user"
        $user = new User($newId, $username, $email, $hash, 'user', new \DateTimeImmutable());
        $this->userRepository->save($user);

        // Direkt einloggen
        $this->auth->login($username, $password, $ip);

        return JsonResponse::success([
            'message'  => 'Registrierung erfolgreich! Du wirst eingeloggt...',
            'redirect' => 'lesezeichen', // Leitet nach Erfolg ins Lesezeichen-Dashboard
        ]);
    }
}
