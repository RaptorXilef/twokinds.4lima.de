<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\Character;
use App\Core\ValueObject\CharacterId;
use InvalidArgumentException;

#[Route('GET', '/charaktere/{id}')]
final readonly class CharacterDetailAction implements ActionInterface
{
    public function __construct(
        private CharacterRepositoryInterface $charRepo,
        private ComicRepositoryInterface $comicRepo,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $idRaw = $request->input['id'] ?? '';
        $idOrName = \urldecode(\is_scalar($idRaw) ? (string) $idRaw : '');

        if ($idOrName === '') {
            return new RedirectResponse('/charaktere');
        }

        $character = $this->resolveCharacter($idOrName);

        if ($character instanceof RedirectResponse) {
            return $character;
        }

        if (!$character instanceof Character) {
            return $this->renderer->render('pages/frontend/404', ['pageTitle' => 'Charakter nicht gefunden'], 404);
        }

        // Alle Comics holen und nach Auftritten filtern
        $allComics = $this->comicRepo->findAll();
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
        \usort($characterComics, fn ($comicA, $comicB): int => $comicA->id->value <=> $comicB->id->value);

        return $this->renderer->render('pages/frontend/character_detail', [
            'character' => $character,
            'characterComics' => $characterComics,
            'pageTitle' => $character->name,
            'siteDescription' => 'Alle Informationen und Comic-Auftritte von ' . $character->name,
        ]);
    }

    private function resolveCharacter(string $idOrName): Character|RedirectResponse|null
    {
        // 1. Ist es eine moderne ID (char_XXXX)?
        if (\preg_match('/^char_\d+$/', $idOrName) === 1) {
            try {
                return $this->charRepo->findById(new CharacterId($idOrName));
            } catch (InvalidArgumentException) {
                // Ignore invalid ID exception and fallback to legacy search
            }
        }

        // 2. Es ist ein alter Name (Legacy Slug)!
        return $this->findCharacterByLegacyName($idOrName);
    }

    private function findCharacterByLegacyName(string $idOrName): ?RedirectResponse
    {
        $characterName = \trim(\str_replace('_', ' ', $idOrName));
        $allCharacters = $this->charRepo->findAll();

        // Prio A: Exakter Treffer (ignoriert Groß-/Kleinschreibung)
        foreach ($allCharacters as $c) {
            if (\strcasecmp($c->name, $characterName) === 0) {
                return new RedirectResponse('/charaktere/' . \urlencode($c->id->value), 301);
            }
        }

        // Prio B: Beginnt mit dem Suchwort (z.B. "/Flora" findet "Flora - Regenwald-Tigerstamm")
        foreach ($allCharacters as $c) {
            if (\stripos($c->name, $characterName) === 0) {
                return new RedirectResponse('/charaktere/' . \urlencode($c->id->value), 301);
            }
        }

        // Prio C: Beinhaltet das Suchwort irgendwo
        foreach ($allCharacters as $c) {
            if (\stripos($c->name, $characterName) !== false) {
                return new RedirectResponse('/charaktere/' . \urlencode($c->id->value), 301);
            }
        }

        return null;
    }
}
