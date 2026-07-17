<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\ValueObject\ComicId;

#[ActionRoute('render_comic')]
final readonly class ComicRenderAction implements ViewActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepository,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            // Alle Comics laden (Sortierung abwärts: Neueste zuerst)
            $allComics = $this->comicRepository->findAll();
            if (empty($allComics)) {
                return new RedirectResponse('404.php');
            }

            // IDs extrahieren
            $orderedIds = \array_map(fn ($c) => $c->id->value, $allComics);

            $comicIdStr   = $request->input['id'] ?? null;
            $comic        = null;
            $currentIndex = 0;

            if ($comicIdStr !== null) {
                // Suche den exakten Comic aus dem Repo
                $comic = $this->comicRepository->findById(new ComicId($comicIdStr));
                if ($comic !== null) {
                    $currentIndex = \array_search($comicIdStr, $orderedIds, true);
                }
            } else {
                // Keine ID in der URL -> Startseite -> Neuesten Comic laden
                $comic        = \reset($allComics);
                $currentIndex = 0;
            }

            if ($comic === null || $currentIndex === false) {
                $this->renderer->render('404', ['pageTitle' => 'Seite nicht gefunden']);

                return null;
            }

            // Navigation berechnen (Da Array DESC sortiert ist: 0 = Neu, count-1 = Alt)
            $nav = [
                'first' => $orderedIds[\count($orderedIds) - 1], // Der älteste Comic
                'last'  => $orderedIds[0],                       // Der neueste Comic
                'prev'  => $orderedIds[$currentIndex + 1] ?? null, // Älter
                'next'  => $orderedIds[$currentIndex - 1] ?? null, // Neuer
            ];

            $this->renderer->render('comic_page', [
                'comic'     => $comic,
                'pageTitle' => $comic->name !== '' ? $comic->name : "Seite {$comic->id->value}",
                'nav'       => $nav,
            ]);

            return null;

        } catch (ValidationException|\InvalidArgumentException $e) {
            return new RedirectResponse('404.php');
        }
    }
}
