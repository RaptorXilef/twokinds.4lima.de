<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\MagicLinkService;

#[ActionRoute('api_frontend_forgot_password')]
final readonly class ApiFrontendForgotPasswordAction implements ActionInterface
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

        // Honeypot
        if (! empty($request->post['middle_name'])) {
            return JsonResponse::success(['message' => 'Falls die E-Mail existiert, haben wir einen Link gesendet.']);
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
            $this->mailService->sendTemplate($email, 'Passwort zurücksetzen', 'forgot_password', [
                'resetUrl' => $resetUrl,
                'username' => $user->username,
            ]);

            // NEU: Triggere den Versand SOFORT, aber NUR für die Passwort-Mails!
            $this->mailService->processQueue(5, ['forgot_password']);
        } else {
            // Anti user-enumeration
            \usleep(\random_int(100000, 300000));
        }

        return JsonResponse::success(['message' => 'Falls die E-Mail existiert, haben wir einen Link gesendet.']);
    }
}
