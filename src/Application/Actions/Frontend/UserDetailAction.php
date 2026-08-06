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

#[Route('GET', '/user/{id}')]
final readonly class UserDetailAction implements ActionInterface
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
        $id = \trim($request->input['id'] ?? '');
        if ($id === '') {
            return new RedirectResponse('/');
        }

        // Wir suchen jetzt nach der fixen ID, nicht mehr nach dem Namen!
        $user = $this->userRepo->findById($id);
        if (! $user) {
            \http_response_code(404);
            $this->renderer->render('pages/frontend/404', ['pageTitle' => 'Benutzer nicht gefunden']);

            return null;
        }

        $allComics = $this->comicRepo->findAll();

        $userComics = [];
        $bookmarks  = [];

        foreach ($allComics as $comic) {
            if (\in_array($user->id, $comic->userIds, true)) {
                $userComics[] = $comic;
            }
        }

        if ($user->publicBookmarks) {
            $bms        = $this->bookmarkRepo->findByUser($user->id);
            $bmComicIds = \array_map(fn ($b) => $b->comicId, $bms);

            foreach ($allComics as $comic) {
                if (\in_array($comic->id->value, $bmComicIds, true)) {
                    $bookmarks[] = $comic;
                }
            }
        }

        \usort($userComics, fn ($a, $b) => \strcmp($b->id->value, $a->id->value));
        \usort($bookmarks, fn ($a, $b) => \strcmp($b->id->value, $a->id->value));

        // Wir rufen das neue Template "user_detail" auf
        $this->renderer->render('pages/frontend/user_detail', [
            'pageTitle'  => 'Profil von ' . $user->username->value,
            'publicUser' => $user,
            'userComics' => $userComics,
            'bookmarks'  => $bookmarks,
        ]);

        return null;
    }
}
