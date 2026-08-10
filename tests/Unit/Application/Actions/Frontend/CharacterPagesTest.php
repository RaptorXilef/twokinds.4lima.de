<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\CharacterDetailAction;
use App\Application\Actions\Frontend\CharacterListAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Core\Entity\Character;
use App\Core\ValueObject\CharacterId;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'frontend', 'characters');

function setupCharPagesTest(mixed $test): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    $charRepo = $stub(CharacterRepositoryInterface::class);
    $comicRepo = $stub(ComicRepositoryInterface::class);
    $groupRepo = $stub(CharacterGroupRepositoryInterface::class);

    $config = $stub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('http://localhost');
    $config->method('get')->willReturnCallback(function (string $key, mixed $default = null) {
        return match ($key) {
            'root_path' => \dirname(__DIR__, 5),
            'site_title' => 'Test',
            'site_description' => 'Test',
            default => $default,
        };
    });

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

    return new class($charRepo, $comicRepo, $groupRepo, $renderer) {
        public function __construct(
            public Stub&CharacterRepositoryInterface $charRepo,
            public Stub&ComicRepositoryInterface $comicRepo,
            public Stub&CharacterGroupRepositoryInterface $groupRepo,
            public TemplateRenderer $renderer,
        ) {
        }
    };
}

\it('CharacterListAction renders character list with filters', function (): void {
    $app = setupCharPagesTest($this);

    $app->charRepo->method('findAll')->willReturn([
        new Character(new CharacterId('char_0001'), 'Trace', null, null, null, null, 'Male', '24', 'Templar', 'Human', null, 'English'),
    ]);
    $app->groupRepo->method('findAll')->willReturn([]);

    $action = new CharacterListAction($app->charRepo, $app->groupRepo, $app->renderer);
    $res = $action->execute(new ServerRequest());

    \expect($res)->toBeInstanceOf(HtmlResponse::class)
        ->and($res->statusCode)->toBe(200)
        ->and($res->html)->toContain('Charaktere filtern');
})->covers(CharacterListAction::class);

\it('CharacterDetailAction redirects if no id provided', function (): void {
    $app = setupCharPagesTest($this);
    $action = new CharacterDetailAction($app->charRepo, $app->comicRepo, $app->renderer);
    $res = $action->execute(new ServerRequest());

    \expect($res)->toBeInstanceOf(RedirectResponse::class)
        ->and($res->url)->toBe('/charaktere');
})->covers(CharacterDetailAction::class);

\it('CharacterDetailAction returns 404 if character not found', function (): void {
    $app = setupCharPagesTest($this);
    $app->charRepo->method('findById')->willReturn(null);

    $action = new CharacterDetailAction($app->charRepo, $app->comicRepo, $app->renderer);
    $res = $action->execute(new ServerRequest(input: ['id' => 'char_999']));

    \expect($res)->toBeInstanceOf(HtmlResponse::class)
        ->and($res->statusCode)->toBe(404);
})->covers(CharacterDetailAction::class);

\it('CharacterDetailAction redirects if searching by legacy name', function (): void {
    $app = setupCharPagesTest($this);

    $app->charRepo->method('findAll')->willReturn([
        new Character(new CharacterId('char_0001'), 'Trace', null, null),
    ]);

    $action = new CharacterDetailAction($app->charRepo, $app->comicRepo, $app->renderer);
    $res = $action->execute(new ServerRequest(input: ['id' => 'Trace']));

    \expect($res)->toBeInstanceOf(RedirectResponse::class)
        ->and($res->url)->toBe('/charaktere/char_0001');
})->covers(CharacterDetailAction::class);
