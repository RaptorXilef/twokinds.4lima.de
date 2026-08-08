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

#[Route('POST', '/api/delete_backup')]
#[RequiresAuth]
final readonly class DeleteBackupAction implements ActionInterface
{
    public function __construct(private BackupServiceInterface $backupService, private AuthService $auth)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('system.backup.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $filenameRaw = $request->post['filename'] ?? '';
        $filename = \is_string($filenameRaw) ? $filenameRaw : '';

        if ($filename !== '') {
            $this->backupService->deleteBackup($filename);

            return JsonResponse::success(['message' => 'Backup gelöscht.']);
        }

        return JsonResponse::error('Dateiname fehlt.', 400);
    }
}
