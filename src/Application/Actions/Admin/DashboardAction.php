<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailLogInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Security\PermissionRegistry;
use App\Core\Service\AuthService;

#[Route('GET', '/admin')]
#[RequiresAuth]
final readonly class DashboardAction implements ViewActionInterface
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
        private MailQueueRepositoryInterface $mailQueueRepo,
        private MailLogInterface $mailLogRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        // ABSOLUTER SICHERHEITS-CHECK: Hat der Nutzer überhaupt etwas im Dashboard verloren?
        if (! $this->auth->hasPermission('dashboard.view') && ! $this->auth->hasPermission('admin.access') && $this->sessionManager->getAdminGroup() !== 'admin') {
            return new RedirectResponse($this->config->getBaseUrl() . '/403');
        }

        $ajaxTab = $request->get['ajax_tab'] ?? null;

        // Basis-Daten, die sehr schnell laden und global (z.B. für Modale) gebraucht werden
        $characters = $this->charRepo->findAll();
        $groups     = $this->groupRepo->findAll();

        // Wirkliche Kapitel aus der Datenbank laden
        $dbChapters = $this->chapterRepo->findAll();

        // (Wir behalten das $existingChapters Array für das Datalist-Dropdown bei Comics)
        $existingChapters = \array_map(fn ($c) => $c->id, $dbChapters);
        $roles            = $this->roleRepo->loadAll();

        $canManageUsers = $this->auth->hasPermission('system.users.manage');
        $canManageRoles = $this->auth->hasPermission('system.roles.manage');

        $perms = [
            'backup_manage' => $this->auth->hasPermission('system.backup.manage'),
            'chap_del'      => $this->auth->hasPermission('chapters.delete'),
            'chap_edit'     => $this->auth->hasPermission('chapters.edit'),
            'char_del'      => $this->auth->hasPermission('characters.delete'),
            'char_edit'     => $this->auth->hasPermission('characters.edit'),
            'comic_del'     => $this->auth->hasPermission('comics.delete'),
            'comic_edit'    => $this->auth->hasPermission('comics.edit'),
            'group_manage'  => $this->auth->hasPermission('groups.manage'),
            'media_del'     => $this->auth->hasPermission('media.delete'),
            'media_up'      => $this->auth->hasPermission('media.upload'),
            'rep_del'       => $this->auth->hasPermission('reports.delete'),
            'rep_resolve'   => $this->auth->hasPermission('reports.resolve'),
            'rep_view'      => $this->auth->hasPermission('reports.view'),
            'system_manage' => $this->auth->hasPermission('system.manage'),
        ];

        // --- AJAX HYDRATION MODUS ---
        // Wenn JS einen spezifischen Tab anfordert, rendern wir NUR diesen Tab!
        if ($ajaxTab) {
            $data = [
                'appRoot'          => \rtrim((string) $this->config->get('root_path'), '/\\'),
                'baseUrl'          => \rtrim($this->config->getBaseUrl(), '/'),
                'canManageRoles'   => $canManageRoles,
                'canManageUsers'   => $canManageUsers,
                'characters'       => $characters,
                'currentUserId'    => $this->sessionManager->getUserId(),
                'dbChapters'       => $dbChapters,
                'existingChapters' => $existingChapters,
                'groups'           => $groups,
                'hiresMinHeight'   => $this->config->get('hires_min_height', 1800),
                'hiresMinWidth'    => $this->config->get('hires_min_width', 1000),
                'permissionsTree'  => PermissionRegistry::getStructure(),
                'perms'            => $perms,
                'roles'            => $roles,
            ];

            \ob_start();

            if ($ajaxTab === 'comics') {
                $data['comics'] = $this->comicRepo->findAll();
                $this->renderer->render('partials/admin/_section_comics', $data);
            } elseif ($ajaxTab === 'reports') {
                $data['allReports'] = $this->reportRepo->findAll();
                $this->renderer->render('partials/admin/_section_reports', $data);
            } elseif ($ajaxTab === 'users') {
                $data['users'] = $this->userRepo->findAll();
                $this->renderer->render('partials/admin/_section_users', $data);
            } elseif ($ajaxTab === 'upload') {
                $this->renderer->render('partials/admin/_section_upload', $data);
            } elseif ($ajaxTab === 'archive') {
                $this->renderer->render('partials/admin/_section_archive', $data);
            } elseif ($ajaxTab === 'characters') {
                $this->renderer->render('partials/admin/_section_characters', $data);
            } elseif ($ajaxTab === 'groups') {
                $assignedIds = [];
                foreach ($groups as $group) {
                    foreach ($group->characterIds as $cid) {
                        $assignedIds[] = $cid->value;
                    }
                }
                $data['assignedIds'] = \array_unique($assignedIds);
                $this->renderer->render('partials/admin/_section_groups', $data);
            } elseif ($ajaxTab === 'media') {
                $this->renderer->render('partials/admin/_section_media', $data);
            } elseif ($ajaxTab === 'backup') {
                $this->renderer->render('partials/admin/_section_backup', $data);
            } elseif ($ajaxTab === 'mails') { // TAB-RENDERER FÜR E-MAILS
                $data['mailQueue'] = $this->mailQueueRepo->findAllQueue();
                $data['mailLogs']  = $this->mailLogRepo->loadLogs();
                $this->renderer->render('partials/admin/_section_mails', $data);
            }

            return JsonResponse::success(['html' => \ob_get_clean()]);
        }

        // --- NORMALER PAGE LOAD MODUS (EXTREM SCHNELL) ---
        $assignedIds = [];
        foreach ($groups as $group) {
            foreach ($group->characterIds as $cid) {
                $assignedIds[] = $cid->value;
            }
        }
        $assignedIds = \array_unique($assignedIds);

        // Bilder-Scan für Charakter-Modal (Geht superschnell)
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

        return $this->renderer->render('pages/admin/dashboard', [
            'pageTitle'        => 'Admin Dashboard',
            'adminUser'        => $this->sessionManager->getAdminUser(),
            'currentUserId'    => $this->sessionManager->getUserId(),
            'dbChapters'       => $dbChapters,
            'characters'       => $characters,
            'groups'           => $groups,
            'assignedIds'      => $assignedIds,
            'existingRanks'    => [],
            'existingChapters' => $existingChapters,
            'availableImages'  => $availableImages,
            'roles'            => $roles,
            'permissionsTree'  => PermissionRegistry::getStructure(),
            'canManageUsers'   => $canManageUsers,
            'canManageRoles'   => $canManageRoles,
            'perms'            => $perms,
            'hiresMinWidth'    => $this->config->get('hires_min_width', 1000),
            'hiresMinHeight'   => $this->config->get('hires_min_height', 1800),
            'allUsers'         => $this->userRepo->findAll(),

            // WICHTIG: Die schweren Datenarrays bleiben LEER! Das spart 11.5 Sekunden Ladezeit.
            'comics'      => [],
            'allReports'  => [],
            'users'       => [],
            'openReports' => [],
        ]);
    }
}
