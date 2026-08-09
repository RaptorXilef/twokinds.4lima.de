<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\Entity\User;
use DateTimeImmutable;

#[Route('GET', '/')]
#[Route('GET', '/comic')]
#[Route('GET', '/comic/{id}')]
final readonly class ComicAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private CharacterRepositoryInterface $charRepo,
        private CharacterGroupRepositoryInterface $groupRepo,
        private UserRepositoryInterface $userRepo,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $requestedIdRaw = $request->input['id'] ?? null;
        $requestedId = \is_scalar($requestedIdRaw) ? (string) $requestedIdRaw : null;

        // Hole alle Comics (werden vom Repo standardmäßig DESC sortiert)
        $allComics = $this->comicRepo->findAll();

        if ($allComics === []) {
            return $this->renderer->render('pages/frontend/404', ['pageTitle' => 'Keine Comics gefunden.'], 404);
        }

        $match = $this->findRequestedComic($allComics, $requestedId);
        $comic = $match['comic'];
        $currentIndex = $match['index'];

        if ($comic === null) {
            return $this->renderer->render('pages/frontend/404', ['pageTitle' => 'Comic nicht gefunden.'], 404);
        }

        // Navigation (Achtung: $allComics ist DESC sortiert (Neuester zuerst))
        // Daher ist der Nachbar "Nächste Seite" (älter) bei Index + 1
        $latest = $allComics[0];
        $first = $allComics[\count($allComics) - 1];

        $prev = $currentIndex < \count($allComics) - 1 ? $allComics[$currentIndex + 1] : null;
        $next = $currentIndex > 0 ? $allComics[$currentIndex - 1] : null;

        // --- Datum aus ID extrahieren (ignoriert Buchstaben am Ende) ---
        $dateStr = \substr($comic->id->value, 0, 8);
        $dateObj = DateTimeImmutable::createFromFormat('Ymd', $dateStr);
        $displayDate = $dateObj !== false ? $dateObj->format('d.m.Y') : $dateStr;

        // --- Dynamischer Browser-Tab-Titel ---
        $pageTitle = $comic->name !== '' ? $comic->name : "Seite vom {$displayDate}";
        if (\strtolower($comic->type) !== 'comicseite' && $comic->type !== '') {
            $pageTitle = $comic->type . ': ' . $pageTitle;
        }

        // Charaktere und Gruppen laden
        $characters = $this->charRepo->findAll();
        $groups = $this->groupRepo->findAll();

        // Helfer-Objekte (User) aus den IDs laden
        $comicUsers = [];
        foreach ($comic->userIds as $uid) {
            $user = $this->userRepo->findById($uid);
            if (!$user instanceof User) {
                continue;
            }

            $comicUsers[] = $user;
        }

        return $this->renderer->render('pages/frontend/comic', [
            'comic' => $comic,
            'prev' => $prev,
            'next' => $next,
            'first' => $first,
            'latest' => $latest,
            'isLatest' => ($comic->id->value === $latest->id->value),
            'pageTitle' => $pageTitle,
            'isComicPage' => true,
            'displayDate' => $displayDate,
            'characters' => $characters,
            'groups' => $groups,
            'comicUsers' => $comicUsers,
        ]);
    }

    /**
     * @param array<array-key, ComicPage> $allComics
     *
     * @return array{comic: ?ComicPage, index: int}
     */
    private function findRequestedComic(array $allComics, ?string $requestedId): array
    {
        if ($requestedId === null || $requestedId === '') {
            return ['comic' => $allComics[0], 'index' => 0];
        }

        foreach ($allComics as $index => $c) {
            if ($c->id->value === $requestedId) {
                return ['comic' => $c, 'index' => $index];
            }
        }

        return ['comic' => null, 'index' => -1];
    }
}
