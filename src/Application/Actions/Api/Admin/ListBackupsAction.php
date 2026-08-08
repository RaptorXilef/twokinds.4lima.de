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

#[Route('GET', '/api/list_backups')]
#[RequiresAuth]
final readonly class ListBackupsAction implements ActionInterface
{
    public function __construct(private BackupServiceInterface $backupService, private AuthService $auth)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('system.backup.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        return JsonResponse::success([
            'backups' => $this->backupService->listBackups(),
            'tables' => $this->backupService->getAllTables(),
        ]);
    }
}
