<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\AuthService;
use App\Core\Service\BackupService;

#[ActionRoute('api_restore_backup')]
final readonly class ApiRestoreBackupAction implements ActionInterface
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
            $filename = $request->post['filename'] ?? '';
            $mode     = (int) ($request->post['mode'] ?? 1);
            $table    = ! empty($request->post['table']) && $request->post['table'] !== 'all' ? $request->post['table'] : null;

            if ($filename === '' || ! \in_array($mode, [1, 2, 3], true)) {
                return JsonResponse::error('Ungültige Parameter.', 400);
            }

            // MAGIC: Wir erstellen ein vollautomatisches Sicherheits-Backup VOR der Wiederherstellung
            $safetyBackupFile = $this->backupService->createBackup(null);

            // Jetzt führen wir die eigentlich gewünschte Wiederherstellung durch
            $this->backupService->restoreBackup($filename, $mode, $table);

            return JsonResponse::success([
                'message' => "Sicherheits-Backup angelegt ($safetyBackupFile) & Wiederherstellung war erfolgreich!",
            ]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
