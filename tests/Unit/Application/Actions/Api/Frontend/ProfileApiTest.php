<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Frontend;

use App\Application\Actions\Api\Frontend\UpdateProfileAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\UserRepositoryInterface;
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
    }

    $session = new SessionManager(new SystemClock());
    $auth = $test->createMock(AuthService::class);
    $auth->method('isLoggedIn')->willReturn($isLoggedIn);

    $userRepo = $test->createMock(UserRepositoryInterface::class);
    $user = new User($userId, new Username('Tester'), new EmailAddress('t@t.de'), 'hash', 'user', new DateTimeImmutable());
    $userRepo->method('findById')->willReturn($user);

    return new class($auth, $session, $userRepo, $stub(ConfigInterface::class), $stub(MailServiceInterface::class), $stub(MagicLinkService::class)) {
        public function __construct(
            public mixed $auth,
            public SessionManager $session,
            public mixed $userRepo,
            public Stub $config,
            public Stub $mailService,
            public Stub $magicLink,
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

    $app->userRepo->expects($this->once())
        ->method('save')
        ->with($this->callback(fn (User $u): bool => $u->wantsNewsletter === true && $u->wantsNewsletterTranscript === false));

    $action = new UpdateProfileAction($app->auth, $app->session, $app->userRepo, $app->config, $app->mailService, $app->magicLink);

    $res = $action->execute(new ServerRequest(post: ['update_type' => 'newsletter', 'wants_newsletter' => '1']));
    \expect($res->statusCode)->toBe(200);
})->covers(UpdateProfileAction::class);
