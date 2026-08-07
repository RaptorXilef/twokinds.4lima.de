<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Core\Service\MagicLinkService;

#[Route('GET', '/passwort-reset')]
final readonly class ResetPasswordAction implements ViewActionInterface
{
    public function __construct(private TemplateRenderer $renderer, private MagicLinkService $magicLinkService)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        $tokenRaw = $request->get['token'] ?? '';
        $token    = \is_scalar($tokenRaw) ? (string) $tokenRaw : '';
        $email    = $this->magicLinkService->peekToken($token);

        return $this->renderer->render('pages/frontend/reset_password', [
            'pageTitle'    => 'Neues Passwort festlegen',
            'isValidToken' => $email !== null,
            'token'        => $token,
        ]);
    }
}
