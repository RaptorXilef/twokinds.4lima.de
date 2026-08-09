<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;

#[Route('GET', '/403')]
final readonly class Error403Action implements ActionInterface
{
    public function __construct(private TemplateRenderer $renderer)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        unset($request);

        return $this->renderer->render('pages/frontend/403', ['pageTitle' => 'Fehler 403 - Zugriff verweigert'], 403);
    }
}
