<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Mail\MailLogInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\MailJob;
use App\Core\Service\AuthService;

#[Route('POST', '/api/requeue_mail')]
#[RequiresAuth]
final readonly class RequeueMailAction implements ActionInterface
{
    public function __construct(
        private MailLogInterface $logRepo,
        private MailQueueRepositoryInterface $queueRepo,
        private AuthService $auth,
        private JsonHelperInterface $jsonHelper,
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

        $log = $this->logRepo->findById($id);
        if (! $log) {
            return JsonResponse::error('E-Mail nicht im Verlauf gefunden.', 404);
        }

        $data = \is_string($log['data']) ? $this->jsonHelper->decode($log['data']) : $log['data'];

        // Packe sie mit extrem hoher Priorität (100) als neuen Job in die Queue
        $job = new MailJob(
            \uniqid('mq_'),
            $log['recipient'],
            $log['subject'],
            $log['template'],
            $data,
            0,
            100,
            new \DateTimeImmutable(),
        );

        $this->queueRepo->enqueue($job);

        return JsonResponse::success(['message' => 'Die E-Mail wurde zur erneuten Verarbeitung in die Warteschlange eingereiht!']);
    }
}
