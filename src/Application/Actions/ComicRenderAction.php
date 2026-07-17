<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\DTO\ComicViewRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\ValueObject\ComicId;

#[ActionRoute('render_comic')]
final readonly class ComicRenderAction implements ViewActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepository,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $dto   = ComicViewRequest::fromRequest($request);
            $comic = $this->comicRepository->findById(new ComicId($dto->comicId));

            if ($comic === null) {
                // Fallback auf 404 Seite, wenn die ID nicht in der DB existiert
                return new RedirectResponse('404.php');
            }

            // Hier übergeben wir die Entity an das PHTML-Template
            $this->renderer->render('comic_page', [
                'comic'     => $comic,
                'pageTitle' => $comic->name !== '' ? $comic->name : "Seite {$comic->id->value}",
            ]);

            return null;

        } catch (ValidationException|\InvalidArgumentException $e) {
            return new RedirectResponse('404.php');
        }
    }
}
