<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\System\ImageStorageInterface;
use App\Core\Service\AuthService;

#[Route('POST', '/api/delete_media')]
#[RequiresAuth]
final readonly class DeleteMediaAction implements ActionInterface
{
    public function __construct(
        private ImageStorageInterface $imageStorage,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('media.delete')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $filenameRaw = $request->post['filename'] ?? '';
        $filenameStr = \is_string($filenameRaw) ? $filenameRaw : '';
        $filename    = \basename($filenameStr);

        if ($filename !== '' && $this->imageStorage->deleteCharacterMedia('profiles', $filename)) {
            return JsonResponse::success(['message' => 'Datei gelöscht.']);
        }

        return JsonResponse::error('Datei nicht gefunden.', 404);
    }
}
