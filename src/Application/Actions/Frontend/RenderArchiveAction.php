<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\ComicRepositoryInterface;

#[ActionRoute('render_archive')]
final readonly class RenderArchiveAction implements ViewActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepository,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        // Hier laden wir später auch die Chapters aus einem ChapterRepository.
        // Für den Moment übergeben wir alle Comics an das Template, das diese dann gruppiert.
        $comics = $this->comicRepository->findAll();
        // Auf neuen template-Pfad geändert:
        $this->renderer->render('frontend/archive', [
            'comics'          => $comics,
            'pageTitle'       => 'Archiv',
            'siteDescription' => 'Das vollständige Archiv der deutschen Übersetzung.',
        ]);

        return null;
    }
}
