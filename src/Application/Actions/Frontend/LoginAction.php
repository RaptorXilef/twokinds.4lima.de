<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\View\TemplateRenderer;
use App\Core\Service\AuthService;

#[Route('GET', '/login')]
final readonly class LoginAction implements ActionInterface
{
    public function __construct(private TemplateRenderer $renderer, private AuthService $auth)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        unset($request);
        if ($this->auth->isLoggedIn()) {
            return new RedirectResponse('/lesezeichen');
        }

        return $this->renderer->render('pages/frontend/login', ['pageTitle' => 'Einloggen']);
    }
}
