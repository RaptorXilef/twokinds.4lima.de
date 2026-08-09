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
use App\Core\Entity\Bookmark;
use App\Core\Entity\ComicPage;
use App\Core\Entity\User;

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
        $idRaw = $request->input['id'] ?? '';
        $id = \is_scalar($idRaw) ? \trim((string) $idRaw) : '';

        if ($id === '') {
            return new RedirectResponse('/');
        }

        $user = $this->userRepo->findById($id);
        if (!$user instanceof User) {
            return $this->renderer->render('pages/frontend/404', ['pageTitle' => 'Benutzer nicht gefunden'], 404);
        }

        $allComics = $this->comicRepo->findAll();

        $userComics = [];
        $bookmarks = [];

        foreach ($allComics as $comic) {
            if (!\in_array($user->id, $comic->userIds, true)) {
                continue;
            }

            $userComics[] = $comic;
        }

        if ($user->publicBookmarks) {
            $bms = $this->bookmarkRepo->findByUser($user->id);
            $bmComicIds = \array_map(fn (Bookmark $bookmark): string => $bookmark->comicId, $bms);

            foreach ($allComics as $comic) {
                if (!\in_array($comic->id->value, $bmComicIds, true)) {
                    continue;
                }

                $bookmarks[] = $comic;
            }
        }

        \usort($userComics, fn (ComicPage $comicA, ComicPage $comicB): int => \strcmp($comicB->id->value, $comicA->id->value)); // phpcs:ignore Generic.Files.LineLength.TooLong
        \usort($bookmarks, fn (ComicPage $comicA, ComicPage $comicB): int => \strcmp($comicB->id->value, $comicA->id->value)); // phpcs:ignore Generic.Files.LineLength.TooLong

        return $this->renderer->render('pages/frontend/user_detail', [
            'pageTitle' => 'Profil von ' . $user->username->value,
            'publicUser' => $user,
            'userComics' => $userComics,
            'bookmarks' => $bookmarks,
        ]);
    }
}
