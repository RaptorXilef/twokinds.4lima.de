<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\AuthService;

#[ActionRoute('render_frontend_profile')]
final readonly class FrontendProfileRenderAction implements ViewActionInterface
{
    public function __construct(
        private TemplateRenderer $renderer,
        private AuthService $auth,
        private SessionManager $sessionManager,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->isLoggedIn()) {
            return new RedirectResponse('/login');
        }

        $userId = $this->sessionManager->getUserId();
        $user   = $this->userRepository->findById($userId);

        // Verhindert Zugriff auf Profil-Seite für System-Accounts (RaptorXilef/Systembetreuer)
        if (\str_starts_with($userId, 'sys_')) {
            $this->sessionManager->addFlash('info', 'System-Accounts können nicht über das Frontend bearbeitet werden.');

            return new RedirectResponse('/lesezeichen');
        }

        $this->renderer->render('frontend/profile', [
            'pageTitle' => 'Mein Profil',
            'user'      => $user,
        ]);

        return null;
    }
}
