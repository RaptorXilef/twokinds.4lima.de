<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Core\Service\AuthService;

#[Route('POST', '/api/delete_chapter')]
#[RequiresAuth]
final readonly class DeleteChapterAction implements ActionInterface
{
    public function __construct(
        private ChapterRepositoryInterface $chapterRepo,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('chapters.delete')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $idRaw = $request->post['chapter_id'] ?? '';
            $id    = \is_scalar($idRaw) ? \trim((string) $idRaw) : '';

            if ($id === '') {
                return JsonResponse::error('Keine ID übermittelt.', 400);
            }

            $this->chapterRepo->delete($id);

            return JsonResponse::success(['message' => "Kapitel '{$id}' erfolgreich gelöscht."]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Löschen: ' . $e->getMessage(), 500);
        }
    }
}
