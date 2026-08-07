<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;
use App\Core\Service\MagicLinkService;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;

#[Route('POST', '/api/frontend_register')]
final readonly class RegisterAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private RateLimiterInterface $rateLimiter,
        private UserRepositoryInterface $userRepository,
        private MagicLinkService $magicLinkService,
        private MailServiceInterface $mailService,
        private ConfigInterface $config,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $ip = $request->getIp();

        // 1. Rate Limiting Check
        if ($this->rateLimiter->isBlocked($ip)) {
            return JsonResponse::error('Zu viele Anfragen. Bitte versuche es in 15 Minuten erneut.', 429);
        }

        // Lese die E-Mail-Config aus und baue die ausführliche Meldung
        $mailConfig = $this->config->getMailSettings();
        $fromEmail  = $mailConfig['from'] ?? 'no-reply@twokinds.4lima.de';

        $successMsg = 'Fast geschafft! Ich habe dir einen Bestätigungslink gesendet.<br><br>' .
            '&bull; Du hast <strong>15 Minuten</strong> Zeit, um auf den Link in der E-Mail zu klicken.<br>' .
            '&bull; Bitte prüfe auch deinen <strong>SPAM-Ordner</strong>!<br>' .
            '&bull; Der Absender der E-Mail ist: <strong>' . \htmlspecialchars($fromEmail) . '</strong>';

        // 2. Honeypot Check (Bot-Trap)
        if (! empty($request->post['middle_name'])) {
            // Fake Success for Bots
            return JsonResponse::success(['message' => $successMsg, 'redirect' => 'login']);
        }

        $username        = Sanitizer::string($request->post['username'] ?? '');
        $email           = Sanitizer::email($request->post['email'] ?? '');
        $password        = (string) ($request->post['password'] ?? ''); // Passwörter NIE bereinigen!
        $passwordConfirm = (string) ($request->post['password_confirm'] ?? '');

        if ($username === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Bitte alle Pflichtfelder ausfüllen.', 400);
        }

        // Passwort Abgleich
        if ($password !== $passwordConfirm) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Die Passwörter stimmen nicht überein.', 400);
        }

        if (! \filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Ungültige E-Mail-Adresse.', 400);
        }

        // Admin-Namen vor Registrierung schützen!
        $lowerUsername = \strtolower($username);
        $restricted    = [];
        if ($bd = $this->config->get('backdoor')) {
            $restricted[] = \strtolower($bd['user'] ?? '');
        }
        if ($sa = $this->config->get('superadmin')) {
            $restricted[] = \strtolower($sa['user'] ?? '');
        }

        if (\in_array($lowerUsername, $restricted, true)) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Dieser Benutzername ist systemseitig reserviert.', 400);
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

        // Admin-Namen vor Registrierung schützen! ENDE

        // 3. DNS MX Check (Stoppt Fake-Domains wie asdf123.xyz)
        $domain = \substr(\strrchr($email, '@'), 1);
        if (! \checkdnsrr($domain, 'MX') && ! \checkdnsrr($domain, 'A')) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Die E-Mail-Domain scheint keine E-Mails empfangen zu können.', 400);
        }

        $newId = $this->auth->generateId('usr_');
        $hash  = \password_hash($password, \PASSWORD_DEFAULT);

        // das "false" für den Newsletter
        $user = new User(
            $newId,
            new Username($username),
            new EmailAddress($email),
            $hash,
            'pending',
            new \DateTimeImmutable(),
            false,
            false,
            false,
        );

        $this->userRepository->save($user);

        // Bestätigungs-E-Mail senden
        $tokenData = $this->magicLinkService->createToken($email);
        $verifyUrl = \rtrim($this->config->getBaseUrl(), '/') . '/verifizieren?token=' . $tokenData['token'];

        $this->mailService->sendTemplate($email, 'Bitte bestätige dein Konto', 'verify_account', [
            'verifyUrl' => $verifyUrl,
            'username'  => $username,
        ]);

        // E-Mail sofort aus der Queue werfen!
        $this->mailService->processQueue(5, ['verify_account']);

        return JsonResponse::success([
            'message'  => $successMsg,
            'redirect' => 'login',
        ]);
    }
}
