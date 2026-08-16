<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailLogInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\MailJob;
use App\Core\Service\AuthService;
use App\Core\Service\MagicLinkService;
use DateTimeImmutable;

#[Route('POST', '/api/requeue_mail')]
#[RequiresAuth]
final readonly class RequeueMailAction implements ActionInterface
{
    public function __construct(
        private MailLogInterface $logRepo,
        private MailQueueRepositoryInterface $queueRepo,
        private AuthService $auth,
        private JsonHelperInterface $jsonHelper,
        private MagicLinkService $magicLinkService,
        private ConfigInterface $config,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('system.manage') && !$this->auth->hasPermission('admin.access')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        \set_time_limit(0);
        $idRaw = $request->post['id'] ?? '';
        $idStr = \is_scalar($idRaw) ? \trim((string) $idRaw) : '';
        $ids = \array_filter(\array_map('trim', \explode(',', $idStr)), fn ($val): bool => $val !== '');
        if ($ids === []) {
            return JsonResponse::error('Keine ID übergeben.', 400);
        }

        $successCount = 0;
        foreach ($ids as $id) {
            $log = $this->logRepo->findById($id);
            if ($log === null) {
                continue;
            }

            $dataRaw = $log['data'] ?? [];
            /** @var array<string, mixed> $data */
            $data = \is_string($dataRaw) ? $this->jsonHelper->decode($dataRaw) : (\is_array($dataRaw) ? $dataRaw : []);
            $template = \is_string($log['template'] ?? null) ? $log['template'] : '';

            // Dynamische Tokens erneuern
            if (\in_array($template, ['verify_account', 'forgot_password', 'verify_new_email'], true)) {
                $data = $this->regenerateSecurityUrls($template, $log, $data);
            }

            // Packe sie mit extrem hoher Priorität (100) als neuen Job in die Queue
            $job = new MailJob(
                \uniqid('mq_'),
                \is_string($log['recipient'] ?? null) ? $log['recipient'] : '',
                \is_string($log['subject'] ?? null) ? $log['subject'] : '',
                $template,
                $data,
                0,
                100,
                new DateTimeImmutable(),
            );

            $this->queueRepo->enqueue($job);
            ++$successCount;
        }
        if ($successCount > 0) {
            return JsonResponse::success(['message' => "$successCount E-Mail(s) zur erneuten Verarbeitung eingereiht!"]);
        }

        return JsonResponse::error('Keine gültigen E-Mails im Verlauf gefunden.', 404);
    }

    /**
     * Dynamische Tokens für Sicherheits-Mails erneuern
     *
     * @param array<string, mixed> $log
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function regenerateSecurityUrls(string $template, array $log, array $data): array
    {
        $recipient = \is_string($log['recipient'] ?? null) ? $log['recipient'] : '';
        $tokenData = $this->magicLinkService->createToken($recipient);
        $baseUrl = \rtrim($this->config->getBaseUrl(), '/');

        if ($template === 'verify_account') {
            $data['verifyUrl'] = $baseUrl . '/verifizieren?token=' . $tokenData['token'];
        } elseif ($template === 'forgot_password') {
            $data['resetUrl'] = $baseUrl . '/passwort-reset?token=' . $tokenData['token'];
        } elseif ($template === 'verify_new_email') {
            $data['verifyUrl'] = $baseUrl . '/email-bestaetigen?token=' . $tokenData['token'];
        }

        return $data;
    }
}
