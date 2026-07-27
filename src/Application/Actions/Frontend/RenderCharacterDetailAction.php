<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\ValueObject\CharacterId;

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
        $idOrName = \urldecode($request->input['id'] ?? '');

        if ($idOrName === '') {
            return new RedirectResponse('/charaktere');
        }

        $character = null;

        // 1. Ist es eine moderne ID (char_XXXX)?
        if (\preg_match('/^char_\d+$/', $idOrName)) {
            try {
                $character = $this->charRepo->findById(new CharacterId($idOrName));
            } catch (\InvalidArgumentException) {
            }
        } else {
            // 2. Es ist ein alter Name (Legacy Slug)! z.B. "Böse_Aura" oder "Eric_Adrian_Vaughan"
            // Wir suchen den Charakter anhand des Namens aus der alten URL.
            $characterName = \str_replace('_', ' ', $idOrName);
            $allCharacters = $this->charRepo->findAll();

            foreach ($allCharacters as $c) {
                if (\strcasecmp($c->name, $characterName) === 0) {
                    // GEFUNDEN! Wir leiten Google und den User PERMANENT (301) auf die neue URL um!
                    // Dadurch geht kein SEO-Ranking verloren.
                    return new RedirectResponse('/charaktere/' . \urlencode($c->id->value), 301);
                }
            }
        }

        if ($character === null) {
            \http_response_code(404);
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
