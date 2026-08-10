<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\Error403Action;
use App\Application\Actions\Frontend\Error404Action;
use App\Application\Actions\Frontend\ImprintAction;
use App\Application\Actions\Frontend\PrivacyAction;
use App\Application\Actions\Frontend\ProjectInfoAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\View\TemplateRenderer;

\uses()->group('application', 'actions', 'frontend');

\it('renders simple static pages correctly', function (string $class, string $template, string $title, int $code): void {
    $renderer = $this->createMock(TemplateRenderer::class);
    $renderer->expects($this->once())
        ->method('render')
        ->with($template, ['pageTitle' => $title], $code)
        ->willReturn(new HtmlResponse('HTML Content', $code));

    $action = new $class($renderer);
    $response = $action->execute(new ServerRequest());

    \expect($response)->toBeInstanceOf(HtmlResponse::class)
        ->and($response->statusCode)->toBe($code)
        ->and($response->html)->toBe('HTML Content');
})->with([
    [ImprintAction::class, 'pages/frontend/imprint', 'Impressum & Lizenz', 200],
    [PrivacyAction::class, 'pages/frontend/privacy', 'Datenschutzerklärung', 200],
    [ProjectInfoAction::class, 'pages/frontend/project_info', 'Über das Projekt & FAQ', 200],
    [Error403Action::class, 'pages/frontend/403', 'Fehler 403 - Zugriff verweigert', 403],
    [Error404Action::class, 'pages/frontend/404', 'Fehler 404 - Seite nicht gefunden', 404],
])->covers(ImprintAction::class, PrivacyAction::class, ProjectInfoAction::class, Error403Action::class, Error404Action::class);
