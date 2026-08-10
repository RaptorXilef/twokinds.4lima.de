<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\ArchiveAction;
use App\Application\Http\ServerRequest;
use App\Application\Response\HtmlResponse;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\Chapter;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\ComicId;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'frontend', 'archive');

\it('groups comics by chapter id and renders the archive', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);

    $comicRepo = $this->createMock(ComicRepositoryInterface::class);
    $comicRepo->method('findAll')->willReturn([
        new ComicPage(new ComicId('20260810'), 'Comicseite', 'A', '', '1', [], '', ''),
        new ComicPage(new ComicId('20260811'), 'Comicseite', 'B', '', '1', [], '', ''),
        new ComicPage(new ComicId('20260812'), 'Comicseite', 'C', '', '2', [], '', ''),
        new ComicPage(new ComicId('20260813'), 'Comicseite', 'D', '', null, [], '', ''), // Unassigned
    ]);

    $chapterRepo = $this->createMock(ChapterRepositoryInterface::class);
    $chapterRepo->method('findAll')->willReturn([
        new Chapter('1', 'Prolog'),
        new Chapter('2', 'Kapitel 1'),
    ]);

    $renderer = $this->createMock(TemplateRenderer::class);
    $renderer->expects($this->once())
        ->method('render')
        ->with('pages/frontend/archive', $this->callback(function (array $data): bool {
            $grouped = $data['groupedComics'];

            return isset($grouped['1'], $grouped['2'], $grouped['Kein Kapitel'])
                && \count($grouped['1']) === 2
                && \count($grouped['Kein Kapitel']) === 1;
        }))
        ->willReturn(new HtmlResponse('HTML'));

    $action = new ArchiveAction($comicRepo, $chapterRepo, $renderer);
    $action->execute(new ServerRequest());
})->covers(ArchiveAction::class);
