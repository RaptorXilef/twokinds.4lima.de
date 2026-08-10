<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\UserDetailAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Core\Entity\User;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'frontend', 'user');

function setupUserDetailTest(mixed $test): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    $userRepo = $stub(UserRepositoryInterface::class);
    $comicRepo = $stub(ComicRepositoryInterface::class);
    $bmRepo = $stub(BookmarkRepositoryInterface::class);

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('http://localhost');
    $config->method('get')->willReturnMap([['root_path', null, \realpath(__DIR__ . '/../../../../../')]]);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }

    $renderer = new TemplateRenderer(
        $config,
        $stub(ImageStorageInterface::class),
        $stub(JsonHelperInterface::class),
        new SessionManager(new SystemClock()),
        $stub(SystemInfoInterface::class),
        $stub(AssetHelperInterface::class),
    );

    return new class($userRepo, $comicRepo, $bmRepo, $renderer) {
        public function __construct(
            public Stub&UserRepositoryInterface $userRepo,
            public Stub&ComicRepositoryInterface $comicRepo,
            public Stub&BookmarkRepositoryInterface $bmRepo,
            public TemplateRenderer $renderer,
        ) {
        }
    };
}

\it('redirects if no id is provided', function (): void {
    $app = setupUserDetailTest($this);
    $action = new UserDetailAction($app->userRepo, $app->comicRepo, $app->bmRepo, $app->renderer);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(302);
})->covers(UserDetailAction::class);

\it('returns 404 if user not found', function (): void {
    $app = setupUserDetailTest($this);
    $app->userRepo->method('findById')->willReturn(null);
    $action = new UserDetailAction($app->userRepo, $app->comicRepo, $app->bmRepo, $app->renderer);

    $res = $action->execute(new ServerRequest(input: ['id' => 'unknown']));
    \expect($res->statusCode)->toBe(404);
})->covers(UserDetailAction::class);

\it('renders user details successfully', function (): void {
    $app = setupUserDetailTest($this);

    $user = new User('usr_1', new Username('Tester'), new EmailAddress('t@t.de'), 'hash', 'user', new DateTimeImmutable());
    $app->userRepo->method('findById')->willReturn($user);
    $app->comicRepo->method('findAll')->willReturn([]);

    $action = new UserDetailAction($app->userRepo, $app->comicRepo, $app->bmRepo, $app->renderer);
    $res = $action->execute(new ServerRequest(input: ['id' => 'usr_1']));

    \expect($res)->toBeInstanceOf(HtmlResponse::class)
        ->and($res->statusCode)->toBe(200)
        ->and($res->html)->toContain('Tester');
})->covers(UserDetailAction::class);
