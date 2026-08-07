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

#[Route('POST', '/api/restore_backup')]
#[RequiresAuth]
final readonly class RestoreBackupAction implements ActionInterface
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
            $filenameRaw = $request->post['filename'] ?? '';
            $filename    = \is_string($filenameRaw) ? $filenameRaw : '';

            $modeRaw = $request->post['mode'] ?? 1;
            $mode    = \is_numeric($modeRaw) ? (int) $modeRaw : 1;

            $tableRaw = $request->post['table'] ?? null;
            $table    = \is_string($tableRaw) && $tableRaw !== 'all' && $tableRaw !== '' ? $tableRaw : null;

            // Neues optionales Passwort abfangen
            $passRaw  = $request->post['password'] ?? null;
            $password = \is_string($passRaw) && $passRaw !== '' ? $passRaw : null;

            if ($filename === '' || ! \in_array($mode, [1, 2, 3], true)) {
                return JsonResponse::error('Ungültige Parameter.', 400);
            }

            // MAGIC: Wir erstellen ein vollautomatisches Sicherheits-Backup VOR der Wiederherstellung
            $safetyBackupFile = $this->backupService->createBackup();

            // Jetzt führen wir die eigentlich gewünschte Wiederherstellung durch
            // Passwort an den Service übergeben
            $this->backupService->restoreBackup($filename, $mode, $table, $password);

            return JsonResponse::success([
                'message' => "Sicherheits-Backup angelegt ($safetyBackupFile) & Wiederherstellung war erfolgreich!",
            ]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
