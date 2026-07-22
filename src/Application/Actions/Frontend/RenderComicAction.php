<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\ComicRepositoryInterface;

#[ActionRoute('render_comic')]
final readonly class RenderComicAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $requestedId = $request->input['id'] ?? null;

        // Hole alle Comics (werden vom Repo standardmäßig DESC sortiert)
        $allComics = $this->comicRepo->findAll();

        if (empty($allComics)) {
            $this->renderer->render('frontend/404', ['pageTitle' => 'Keine Comics gefunden.']);

            return null;
        }

        $comic        = null;
        $currentIndex = -1;

        if ($requestedId) {
            foreach ($allComics as $index => $c) {
                if ($c->id->value === $requestedId) {
                    $comic        = $c;
                    $currentIndex = $index;

                    break;
                }
            }
        } else {
            // Neuesten Comic laden
            $comic        = $allComics[0];
            $currentIndex = 0;
        }

        if (! $comic) {
            $this->renderer->render('frontend/404', ['pageTitle' => 'Comic nicht gefunden.']);

            return null;
        }

        // Navigation (Achtung: $allComics ist DESC sortiert (Neuester zuerst))
        // Daher ist der Nachbar "Nächste Seite" (älter) bei Index + 1
        $latest = $allComics[0];
        $first  = $allComics[\count($allComics) - 1];

        $prev = ($currentIndex < \count($allComics) - 1) ? $allComics[$currentIndex + 1] : null;
        $next = ($currentIndex > 0) ? $allComics[$currentIndex - 1] : null;

        // --- Datum aus ID extrahieren (ignoriert Buchstaben am Ende) ---
        $dateStr     = \substr($comic->id->value, 0, 8);
        $dateObj     = \DateTimeImmutable::createFromFormat('Ymd', $dateStr);
        $displayDate = $dateObj ? $dateObj->format('d.m.Y') : $dateStr;

        // --- Dynamischer Browser-Tab-Titel ---
        $pageTitle = $comic->name !== '' ? $comic->name : "Seite vom {$displayDate}";
        if (\strtolower($comic->type) !== 'comicseite' && $comic->type !== '') {
            $pageTitle = $comic->type . ': ' . $pageTitle;
        }

        $this->renderer->render('frontend/comic', [
            'comic'       => $comic,
            'prev'        => $prev,
            'next'        => $next,
            'first'       => $first,
            'latest'      => $latest,
            'isLatest'    => ($comic->id->value === $latest->id->value),
            'pageTitle'   => $pageTitle,
            'isComicPage' => true,
            'displayDate' => $displayDate, // An Template übergeben
        ]);

        return null;
    }
}
