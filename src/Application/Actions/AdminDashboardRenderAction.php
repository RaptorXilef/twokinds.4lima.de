<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Security\PermissionRegistry;
use App\Core\Service\AuthService;

#[ActionRoute('render_admin_dashboard')]
final readonly class AdminDashboardRenderAction implements ViewActionInterface
{
    public function __construct(
        private TemplateRenderer $renderer,
        private SessionManager $sessionManager,
        private ComicRepositoryInterface $comicRepo,
        private ChapterRepositoryInterface $chapterRepo,
        private CharacterRepositoryInterface $charRepo,
        private CharacterGroupRepositoryInterface $groupRepo,
        private ReportRepositoryInterface $reportRepo,
        private ConfigInterface $config,
        private RoleRepositoryInterface $roleRepo,
        private UserRepositoryInterface $userRepo,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $comics      = $this->comicRepo->findAll();
        $characters  = $this->charRepo->findAll();
        $groups      = $this->groupRepo->findAll();
        $allReports  = $this->reportRepo->findAll(); // <--- (Alle Reports laden)
        $openReports = $this->reportRepo->findByStatus('open'); // für die rote Badge im Menü

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

        // Wirkliche Kapitel aus der Datenbank laden
        $dbChapters = $this->chapterRepo->findAll();

        // (Wir behalten das $existingChapters Array für das Datalist-Dropdown bei Comics)
        $existingChapters = \array_map(fn ($c) => $c->id, $dbChapters);

        $roles           = $this->roleRepo->loadAll();
        $users           = $this->userRepo->findAll();
        $permissionsTree = PermissionRegistry::getStructure();
        $canManageUsers  = $this->auth->hasPermission('system.users.manage');
        $canManageRoles  = $this->auth->hasPermission('system.roles.manage');

        // --- NEUE PERMISSIONS FÜR DIE UI ---
        $perms = [
            'chap_del'     => $this->auth->hasPermission('chapters.delete'),
            'chap_edit'    => $this->auth->hasPermission('chapters.edit'),
            'char_del'     => $this->auth->hasPermission('characters.delete'),
            'char_edit'    => $this->auth->hasPermission('characters.edit'),
            'comic_del'    => $this->auth->hasPermission('comics.delete'),
            'comic_edit'   => $this->auth->hasPermission('comics.edit'),
            'group_manage' => $this->auth->hasPermission('groups.manage'),
            'media_del'    => $this->auth->hasPermission('media.delete'),
            'media_up'     => $this->auth->hasPermission('media.upload'),
            'rep_del'      => $this->auth->hasPermission('reports.delete'),
            'rep_resolve'  => $this->auth->hasPermission('reports.resolve'),
            'rep_view'     => $this->auth->hasPermission('reports.view'),
        ];

        $this->renderer->render('admin/dashboard', [
            'pageTitle'        => 'Admin Dashboard',
            'adminUser'        => $this->sessionManager->getAdminUser(),
            'currentUserId'    => $this->sessionManager->getUserId(),
            'comics'           => $comics,
            'dbChapters'       => $dbChapters,
            'characters'       => $characters,
            'groups'           => $groups,
            'assignedIds'      => $assignedIds,
            'openReports'      => $openReports,
            'existingRanks'    => $existingRanks,
            'existingChapters' => $existingChapters,
            'availableImages'  => $availableImages,
            'allReports'       => $allReports,
            'hiresMinWidth'    => $this->config->get('hires_min_width', 1000),
            'hiresMinHeight'   => $this->config->get('hires_min_height', 1800),
            'roles'            => $roles,
            'users'            => $users,
            'permissionsTree'  => $permissionsTree,
            'canManageUsers'   => $canManageUsers,
            'canManageRoles'   => $canManageRoles,
            'perms'            => $perms,
        ]);

        return null;
    }
}
