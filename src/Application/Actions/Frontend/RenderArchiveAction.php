<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;

#[ActionRoute('render_archive')]
final readonly class RenderArchiveAction implements ViewActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepository,
        private ChapterRepositoryInterface $chapterRepository,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $comics   = $this->comicRepository->findAll();
        $chapters = $this->chapterRepository->findAll();

        // Comics nach Kapitel gruppieren
        $groupedComics = [];
        foreach ($comics as $comic) {
            $chapterId                   = $comic->chapterId ?? 'Kein Kapitel';
            $groupedComics[$chapterId][] = $comic;
        }

        // Wir sortieren die Kapitel-IDs absteigend (neueste oben)
        \krsort($groupedComics);

        // Die Kapitel-Informationen (Titel/Beschreibung) als einfaches Dictionary aufbauen
        $chapterDetails = [];
        foreach ($chapters as $chapter) {
            $chapterDetails[$chapter->id] = $chapter;
        }

        $this->renderer->render('frontend/archive', [
            'groupedComics'   => $groupedComics,
            'chapterDetails'  => $chapterDetails,
            'pageTitle'       => 'Archiv',
            'siteDescription' => 'Das vollständige Archiv der deutschen Übersetzung.',
        ]);

        return null;
    }
}
