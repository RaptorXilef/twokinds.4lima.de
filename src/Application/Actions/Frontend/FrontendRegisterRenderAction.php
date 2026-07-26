<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\View\TemplateRenderer;
use App\Core\Service\AuthService;

#[ActionRoute('render_frontend_register')]
final readonly class FrontendRegisterRenderAction implements ViewActionInterface
{
    public function __construct(private TemplateRenderer $renderer, private AuthService $auth)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        if ($this->auth->isLoggedIn()) {
            return new RedirectResponse('/lesezeichen');
        }
        $this->renderer->render('frontend/register', ['pageTitle' => 'Konto erstellen']);

        return null;
    }
}
