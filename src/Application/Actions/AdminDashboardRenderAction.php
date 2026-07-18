<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
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
        private ConfigInterface $config,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $comics      = $this->comicRepo->findAll();
        $characters  = $this->charRepo->findAll();
        $groups      = $this->groupRepo->findAll();
        $openReports = $this->reportRepo->findByStatus('open');

        // 1. Zugeordnete Charaktere herausfinden (Für den Pool-Filter)
        $assignedIds = [];
        foreach ($groups as $group) {
            foreach ($group->characterIds as $cid) {
                $assignedIds[] = $cid->value;
            }
        }
        // Wir übergeben einfach die Liste der zugeordneten IDs ans Template!
        $assignedIds = \array_unique($assignedIds);

        // 2. Einzigartige Ränge für das Dropdown ermitteln
        $ranks = [];
        foreach ($characters as $char) {
            if ($char->rank !== null && $char->rank !== '') {
                $ranks[] = $char->rank;
            }
        }
        $existingRanks = \array_values(\array_unique($ranks));
        \sort($existingRanks);

        // 3. Verfügbare Profilbilder aus dem Ordner scannen
        $imageDir        = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/profiles';
        $availableImages = [];
        if (\is_dir($imageDir)) {
            $files = \scandir($imageDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && \preg_match('/\.(webp|png|jpg|jpeg|gif)$/i', $file)) {
                    $availableImages[] = $file;
                }
            }
        }

        $this->renderer->render('admin/dashboard', [
            'pageTitle'       => 'Admin Dashboard',
            'adminUser'       => $this->sessionManager->getAdminUser(),
            'comics'          => $comics,
            'characters'      => $characters,
            'groups'          => $groups,
            'assignedIds'     => $assignedIds,
            'openReports'     => $openReports,
            'existingRanks'   => $existingRanks,
            'availableImages' => $availableImages,
        ]);

        return null;
    }
}
