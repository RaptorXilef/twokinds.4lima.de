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

#[Route('POST', '/api/delete_backup')]
#[RequiresAuth]
final readonly class DeleteBackupAction implements ActionInterface
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
