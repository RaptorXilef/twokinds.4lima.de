<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\AuthService;
use App\Core\Service\BackupService;

#[ActionRoute('api_delete_backup')]
final readonly class ApiDeleteBackupAction implements ActionInterface
{
    public function __construct(private BackupService $backupService, private AuthService $auth)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.backup.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }
        $filename = $request->post['filename'] ?? '';
        if ($filename !== '') {
            $this->backupService->deleteBackup($filename);

            return JsonResponse::success(['message' => 'Backup gelöscht.']);
        }

        return JsonResponse::error('Dateiname fehlt.', 400);
    }
}
