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

#[Route('POST', '/api/delete_comic_media')]
#[RequiresAuth]
final readonly class DeleteComicMediaAction implements ActionInterface
{
    public function __construct(
        private ImageStorageInterface $imageStorage,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('media.delete') && ! $this->auth->hasPermission('comics.delete')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $id = \basename((string) ($request->post['comic_id'] ?? ''));
        if ($id === '') {
            return JsonResponse::error('Keine ID übergeben.', 400);
        }

        // Komplette Logik an die Infrastruktur delegiert!
        $deleted = $this->imageStorage->deleteComicMedia($id);

        if ($deleted > 0) {
            return JsonResponse::success(['message' => "Erfolgreich $deleted Dateiversionen gelöscht."]);
        }

        return JsonResponse::error('Keine Dateien zu dieser ID gefunden.', 404);
    }
}
