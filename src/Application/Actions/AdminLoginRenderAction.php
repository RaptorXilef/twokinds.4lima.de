<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;

#[ActionRoute('render_admin_login')]
final readonly class AdminLoginRenderAction implements ViewActionInterface
{
    public function __construct(
        private TemplateRenderer $renderer,
        private SessionManager $sessionManager,
        private ConfigInterface $config,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        // Wenn der User schon eingeloggt ist, direkt ins Dashboard leiten
        if ($this->sessionManager->getUserId() !== '') {
            $baseUrl = \rtrim($this->config->getBaseUrl(), '/');

            return new RedirectResponse($baseUrl . '/admin');
        }

        $this->renderer->render('admin/login', [
            'pageTitle' => 'Admin Login',
        ]);

        return null;
    }
}
