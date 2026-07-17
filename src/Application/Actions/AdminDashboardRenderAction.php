<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\ReportRepositoryInterface;

#[ActionRoute('render_admin_dashboard')]
final readonly class AdminDashboardRenderAction implements ViewActionInterface
{
    public function __construct(
        private TemplateRenderer $renderer,
        private SessionManager $sessionManager,
        private ComicRepositoryInterface $comicRepo,
        private CharacterRepositoryInterface $charRepo,
        private ReportRepositoryInterface $reportRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $comics      = $this->comicRepo->findAll();
        $characters  = $this->charRepo->findAll();
        $openReports = $this->reportRepo->findByStatus('open');

        $this->renderer->render('admin/dashboard', [
            'pageTitle'   => 'Admin Dashboard',
            'adminUser'   => $this->sessionManager->getAdminUser(),
            'comics'      => $comics,
            'characters'  => $characters,
            'openReports' => $openReports,
        ]);

        return null;
    }
}
