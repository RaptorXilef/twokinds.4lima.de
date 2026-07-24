<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;

#[ActionRoute('page_project_info')]
final readonly class ProjectInfoAction implements ActionInterface
{
    public function execute(ServerRequest $request): mixed
    {
        return new HtmlResponse('pages/frontend/project_info.phtml', [
            'pageTitle' => 'Über das Projekt & FAQ',
        ]);
    }
}
