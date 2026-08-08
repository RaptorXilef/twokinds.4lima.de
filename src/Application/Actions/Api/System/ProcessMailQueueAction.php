<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\System;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;

#[Route('GET', '/api/process_mail_queue')]
final readonly class ProcessMailQueueAction implements ActionInterface
{
    public function __construct(
        private MailServiceInterface $mailService,
        private ConfigInterface $config,
        private UserRepositoryInterface $userRepository,
        private MagicLinkRepositoryInterface $magicLinkRepository,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $tokenRaw = $request->get['token'] ?? '';
        $providedToken = \is_string($tokenRaw) ? $tokenRaw : (\is_scalar($tokenRaw) ? (string) $tokenRaw : '');

        $cronRaw = $this->config->get('cron_secret', '');
        $expectedCronToken = \is_string($cronRaw) ? $cronRaw : (\is_scalar($cronRaw) ? (string) $cronRaw : '');

        // 1. Sicherheits-Check
        if ($expectedCronToken === '' || $providedToken !== $expectedCronToken) {
            return JsonResponse::error('Unautorisiert.', 403);
        }

        // 2. Mails versenden (inklusive Newsletter)
        $limit = 50;
        $sent = $this->mailService->processQueue($limit, []);

        // 3. Garbage Collection: Abgelaufene Magic Links löschen
        $deletedLinks = $this->magicLinkRepository->deleteExpired();

        // 4. Garbage Collection: Unbestätigte Accounts nach 60 Minuten löschen
        $deletedUsers = $this->userRepository->deleteUnverifiedAccounts(60);

        return JsonResponse::success([
            'status' => 'processed',
            'sent_count' => $sent,
            'deleted_links' => $deletedLinks,
            'deleted_unverified_users' => $deletedUsers,
            'mode' => 'cron',
        ]);
    }
}
