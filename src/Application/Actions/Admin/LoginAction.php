<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;

#[Route('GET', '/admin/login')]
final readonly class LoginAction implements ActionInterface
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

        return $this->renderer->render('pages/admin/login', ['pageTitle' => 'Admin Login']);
    }
}
