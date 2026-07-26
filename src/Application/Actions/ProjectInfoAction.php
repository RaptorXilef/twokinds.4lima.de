<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;

#[ActionRoute('page_project_info')]
final readonly class ProjectInfoAction implements ViewActionInterface
{
    public function __construct(
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $this->renderer->render('frontend/project_info', [
            'pageTitle' => 'Über das Projekt & FAQ',
        ]);

        return null;
    }
}
