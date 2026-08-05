<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Frontend;

use App\Application\Attribute\Route;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\MagicLinkService;

#[Route('POST', '/api/frontend_forgot_password')]
final readonly class ForgotPasswordAction implements ActionInterface
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
        $fromEmail  = $mailConfig['from'] ?? 'no-reply@twokinds.4lima.de';
        $successMsg = 'Falls diese E-Mail existiert, habe ich dir einen Reset-Link gesendet.<br><br>' .
                      '&bull; Der Link ist <strong>15 Minuten</strong> gültig.<br>' .
                      '&bull; Bitte prüfe auch deinen <strong>SPAM-Ordner</strong>!<br>' .
                      '&bull; Der Absender der E-Mail ist: <strong>' . \htmlspecialchars($fromEmail) . '</strong>';

        // Honeypot
        if (! empty($request->post['middle_name'])) {
            return JsonResponse::success(['message' => $successMsg]);
        }

        $email = \trim((string) ($request->post['email'] ?? ''));
        if ($email === '') {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Bitte eine E-Mail eingeben.', 400);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            $tokenData = $this->magicLinkService->createToken($email);
            $resetUrl  = \rtrim($this->config->getBaseUrl(), '/') . '/passwort-reset?token=' . $tokenData['token'];
            $this->mailService->sendTemplate($user->email->value, 'Passwort zurücksetzen', 'forgot_password', [
                'resetUrl' => $resetUrl,
                'username' => $user->username->value,
            ]);

            // NEU: Triggere den Versand SOFORT, aber NUR für die Passwort-Mails!
            $this->mailService->processQueue(5, ['forgot_password']);
        } else {
            // Anti user-enumeration
            \usleep(\random_int(100000, 300000));
        }

        return JsonResponse::success(['message' => $successMsg]);
    }
}
