<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\FileDownloadResponse;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Service\AuthService;

#[ActionRoute('api_download_backup')]
final readonly class ApiDownloadBackupAction implements ActionInterface
{
    public function __construct(private AuthService $auth, private ConfigInterface $config)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.backup.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }
        $filename = \basename($request->get['file'] ?? '');
        if ($filename === '') {
            return JsonResponse::error('Keine Datei angegeben.', 400);
        }

        $filepath = \rtrim((string) $this->config->get('root_path'), '/\\') . '/var/backups/' . $filename;
        if (! \file_exists($filepath)) {
            return JsonResponse::error('Datei nicht gefunden.', 404);
        }

        return new FileDownloadResponse(\file_get_contents($filepath), $filename, 'application/json');
    }
}
