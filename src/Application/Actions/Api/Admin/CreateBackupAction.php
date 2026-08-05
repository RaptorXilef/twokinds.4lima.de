<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\Route;
use App\Application\Attribute\RequiresAuth;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\AuthService;
use App\Core\Service\BackupService;

#[Route('POST', '/api/create_backup')]
#[RequiresAuth]
final readonly class CreateBackupAction implements ActionInterface
{
    public function __construct(private BackupService $backupService, private AuthService $auth)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.backup.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $table = ! empty($request->post['table']) && $request->post['table'] !== 'all' ? $request->post['table'] : null;
            $file  = $this->backupService->createBackup($table);

            return JsonResponse::success(['message' => "Backup erfolgreich erstellt: $file"]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
