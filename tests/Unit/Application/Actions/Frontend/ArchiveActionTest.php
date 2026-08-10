<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Frontend;

use App\Application\Actions\Frontend\ArchiveAction;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Application\View\TemplateRenderer;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use App\Core\Entity\Chapter;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\ComicId;
use App\Infrastructure\Utils\SystemClock;
use Closure;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('application', 'actions', 'frontend', 'archive');

\it('groups comics by chapter id and renders the archive', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);

    $comicRepo = $stub(ComicRepositoryInterface::class);
    $comicRepo->method('findAll')->willReturn([
        new ComicPage(new ComicId('20260810'), 'Comicseite', 'A', '', '1', [], '', ''),
        new ComicPage(new ComicId('20260811'), 'Comicseite', 'B', '', '1', [], '', ''),
        new ComicPage(new ComicId('20260812'), 'Comicseite', 'C', '', '2', [], '', ''),
        new ComicPage(new ComicId('20260813'), 'Comicseite', 'D', '', null, [], '', ''), // Unassigned
    ]);

    $chapterRepo = $stub(ChapterRepositoryInterface::class);
    $chapterRepo->method('findAll')->willReturn([
        new Chapter('1', 'Prolog'),
        new Chapter('2', 'Kapitel 1'),
    ]);

    if (\session_status() === \PHP_SESSION_NONE) {
        \session_start();
    }

    $config = $stub(ConfigInterface::class);
    $config->method('get')->willReturnMap([
        ['root_path', null, \realpath(__DIR__ . '/../../../../../')],
        ['site_title', null, 'Test Title'],
        ['base_url', null, 'http://localhost'],
    ]);

    $renderer = new TemplateRenderer(
        $config,
        $stub(ImageStorageInterface::class),
        $stub(JsonHelperInterface::class),
        new SessionManager(new SystemClock()),
        $stub(SystemInfoInterface::class),
        $stub(AssetHelperInterface::class),
    );

    $action = new ArchiveAction($comicRepo, $chapterRepo, $renderer);
    $response = $action->execute(new ServerRequest());

    \expect($response->statusCode)->toBe(200)
        ->and($response->html)->toContain('Prolog')
        ->and($response->html)->toContain('Kapitel 1');
})->covers(ArchiveAction::class);
