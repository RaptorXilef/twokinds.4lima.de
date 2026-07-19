<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Core\Entity\Chapter;

#[ActionRoute('api_save_chapter')]
final readonly class ApiSaveChapterAction implements ActionInterface
{
    public function __construct(private ChapterRepositoryInterface $chapterRepo)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $id    = \trim((string) ($request->post['chapter_id'] ?? ''));
            $title = \trim((string) ($request->post['title'] ?? ''));
            $desc  = \trim((string) ($request->post['description'] ?? ''));

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
