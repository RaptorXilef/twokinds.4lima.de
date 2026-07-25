<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\ComicRepositoryInterface;

#[ActionRoute('render_bookmarks')]
final readonly class RenderBookmarksAction implements ViewActionInterface
{
    public function __construct(
        private TemplateRenderer $renderer,
        private ComicRepositoryInterface $comicRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $comics = $this->comicRepo->findAll();

        $this->renderer->render('frontend/bookmarks', [
            'pageTitle'       => 'Meine Lesezeichen',
            'siteDescription' => 'Deine lokal gespeicherten TwoKinds Lesezeichen auf einen Blick.',
            'comics'          => $comics,
        ]);

        return null;
    }
}
