<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;

#[Route('GET', '/404')]
final readonly class Error404Action implements ViewActionInterface
{
    public function __construct(private TemplateRenderer $renderer)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        \http_response_code(404);
        // Auf neuen template-Pfad geändert:
        $this->renderer->render('frontend/404', [
            'pageTitle' => 'Fehler 404 - Seite nicht gefunden',
        ]);

        return null;
    }
}
