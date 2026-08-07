<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Core\Entity\Chapter;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;

#[Route('POST', '/api/save_chapter')]
#[RequiresAuth]
final readonly class SaveChapterAction implements ActionInterface
{
    public function __construct(
        private ChapterRepositoryInterface $chapterRepo,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('chapters.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $id    = Sanitizer::string($request->post['chapter_id'] ?? '');
            $title = Sanitizer::string($request->post['title'] ?? '');
            $desc  = Sanitizer::html($request->post['description'] ?? ''); // HTML erlaubt

            if ($id === '' || $title === '') {
                return JsonResponse::error('Kapitel-ID und Titel sind Pflichtfelder.', 400);
            }

            $this->chapterRepo->save(new Chapter($id, $title, $desc));

            return JsonResponse::success(['message' => "Kapitel '{$id}' erfolgreich gespeichert."]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Speichern: ' . $e->getMessage(), 500);
        }
    }
}
