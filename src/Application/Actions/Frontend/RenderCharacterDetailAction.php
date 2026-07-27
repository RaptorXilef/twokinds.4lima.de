<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;

#[ActionRoute('render_character_detail')]
final readonly class RenderCharacterDetailAction implements ViewActionInterface
{
    public function __construct(
        private CharacterRepositoryInterface $charRepo,
        private ComicRepositoryInterface $comicRepo,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $idStr     = $request->input['id'] ?? '';
        $character = null;

        if ($idStr !== '') {
            try {
                $character = $this->charRepo->findById(new \App\Core\ValueObject\CharacterId($idStr));
            } catch (\InvalidArgumentException) {
                // Ungültiges ID-Format wird abgefangen
            }
        }

        if ($character === null) {
            $this->renderer->render('frontend/404', ['pageTitle' => 'Charakter nicht gefunden']);

            return null;
        }

        // Alle Comics holen und nach Auftritten filtern
        $allComics       = $this->comicRepo->findAll();
        $characterComics = [];

        foreach ($allComics as $comic) {
            foreach ($comic->characterIds as $cidObj) {
                if ($cidObj->value === $character->id->value) {
                    $characterComics[] = $comic;

                    break; // Comic wurde hinzugefügt, ab zum nächsten Comic
                }
            }
        }

        // Chronologisch aufsteigend sortieren (Älteste Auftritte zuerst)
        \usort($characterComics, fn ($a, $b) => $a->id->value <=> $b->id->value);

        $this->renderer->render('frontend/character_detail', [
            'character'       => $character,
            'characterComics' => $characterComics,
            'pageTitle'       => $character->name,
            'siteDescription' => 'Alle Informationen und Comic-Auftritte von ' . $character->name,
        ]);

        return null;
    }
}
