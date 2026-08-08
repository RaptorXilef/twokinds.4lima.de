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
use App\Core\Service\MagicLinkService;

#[Route('POST', '/api/frontend_resend_verification')]
final readonly class ResendVerificationAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private MagicLinkService $magicLinkService,
        private MailServiceInterface $mailService,
        private RateLimiterInterface $rateLimiter,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $ip = $request->getIp();
        if ($this->rateLimiter->isBlocked($ip)) {
            return JsonResponse::error('Zu viele Anfragen. Bitte versuche es in 15 Minuten erneut.', 429);
        }

        // Lese die E-Mail-Config aus und baue die ausführliche Meldung
        $mailConfig = $this->config->getMailSettings();
        $fromEmail = \is_string($mailConfig['from'] ?? null) ? $mailConfig['from'] : 'no-reply@twokinds.4lima.de';

        $successMsg = 'Falls ein unbestätigtes Konto existiert, wurde eine neue E-Mail an dich versendet.<br><br>' .
            '&bull; Der Link ist <strong>15 Minuten</strong> gültig.<br>' .
            '&bull; Bitte prüfe auch deinen <strong>SPAM-Ordner</strong>!<br>' .
            '&bull; Der Absender der E-Mail ist: <strong>' . \htmlspecialchars($fromEmail) . '</strong>';

        $honeypot = $request->post['middle_name'] ?? '';
        if ($honeypot !== '') {
            return JsonResponse::success(['message' => $successMsg]);
        }

        $emailRaw = $request->post['email'] ?? '';
        $email = \is_scalar($emailRaw) ? \trim((string) $emailRaw) : '';

        if ($email === '') {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Bitte eine E-Mail eingeben.', 400);
        }

        $user = $this->userRepository->findByEmail($email);

        // E-Mail nur senden, wenn User existiert UND noch auf 'pending' steht!
        if ($user instanceof User && $user->roleId === 'pending') {
            $tokenData = $this->magicLinkService->createToken($email);
            $verifyUrl = \rtrim($this->config->getBaseUrl(), '/') . '/verifizieren?token=' . $tokenData['token'];

            $this->mailService->sendTemplate($user->email->value, 'Bitte bestätige dein Konto', 'verify_account', [
                'verifyUrl' => $verifyUrl,
                'username' => $user->username->value,
            ]);

            // Sofortiger Versand
            $this->mailService->processQueue(5, ['verify_account']);
        }

        // Immer Erfolgsmeldung zeigen (aus Sicherheitsgründen)
        return JsonResponse::success(['message' => $successMsg]);
    }
}
