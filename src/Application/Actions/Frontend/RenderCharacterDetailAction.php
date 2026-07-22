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
        $charNameRaw = $request->input['char_name'] ?? '';
        // Der Name aus der URL hat Unterstriche statt Leerzeichen (z.B. "Trace_Legacy")
        $characterName = \str_replace('_', ' ', $charNameRaw);

        $allCharacters = $this->charRepo->findAll();
        $character     = null;

        // Charakter anhand des Namens suchen
        foreach ($allCharacters as $c) {
            if (\strcasecmp($c->name, $characterName) === 0) {
                $character = $c;

                break;
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
