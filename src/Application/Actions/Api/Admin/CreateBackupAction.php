<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\System\BackupServiceInterface;
use App\Core\Service\AuthService;

#[Route('POST', '/api/create_backup')]
#[RequiresAuth]
final readonly class CreateBackupAction implements ActionInterface
{
    public function __construct(private BackupServiceInterface $backupService, private AuthService $auth)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.backup.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $tableNameRaw = $request->post['table'] ?? null;
            $table        = \is_string($tableNameRaw) && $tableNameRaw !== 'all' && $tableNameRaw !== '' ? $tableNameRaw : null;

            $file = $this->backupService->createBackup($table);

            return JsonResponse::success(['message' => "Backup erfolgreich erstellt: $file"]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
