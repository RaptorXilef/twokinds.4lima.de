<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\System;

use App\Application\Attribute\Route;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Service\BackupService;

#[Route('GET', '/api/cron_backup')]
final readonly class CronBackupAction implements ActionInterface
{
    public function __construct(private BackupService $backupService, private ConfigInterface $config)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        $token  = $request->get['token'] ?? '';
        $secret = $this->config->get('cron_secret', '');

        if ($secret === '' || $token !== $secret) {
            return JsonResponse::error('Unautorisiert. Token ungültig.', 403);
        }

        try {
            $filename = $this->backupService->createBackup();

            return JsonResponse::success(['message' => "Automatisches Backup erstellt: $filename"]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
