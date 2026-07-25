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

#[ActionRoute('api_frontend_resend_verification')]
final readonly class ApiFrontendResendVerificationAction implements ActionInterface
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
            return JsonResponse::success(['message' => 'Falls ein unbestätigtes Konto existiert, wurde eine E-Mail versendet.']);
        }

        $email = \trim((string) ($request->post['email'] ?? ''));
        if ($email === '') {
            $this->rateLimiter->recordFailedAttempt($ip);

            return JsonResponse::error('Bitte eine E-Mail eingeben.', 400);
        }

        $user = $this->userRepository->findByEmail($email);

        // E-Mail nur senden, wenn User existiert UND noch auf 'pending' steht!
        if ($user && $user->roleId === 'pending') {
            $tokenData = $this->magicLinkService->createToken($email);
            $verifyUrl = \rtrim($this->config->getBaseUrl(), '/') . '/verifizieren?token=' . $tokenData['token'];

            $this->mailService->sendTemplate($email, 'Bitte bestätige dein Konto', 'verify_account', [
                'verifyUrl' => $verifyUrl,
                'username'  => $user->username,
            ]);

            // Sofortiger Versand
            $this->mailService->processQueue(5, ['verify_account']);
        } else {
            // Anti user-enumeration: Künstliche Pause
            \usleep(\random_int(100000, 300000));
        }

        // Immer Erfolgsmeldung zeigen (aus Sicherheitsgründen)
        return JsonResponse::success(['message' => 'Falls ein unbestätigtes Konto existiert, wurde eine neue E-Mail versendet.']);
    }
}
