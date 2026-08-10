<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\ComicAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\ComicId;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'frontend', 'comic');

function setupComicActionTest(mixed $test, array $comics): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);
    $mock = Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }

    $comicRepo = $mock(ComicRepositoryInterface::class);
    $comicRepo->method('findAll')->willReturn($comics);

    $charRepo = $stub(CharacterRepositoryInterface::class);
    $charRepo->method('findAll')->willReturn([]);

    $groupRepo = $stub(CharacterGroupRepositoryInterface::class);
    $groupRepo->method('findAll')->willReturn([]);

    $userRepo = $stub(UserRepositoryInterface::class);

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('http://localhost');
    $config->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
        return match ($key) {
            'root_path' => \dirname(__DIR__, 5),
            default => $default,
        };
    });

    $renderer = new TemplateRenderer(
        $config,
        $stub(ImageStorageInterface::class),
        $stub(JsonHelperInterface::class),
        new SessionManager(new SystemClock()),
        $stub(SystemInfoInterface::class),
        $stub(AssetHelperInterface::class),
    );

    return new class($comicRepo, $charRepo, $groupRepo, $userRepo, $renderer) {
        public function __construct(
            public MockObject $comicRepo,
            public Stub $charRepo,
            public Stub $groupRepo,
            public Stub $userRepo,
            public TemplateRenderer $renderer,
        ) {
        }
    };
}

\it('renders 404 if no comics exist', function (): void {
    $app = setupComicActionTest($this, []);

    $action = new ComicAction($app->comicRepo, $app->charRepo, $app->groupRepo, $app->userRepo, $app->renderer);
    $response = $action->execute(new ServerRequest());

    \expect($response->statusCode)->toBe(404)
        ->and($response->html)->toContain('Fehler 404');
})->covers(ComicAction::class);

\it('renders the requested comic page and resolves prev/next links', function (): void {
    $c1 = new ComicPage(new ComicId('20260811'), 'Comicseite', 'Latest', '', null, [], '', '');
    $c2 = new ComicPage(new ComicId('20260810'), 'Comicseite', 'Middle', '', null, [], '', '');
    $c3 = new ComicPage(new ComicId('20260809'), 'Comicseite', 'First', '', null, [], '', '');

    $app = setupComicActionTest($this, [$c1, $c2, $c3]);

    $action = new ComicAction($app->comicRepo, $app->charRepo, $app->groupRepo, $app->userRepo, $app->renderer);
    $response = $action->execute(new ServerRequest(input: ['id' => '20260810']));

    \expect($response->statusCode)->toBe(200)
        ->and($response->html)->toContain('Middle');
})->covers(ComicAction::class);

\it('defaults to the latest comic if no id is provided', function (): void {
    $c1 = new ComicPage(new ComicId('20260811'), 'Comicseite', 'Latest', '', null, [], '', '');
    $app = setupComicActionTest($this, [$c1]);

    $action = new ComicAction($app->comicRepo, $app->charRepo, $app->groupRepo, $app->userRepo, $app->renderer);
    $response = $action->execute(new ServerRequest());

    \expect($response->statusCode)->toBe(200)
        ->and($response->html)->toContain('Latest');
})->covers(ComicAction::class);
