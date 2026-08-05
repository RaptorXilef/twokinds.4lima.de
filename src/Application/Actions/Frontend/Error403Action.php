<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;

#[Route('GET', '/403')]
final readonly class Error403Action implements ViewActionInterface
{
    public function __construct(private TemplateRenderer $renderer)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        \http_response_code(403);
        $this->renderer->render('frontend/403', ['pageTitle' => 'Fehler 403 - Zugriff verweigert']);

        return null;
    }
}
