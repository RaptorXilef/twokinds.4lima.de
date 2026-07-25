<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Service\MagicLinkService;

#[ActionRoute('render_frontend_verify')]
final readonly class FrontendVerifyRenderAction implements ViewActionInterface
{
    public function __construct(
        private MagicLinkService $magicLinkService,
        private UserRepositoryInterface $userRepository,
        private SessionManager $sessionManager,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $token = $request->get['token'] ?? '';
        $email = $this->magicLinkService->verifyAny($token);

        if (! $email) {
            $this->sessionManager->addFlash('error', 'Der Bestätigungslink ist ungültig oder abgelaufen.');

            return new RedirectResponse('/login');
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user && $user->roleId === 'pending') {
            $updatedUser = new User(
                $user->id,
                $user->username,
                $user->email,
                $user->passwordHash,
                'user', // Rolle wird von 'pending' auf 'user' gesetzt!
                $user->createdAt,
            );
            $this->userRepository->save($updatedUser);

            $this->sessionManager->addFlash('success', 'Dein Konto wurde erfolgreich bestätigt! Du kannst dich jetzt einloggen.');
        } else {
            $this->sessionManager->addFlash('info', 'Dieses Konto wurde bereits bestätigt.');
        }

        return new RedirectResponse('/login');
    }
}
