<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;

#[Route('GET', '/archiv')]
final readonly class ArchiveAction implements ViewActionInterface
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
        $groupedComics    = [];
        $unassignedComics = [];

        // 1. Comics sauber trennen
        foreach ($comics as $comic) {
            $chapterId = $comic->chapterId;
            if ($chapterId === null || \trim((string) $chapterId) === '') {
                $unassignedComics[] = $comic;
            } else {
                $groupedComics[$chapterId][] = $comic;
            }
        }

        // 2. Normale Kapitel sortieren (Aufsteigend: 0, 1, 2...)
        \uksort($groupedComics, function (string|int $a, string|int $b): int {
            $numA = \is_numeric($a) ? (float) $a : null;
            $numB = \is_numeric($b) ? (float) $b : null;

            if ($numA !== null && $numB !== null) {
                return $numA <=> $numB; // Aufsteigend
            }
            if ($numA !== null) {
                return -1;
            } // Zahlen immer zuerst
            if ($numB !== null) {
                return 1;
            }

            return \strnatcasecmp((string) $a, (string) $b); // Texte (falls vorhanden) aufsteigend
        });

        // 3. Den Stapel ohne Kapitel GANZ SICHER ans Ende hängen!
        if ($unassignedComics !== []) {
            $groupedComics['Kein Kapitel'] = $unassignedComics;
        }

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
