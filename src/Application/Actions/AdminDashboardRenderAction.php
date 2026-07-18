<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
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
        private CharacterGroupRepositoryInterface $groupRepo,
        private ReportRepositoryInterface $reportRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $comics      = $this->comicRepo->findAll();
        $characters  = $this->charRepo->findAll();
        $groups      = $this->groupRepo->findAll();
        $openReports = $this->reportRepo->findByStatus('open');

        // Unzugeordnete Charaktere herausfinden
        $assignedIds = [];
        foreach ($groups as $group) {
            foreach ($group->characterIds as $cid) {
                $assignedIds[] = $cid->value;
            }
        }
        $assignedIds = \array_unique($assignedIds);

        $unassignedCharacters = \array_filter(
            $characters,
            fn ($char) => ! \in_array($char->id->value, $assignedIds, true),
        );

        $this->renderer->render('admin/dashboard', [
            'pageTitle'            => 'Admin Dashboard',
            'adminUser'            => $this->sessionManager->getAdminUser(),
            'comics'               => $comics,
            'characters'           => $characters,
            'groups'               => $groups,
            'unassignedCharacters' => $unassignedCharacters,
            'openReports'          => $openReports,
        ]);

        return null;
    }
}
