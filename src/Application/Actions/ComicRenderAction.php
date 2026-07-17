<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
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
        $comicIdStr = $request->input['id'] ?? null;
        $comic      = null;

        if ($comicIdStr !== null) {
            try {
                $comic = $this->comicRepository->findById(new ComicId($comicIdStr));
            } catch (\InvalidArgumentException) {
                $comic = null; // Ungültiges Format
            }
        } else {
            // Keine ID in der URL -> Startseite -> Neuesten Comic laden
            $allComics = $this->comicRepository->findAll();
            $comic     = \reset($allComics) ?: null;
        }

        if ($comic === null) {
            $this->renderer->render('404', ['pageTitle' => 'Seite nicht gefunden']);

            return null;
        }

        $this->renderer->render('comic_page', [
            'comic'     => $comic,
            'pageTitle' => $comic->name !== '' ? $comic->name : "Seite {$comic->id->value}",
        ]);

        return null;
    }
}
