<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;

#[Route('GET', '/bestaetigungsmail-anfordern')]
final readonly class ResendVerificationAction implements ActionInterface
{
    public function __construct(private TemplateRenderer $renderer)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        return $this->renderer->render('pages/frontend/resend_verification', ['pageTitle' => 'Bestätigungsmail erneut anfordern']);
    }
}
