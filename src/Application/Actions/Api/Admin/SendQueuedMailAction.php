<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Mail\DirectMailServiceInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Service\AuthService;

#[Route('POST', '/api/send_queued_mail')]
#[RequiresAuth]
final readonly class SendQueuedMailAction implements ActionInterface
{
    public function __construct(
        private MailQueueRepositoryInterface $queueRepo,
        private DirectMailServiceInterface $mailService,
        private AuthService $auth,
        private JsonHelperInterface $jsonHelper,
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
        $failCount = 0;
        foreach ($ids as $id) {
            $mail = $this->queueRepo->findById($id);
            if ($mail === null) {
                ++$failCount;
                continue;
            }

            $dataRaw = $mail['data'] ?? [];
            $decoded = \is_string($dataRaw) ? $this->jsonHelper->decode($dataRaw) : (\is_array($dataRaw) ? $dataRaw : []);

            /** @var array<string, mixed> $data */
            $data = [];
            foreach ($decoded as $k => $v) {
                $data[(string) $k] = $v;
            }

            $recipient = \is_string($mail['recipient'] ?? null) ? $mail['recipient'] : '';
            $subject = \is_string($mail['subject'] ?? null) ? $mail['subject'] : '';
            $template = \is_string($mail['template'] ?? null) ? $mail['template'] : '';

            // Versendet die Mail direkt und loggt sie in mail_logs (Da wir SmtpMailService nutzen)
            $result = $this->mailService->sendTemplate($recipient, $subject, $template, $data);

            if ($result === true) {
                // Nach erfolgreichem Versand aus der Queue entfernen
                $this->queueRepo->delete($id);
                ++$successCount;
            } else {
                ++$failCount;
            }
        }
        if ($successCount > 0 && $failCount === 0) {
            return JsonResponse::success(['message' => "Erfolgreich $successCount E-Mail(s) versendet!"]);
        }
        if ($successCount > 0 && $failCount > 0) {
            return JsonResponse::success(['message' => "Aktion abgeschlossen: $successCount versendet, $failCount fehlgeschlagen."]);
        }

        return JsonResponse::error('Versand fehlgeschlagen.', 500);
    }
}
