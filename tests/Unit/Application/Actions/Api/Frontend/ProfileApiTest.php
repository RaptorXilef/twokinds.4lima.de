<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Frontend;

use App\Application\Actions\Api\Frontend\UpdateProfileAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\User;
use App\Core\Service\AuthService;
use App\Core\Service\MagicLinkService;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'api', 'frontend', 'profile');

function setupProfileApiTest(mixed $test, bool $isLoggedIn = true, string $userId = 'usr_123'): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }
    $_SESSION = [];
    if ($isLoggedIn) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['auth_hash'] = 'hash123';
        $_SESSION['admin_group'] = 'user';
    }

    $session = new SessionManager(new SystemClock());
    $userRepo = $stub(UserRepositoryInterface::class);

    if ($isLoggedIn && !\str_starts_with($userId, 'sys_')) {
        $user = new User($userId, new Username('Tester'), new EmailAddress('t@t.de'), 'hash123', 'user', new DateTimeImmutable());
        $userRepo->method('findById')->willReturn($user);
    }

    $auth = new AuthService(
        $stub(ConfigInterface::class),
        $stub(RoleRepositoryInterface::class),
        $stub(RateLimiterInterface::class),
        $session,
        $userRepo,
    );

    $magicLink = new MagicLinkService(
        $stub(ClockInterface::class),
        $stub(ConfigInterface::class),
        $stub(MagicLinkRepositoryInterface::class),
    );

    return new class($auth, $session, $userRepo, $stub(ConfigInterface::class), $stub(MailServiceInterface::class), $magicLink) {
        public function __construct(
            public AuthService $auth,
            public SessionManager $session,
            public mixed $userRepo,
            public Stub $config,
            public Stub $mailService,
            public MagicLinkService $magicLink,
        ) {
        }
    };
}

\it('UpdateProfileAction returns 401 if not logged in', function (): void {
    $app = setupProfileApiTest($this, false);
    $action = new UpdateProfileAction($app->auth, $app->session, $app->userRepo, $app->config, $app->mailService, $app->magicLink);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(401);
})->covers(UpdateProfileAction::class);

\it('UpdateProfileAction returns 403 for sys accounts', function (): void {
    $app = setupProfileApiTest($this, true, 'sys_admin');
    $action = new UpdateProfileAction($app->auth, $app->session, $app->userRepo, $app->config, $app->mailService, $app->magicLink);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(403);
})->covers(UpdateProfileAction::class);

\it('UpdateProfileAction returns 400 for invalid action_type', function (): void {
    $app = setupProfileApiTest($this, true);
    $action = new UpdateProfileAction($app->auth, $app->session, $app->userRepo, $app->config, $app->mailService, $app->magicLink);

    $res = $action->execute(new ServerRequest(post: ['update_type' => 'invalid_hack']));
    \expect($res->statusCode)->toBe(400)->and($res->data['error'])->toBe('Ungültige Aktion.');
})->covers(UpdateProfileAction::class);

\it('UpdateProfileAction handles newsletter settings saving', function (): void {
    $app = setupProfileApiTest($this, true);

    $action = new UpdateProfileAction($app->auth, $app->session, $app->userRepo, $app->config, $app->mailService, $app->magicLink);

    $res = $action->execute(new ServerRequest(post: ['update_type' => 'newsletter', 'wants_newsletter' => '1']));
    \expect($res->statusCode)->toBe(200);
})->covers(UpdateProfileAction::class);
