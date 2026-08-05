<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Service\AuthService;

#[Route('POST', '/api/send_queued_mail')]
#[RequiresAuth]
final readonly class SendQueuedMailAction implements ActionInterface
{
    public function __construct(
        private MailQueueRepositoryInterface $queueRepo,
        private MailServiceInterface $mailService,
        private AuthService $auth,
        private JsonHelperInterface $jsonHelper,
        private \PDO $pdo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.manage') && ! $this->auth->hasPermission('admin.access')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $id = \trim((string) ($request->post['id'] ?? ''));
        if ($id === '') {
            return JsonResponse::error('Keine ID übergeben.', 400);
        }

        $mail = $this->queueRepo->findById($id);
        if (! $mail) {
            return JsonResponse::error('E-Mail nicht in der Warteschlange gefunden.', 404);
        }

        $data = \is_string($mail['data']) ? $this->jsonHelper->decode($mail['data']) : $mail['data'];

        // Versendet die Mail direkt und loggt sie in mail_logs (via SmtpMailService)
        $result = $this->mailService->sendTemplate($mail['recipient'], $mail['subject'], $mail['template'], $data);

        if ($result === true) {
            // Nach erfolgreichem Versand aus der Queue entfernen
            $this->pdo->prepare('DELETE FROM `mail_queue` WHERE id = ?')->execute([$id]);

            return JsonResponse::success(['message' => 'E-Mail wurde erfolgreich versendet!']);
        }

        return JsonResponse::error('Versand fehlgeschlagen: ' . $result, 500);
    }
}
