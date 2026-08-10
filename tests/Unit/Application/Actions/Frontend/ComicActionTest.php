<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\ComicAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\ComicId;
use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'frontend', 'comic');

function setupComicActionTest(mixed $test, array $comics): object
{
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);
    $mock = Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);

    $comicRepo = $stub(ComicRepositoryInterface::class);
    $comicRepo->method('findAll')->willReturn($comics);

    $renderer = $mock(TemplateRenderer::class);

    return new class($comicRepo, $stub(CharacterRepositoryInterface::class), $stub(CharacterGroupRepositoryInterface::class), $stub(UserRepositoryInterface::class), $renderer) {
        public function __construct(
            public Stub $comicRepo,
            public Stub $charRepo,
            public Stub $groupRepo,
            public Stub $userRepo,
            public mixed $renderer,
        ) {
        }
    };
}

\it('renders 404 if no comics exist', function (): void {
    $app = setupComicActionTest($this, []);

    $app->renderer->expects($this->once())
        ->method('render')
        ->with('pages/frontend/404', ['pageTitle' => 'Keine Comics gefunden.'], 404)
        ->willReturn(new HtmlResponse('404', 404));

    $action = new ComicAction($app->comicRepo, $app->charRepo, $app->groupRepo, $app->userRepo, $app->renderer);
    $action->execute(new ServerRequest());
})->covers(ComicAction::class);

\it('renders the requested comic page and resolves prev/next links', function (): void {
    $c1 = new ComicPage(new ComicId('20260811'), 'Comicseite', 'Latest', '', null, [], '', '');
    $c2 = new ComicPage(new ComicId('20260810'), 'Comicseite', 'Middle', '', null, [], '', '');
    $c3 = new ComicPage(new ComicId('20260809'), 'Comicseite', 'First', '', null, [], '', '');

    $app = setupComicActionTest($this, [$c1, $c2, $c3]);

    $app->renderer->expects($this->once())
        ->method('render')
        ->willReturn(new HtmlResponse('HTML', 200));

    $action = new ComicAction($app->comicRepo, $app->charRepo, $app->groupRepo, $app->userRepo, $app->renderer);
    $action->execute(new ServerRequest(input: ['id' => '20260810']));
})->covers(ComicAction::class);

\it('defaults to the latest comic if no id is provided', function (): void {
    $c1 = new ComicPage(new ComicId('20260811'), 'Comicseite', 'Latest', '', null, [], '', '');
    $app = setupComicActionTest($this, [$c1]);

    $app->renderer->expects($this->once())
        ->method('render')
        ->willReturn(new HtmlResponse('HTML', 200));

    $action = new ComicAction($app->comicRepo, $app->charRepo, $app->groupRepo, $app->userRepo, $app->renderer);
    $action->execute(new ServerRequest());
})->covers(ComicAction::class);
