<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Core\Service\AuthService;

#[ActionRoute('api_delete_chapter')]
final readonly class ApiDeleteChapterAction implements ActionInterface
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
            $id = \trim((string) ($request->post['chapter_id'] ?? ''));
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
