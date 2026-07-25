<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;

#[ActionRoute('render_bookmarks')]
final readonly class RenderBookmarksAction implements ViewActionInterface
{
    public function __construct(
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $this->renderer->render('frontend/bookmarks', [
            'pageTitle'       => 'Meine Lesezeichen',
            'siteDescription' => 'Deine lokal gespeicherten TwoKinds Lesezeichen auf einen Blick.',
        ]);

        return null;
    }
}
