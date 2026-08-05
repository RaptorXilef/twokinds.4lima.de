<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\Route;
use App\Application\Attribute\RequiresAuth;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Service\AuthService;

#[Route('POST', '/api/delete_media')]
#[RequiresAuth]
final readonly class DeleteMediaAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('media.delete')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }
        $filename  = \basename((string) ($request->post['filename'] ?? ''));
        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/profiles';
        $filePath  = "$targetDir/$filename";

        if ($filename !== '' && \file_exists($filePath)) {
            \unlink($filePath);

            return JsonResponse::success(['message' => 'Datei gelöscht.']);
        }

        return JsonResponse::error('Datei nicht gefunden.', 404);
    }
}
