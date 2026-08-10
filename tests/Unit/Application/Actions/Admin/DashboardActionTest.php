<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Admin;

use App\Application\Actions\Admin\DashboardAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\Response\JsonResponse;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailLogInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Core\Service\AuthService;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'admin', 'dashboard');

function setupDashboardTest(mixed $test, bool $hasPerm = true): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
    if ($hasPerm) {
        $_SESSION['user_id'] = 'sys_admin';
    } else {
        $_SESSION['user_id'] = 'usr_1';
        $_SESSION['compiled_permissions'] = [];
    }

    $session = new SessionManager(new SystemClock());
    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('http://localhost');
    $config->method('get')->willReturnMap([['root_path', null, \realpath(__DIR__ . '/../../../../../../')]]);

    $auth = new AuthService(
        $config,
        $stub(RoleRepositoryInterface::class),
        $stub(RateLimiterInterface::class),
        $session,
        $stub(UserRepositoryInterface::class),
    );

    $renderer = new TemplateRenderer(
        $config,
        $stub(ImageStorageInterface::class),
        $stub(JsonHelperInterface::class),
        $session,
        $stub(SystemInfoInterface::class),
        $stub(AssetHelperInterface::class),
    );

    return new class(
        $renderer,
        $session,
        $stub(ComicRepositoryInterface::class),
        $stub(ChapterRepositoryInterface::class),
        $stub(CharacterRepositoryInterface::class),
        $stub(CharacterGroupRepositoryInterface::class),
        $stub(ReportRepositoryInterface::class),
        $config,
        $stub(RoleRepositoryInterface::class),
        $stub(UserRepositoryInterface::class),
        $auth,
        $stub(MailQueueRepositoryInterface::class),
        $stub(MailLogInterface::class),
    ) {
        public function __construct(
            public TemplateRenderer $renderer,
            public SessionManager $session,
            public Stub $comicRepo,
            public Stub $chapterRepo,
            public Stub $charRepo,
            public Stub $groupRepo,
            public Stub $reportRepo,
            public Stub $config,
            public Stub $roleRepo,
            public Stub $userRepo,
            public AuthService $auth,
            public Stub $mailQueueRepo,
            public Stub $mailLogRepo,
        ) {
        }
    };
}

\it('redirects to 403 if user has no dashboard permission', function (): void {
    $app = setupDashboardTest($this, false);
    $action = new DashboardAction(
        $app->renderer,
        $app->session,
        $app->comicRepo,
        $app->chapterRepo,
        $app->charRepo,
        $app->groupRepo,
        $app->reportRepo,
        $app->config,
        $app->roleRepo,
        $app->userRepo,
        $app->auth,
        $app->mailQueueRepo,
        $app->mailLogRepo,
    );

    $res = $action->execute(new ServerRequest());
    \expect($res)->toBeInstanceOf(RedirectResponse::class)
        ->and($res->statusCode)->toBe(302);
})->covers(DashboardAction::class);

\it('renders full dashboard HTML if no ajax tab requested', function (): void {
    $app = setupDashboardTest($this, true);
    $app->charRepo->method('findAll')->willReturn([]);
    $app->groupRepo->method('findAll')->willReturn([]);

    $action = new DashboardAction(
        $app->renderer,
        $app->session,
        $app->comicRepo,
        $app->chapterRepo,
        $app->charRepo,
        $app->groupRepo,
        $app->reportRepo,
        $app->config,
        $app->roleRepo,
        $app->userRepo,
        $app->auth,
        $app->mailQueueRepo,
        $app->mailLogRepo,
    );

    $res = $action->execute(new ServerRequest());
    \expect($res)->toBeInstanceOf(HtmlResponse::class)
        ->and($res->statusCode)->toBe(200);
})->covers(DashboardAction::class);

\it('returns JSON partials for AJAX tab requests', function (string $tab): void {
    $app = setupDashboardTest($this, true);
    $action = new DashboardAction(
        $app->renderer,
        $app->session,
        $app->comicRepo,
        $app->chapterRepo,
        $app->charRepo,
        $app->groupRepo,
        $app->reportRepo,
        $app->config,
        $app->roleRepo,
        $app->userRepo,
        $app->auth,
        $app->mailQueueRepo,
        $app->mailLogRepo,
    );

    $res = $action->execute(new ServerRequest(get: ['ajax_tab' => $tab]));
    \expect($res)->toBeInstanceOf(JsonResponse::class)
        ->and($res->statusCode)->toBe(200)
        ->and($res->data)->toHaveKey('html');
})->with([
    'comics', 'reports', 'users', 'upload', 'archive', 'characters', 'groups', 'media', 'backup', 'mails',
])->covers(DashboardAction::class);
