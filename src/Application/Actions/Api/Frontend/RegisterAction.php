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
use DateTimeImmutable;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
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
        $fromEmail = \is_string($mailConfig['from'] ?? null) ? $mailConfig['from'] : 'no-reply@twokinds.4lima.de';

        $successMsg = 'Fast geschafft! Ich habe dir einen Bestätigungslink gesendet.<br><br>' .
            '&bull; Du hast <strong>15 Minuten</strong> Zeit, um auf den Link in der E-Mail zu klicken.<br>' .
            '&bull; Bitte prüfe auch deinen <strong>SPAM-Ordner</strong>!<br>' .
            '&bull; Der Absender der E-Mail ist: <strong>' . \htmlspecialchars($fromEmail) . '</strong>';

        // 2. Honeypot Check (Bot-Trap)
        $honeypot = $request->post['middle_name'] ?? '';
        if ($honeypot !== '') {
            // Fake Success for Bots
            return JsonResponse::success(['message' => $successMsg, 'redirect' => 'login']);
        }

        $username = Sanitizer::string($request->post['username'] ?? '');
        $email = Sanitizer::email($request->post['email'] ?? '');
        $passRaw = $request->post['password'] ?? ''; // Passwörter NIE bereinigen!
        $password = \is_scalar($passRaw) ? (string) $passRaw : '';
        $confirmRaw = $request->post['password_confirm'] ?? '';
        $passwordConfirm = \is_scalar($confirmRaw) ? (string) $confirmRaw : '';

        $error = $this->validateInput($username, $email, $password, $passwordConfirm);
        if ($error !== null) {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error($error, 400);
        }

        $this->createUserAndSendMail($username, $email, $password);

        return JsonResponse::success([
            'message' => $successMsg,
            'redirect' => 'login',
        ]);
    }

    // =========================================================================
    // PRIVATE HELPER
    // =========================================================================

    private function validateInput(string $username, string $email, string $password, string $confirm): ?string
    {
        if ($username === '' || $email === '' || $password === '' || $confirm === '') {
            return 'Bitte alle Pflichtfelder ausfüllen.';
        }

        if ($password !== $confirm) {
            return 'Die Passwörter stimmen nicht überein.';
        }

        if (\strlen($password) < 8) {
            return 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        }

        if (\filter_var($email, \FILTER_VALIDATE_EMAIL) === false) {
            return 'Ungültige E-Mail-Adresse.';
        }

        if ($this->isRestrictedUsername($username)) {
            return 'Dieser Benutzername ist systemseitig reserviert.';
        }

        if ($this->userRepository->findByUsername($username) instanceof User) {
            return 'Dieser Benutzername ist bereits vergeben.';
        }

        if ($this->userRepository->findByEmail($email) instanceof User) {
            return 'Diese E-Mail-Adresse wird bereits verwendet.';
        }

        // 3. DNS MX Check (Stoppt Fake-Domains wie asdf123.xyz)
        $domainStr = \strrchr($email, '@');
        $domain = \is_string($domainStr) ? \substr($domainStr, 1) : '';

        if ($domain === '' || (!\checkdnsrr($domain, 'MX') && !\checkdnsrr($domain, 'A'))) {
            return 'Die E-Mail-Domain scheint keine E-Mails empfangen zu können.';
        }

        return null;
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

        $superadminCfg = $this->config->get('superadmin');
        if (\is_array($superadminCfg)) {
            $saUser = \is_string($superadminCfg['user'] ?? null) ? $superadminCfg['user'] : '';
            $restricted[] = \strtolower($saUser);
        }

        return \in_array($lowerName, $restricted, true);
    }

    private function createUserAndSendMail(string $username, string $email, string $password): void
    {
        $newId = $this->auth->generateId('usr_');
        $hash = \password_hash($password, \PASSWORD_DEFAULT);

        // das "false" für den Newsletter
        $user = new User(
            $newId,
            new Username($username),
            new EmailAddress($email),
            $hash,
            'pending',
            new DateTimeImmutable(),
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
            'username' => $username,
        ]);

        // E-Mail sofort aus der Queue werfen!
        $this->mailService->processQueue(5, ['verify_account']);
    }
}
