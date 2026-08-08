<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Service\AuthService;

#[Route('GET', '/lesezeichen')]
final readonly class BookmarksAction implements ActionInterface
{
    public function __construct(
        private TemplateRenderer $renderer,
        private ComicRepositoryInterface $comicRepo,
        private BookmarkRepositoryInterface $bookmarkRepo,
        private AuthService $auth,
        private SessionManager $sessionManager,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $allComics = $this->comicRepo->findAll();

        $isLoggedIn     = $this->auth->isLoggedIn();
        $cloudBookmarks = [];

        if ($isLoggedIn) {
            $userId = $this->sessionManager->getUserId();
            // Nur echte User-Accounts fragen die Datenbank!
            if (! \str_starts_with($userId, 'sys_')) {
                // Hole nur die Lesezeichen dieses Nutzers
                $cloudBookmarks = $this->bookmarkRepo->findByUser($userId);
            }
        }

        return $this->renderer->render('pages/frontend/bookmarks', [
            'pageTitle'       => 'Meine Lesezeichen',
            'siteDescription' => 'Deine gespeicherten TwoKinds Lesezeichen auf einen Blick.',
            'comics'          => $allComics,
            'isLoggedIn'      => $isLoggedIn,
            'cloudBookmarks'  => $cloudBookmarks,
        ]);
    }
}
