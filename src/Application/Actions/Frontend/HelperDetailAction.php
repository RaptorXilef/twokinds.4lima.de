<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;

#[Route('GET', '/helfer/{username}')]
final readonly class HelperDetailAction implements ActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private ComicRepositoryInterface $comicRepo,
        private BookmarkRepositoryInterface $bookmarkRepo,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $username = \urldecode($request->input['username'] ?? '');
        if ($username === '') {
            return new RedirectResponse('/');
        }

        $user = $this->userRepo->findByUsername($username);
        if (! $user) {
            \http_response_code(404);
            $this->renderer->render('frontend/404', ['pageTitle' => 'Helfer nicht gefunden']);

            return null;
        }

        $allComics = $this->comicRepo->findAll();

        $helperComics = [];
        $bookmarks    = [];

        // Suche alle Comics, bei denen der User mitgeholfen hat
        foreach ($allComics as $comic) {
            if (\in_array($user->id, $comic->helperIds, true)) {
                $helperComics[] = $comic;
            }
        }

        // Falls Lesezeichen öffentlich sind, bereite sie vor
        if ($user->publicBookmarks) {
            $bms        = $this->bookmarkRepo->findByUser($user->id);
            $bmComicIds = \array_map(fn ($b) => $b->comicId, $bms);

            foreach ($allComics as $comic) {
                if (\in_array($comic->id->value, $bmComicIds, true)) {
                    $bookmarks[] = $comic;
                }
            }
        }

        // Sortiere absteigend (neueste zuerst)
        \usort($helperComics, fn ($a, $b) => \strcmp($b->id->value, $a->id->value));
        \usort($bookmarks, fn ($a, $b) => \strcmp($b->id->value, $a->id->value));

        $this->renderer->render('frontend/helper_detail', [
            'pageTitle'    => 'Profil von ' . $user->username->value,
            'helperUser'   => $user,
            'helperComics' => $helperComics,
            'bookmarks'    => $bookmarks,
        ]);

        return null;
    }
}
