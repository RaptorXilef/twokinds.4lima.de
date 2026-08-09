<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;

#[Route('GET', '/404')]
final readonly class Error404Action implements ActionInterface
{
    public function __construct(private TemplateRenderer $renderer)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        unset($request);

        return $this->renderer->render('pages/frontend/404', ['pageTitle' => 'Fehler 404 - Seite nicht gefunden'], 404);
    }
}
