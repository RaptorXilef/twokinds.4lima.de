<?php

declare(strict_types=1);

use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\ComicService;
use App\Core\ValueObject\ComicId;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * ZENTRALER SETUP: Hier wird der Service und alle Mocks exakt EINMAL instanziiert.
 */
function setupComicTest(mixed $test): object
{
    // Erlaubt den Zugriff auf die protected 'createMock' Methode
    $mock = \Closure::bind(fn (string $c) => $test->createMock($c), $test, $test::class);

    return new class($mock(ComicRepositoryInterface::class), $mock(ComicRevisionRepositoryInterface::class), $mock(ClockInterface::class), $mock(SiteGeneratorInterface::class)) {
        public ComicService $service;

        public function __construct(
            public MockObject&ComicRepositoryInterface $comicRepo,
            public MockObject&ComicRevisionRepositoryInterface $revisionRepo,
            public MockObject&ClockInterface $clock,
            public MockObject&SiteGeneratorInterface $siteGen,
        ) {
            $this->service = new ComicService(
                $this->comicRepo,
                $this->revisionRepo,
                $this->clock,
                $this->siteGen,
            );
        }
    };
}

\it('saves a new comic without creating a revision', function (): void {
    $app = \setupComicTest($this);

    // Arrange
    $comicId = new ComicId('20260807');
    $comic   = new ComicPage($comicId, 'Comicseite', 'Test', null, null, [], '', '');

    // Comic existiert noch nicht
    $app->comicRepo->expects($this->once())
        ->method('findById')
        ->willReturn(null);

    // Es darf KEIN Snapshot erstellt werden
    $app->revisionRepo->expects($this->never())
        ->method('createSnapshot');

    // Comic muss gespeichert werden
    $app->comicRepo->expects($this->once())
        ->method('save')
        ->with($comic);

    // SiteGenerator muss getriggert werden
    $app->siteGen->expects($this->once())
        ->method('generateAll');

    // Act
    $app->service->saveComic($comic);

})->covers(ComicService::class);

\it('creates a revision when saving an existing comic', function (): void {
    $app = \setupComicTest($this);

    // Arrange
    $comicId       = new ComicId('20260807');
    $existingComic = new ComicPage($comicId, 'Comicseite', 'Old Name', null, null, [], '', '');
    $updatedComic  = new ComicPage($comicId, 'Comicseite', 'New Name', null, null, [], '', '');

    // Comic existiert bereits
    $app->comicRepo->expects($this->once())
        ->method('findById')
        ->willReturn($existingComic);

    // Es MUSS ein Snapshot vom ALTZUSTAND erstellt werden
    $app->revisionRepo->expects($this->once())
        ->method('createSnapshot')
        ->with($existingComic);

    $app->comicRepo->expects($this->once())
        ->method('save')
        ->with($updatedComic);

    // Act
    $app->service->saveComic($updatedComic);

})->covers(ComicService::class);
