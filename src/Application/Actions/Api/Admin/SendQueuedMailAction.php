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

        $idRaw = $request->post['id'] ?? '';
        $id = \is_scalar($idRaw) ? \trim((string) $idRaw) : '';

        if ($id === '') {
            return JsonResponse::error('Keine ID übergeben.', 400);
        }

        $mail = $this->queueRepo->findById($id);
        if ($mail === null) {
            return JsonResponse::error('E-Mail nicht in der Warteschlange gefunden.', 404);
        }

        $dataRaw = $mail['data'] ?? [];

        /** @var array<string, mixed> $data */
        $data = \is_string($dataRaw) ? $this->jsonHelper->decode($dataRaw) : (\is_array($dataRaw) ? $dataRaw : []);

        $recipient = \is_string($mail['recipient'] ?? null) ? $mail['recipient'] : '';
        $subject = \is_string($mail['subject'] ?? null) ? $mail['subject'] : '';
        $template = \is_string($mail['template'] ?? null) ? $mail['template'] : '';

        // Versendet die Mail direkt und loggt sie in mail_logs (Da wir SmtpMailService nutzen)
        $result = $this->mailService->sendTemplate($recipient, $subject, $template, $data);

        if ($result === true) {
            // Nach erfolgreichem Versand aus der Queue entfernen
            $this->queueRepo->delete($id);

            return JsonResponse::success(['message' => 'E-Mail wurde erfolgreich versendet!']);
        }

        return JsonResponse::error('Versand fehlgeschlagen: ' . $result, 500);
    }
}
