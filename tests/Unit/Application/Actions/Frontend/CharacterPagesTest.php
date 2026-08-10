<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\CharacterDetailAction;
use App\Application\Actions\Frontend\CharacterListAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\Response\RedirectResponse;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\Character;
use App\Core\ValueObject\CharacterId;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'frontend', 'characters');

function setupCharPagesTest(mixed $test): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);
    $mock = Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);

    $charRepo = $stub(CharacterRepositoryInterface::class);
    $comicRepo = $stub(ComicRepositoryInterface::class);
    $groupRepo = $stub(CharacterGroupRepositoryInterface::class);
    $renderer = $mock(TemplateRenderer::class);

    return new class($charRepo, $comicRepo, $groupRepo, $renderer) {
        public function __construct(
            public Stub&CharacterRepositoryInterface $charRepo,
            public Stub&ComicRepositoryInterface $comicRepo,
            public Stub&CharacterGroupRepositoryInterface $groupRepo,
            public mixed $renderer,
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

    $app->renderer->expects($this->once())
        ->method('render')
        ->with('pages/frontend/character_list', $this->callback(function (array $data): bool {
            return $data['pageTitle'] === 'Charaktere'
                && \in_array('Human', $data['filterData']['species'], true);
        }))
        ->willReturn(new HtmlResponse('HTML'));

    $action = new CharacterListAction($app->charRepo, $app->groupRepo, $app->renderer);
    $res = $action->execute(new ServerRequest());
    \expect($res->statusCode)->toBe(200);
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

    $app->renderer->expects($this->once())
        ->method('render')
        ->with('pages/frontend/404', $this->anything(), 404)
        ->willReturn(new HtmlResponse('404', 404));

    $action = new CharacterDetailAction($app->charRepo, $app->comicRepo, $app->renderer);
    $res = $action->execute(new ServerRequest(input: ['id' => 'char_999']));
    \expect($res->statusCode)->toBe(404);
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
