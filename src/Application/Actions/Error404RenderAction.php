<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;

#[ActionRoute('render_404')]
final readonly class Error404RenderAction implements ViewActionInterface
{
    public function __construct(private TemplateRenderer $renderer)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        \http_response_code(404);

        $this->renderer->render('404', [
            'pageTitle' => 'Fehler 404 - Seite nicht gefunden',
        ]);

        return null;
    }
}
