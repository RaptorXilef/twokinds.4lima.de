<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\FileDownloadResponse;
use App\Application\Response\JsonResponse;
use App\Contracts\System\BackupServiceInterface;
use App\Core\Service\AuthService;

#[Route('GET', '/api/download_backup')]
#[RequiresAuth]
final readonly class DownloadBackupAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private BackupServiceInterface $backupService,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.backup.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $filenameRaw = $request->get['file'] ?? '';
        $filenameStr = \is_string($filenameRaw) ? $filenameRaw : '';
        $filename    = \basename($filenameStr);

        if ($filename === '') {
            return JsonResponse::error('Keine Datei angegeben.', 400);
        }

        // Delegiert!
        $content = $this->backupService->getBackupContent($filename);

        if ($content === null) {
            return JsonResponse::error('Datei nicht gefunden.', 404);
        }

        $mimeType = \str_ends_with($filename, '.zip') ? 'application/zip' : 'application/json';

        return new FileDownloadResponse($content, $filename, $mimeType);
    }
}
