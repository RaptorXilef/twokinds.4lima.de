<?php
declare(strict_types=1);

use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\ComicService;
use App\Core\ValueObject\ComicId;

covers(ComicService::class);

beforeEach(function () {
    $this->comicRepoMock = $this->createMock(ComicRepositoryInterface::class);
    $this->revisionRepoMock = $this->createMock(ComicRevisionRepositoryInterface::class);
    $this->clockMock = $this->createMock(ClockInterface::class);
    $this->siteGenMock = $this->createMock(SiteGeneratorInterface::class);

    $this->service = new ComicService(
        $this->comicRepoMock,
        $this->revisionRepoMock,
        $this->clockMock,
        $this->siteGenMock
    );
});

it('saves a new comic without creating a revision', function () {
    // Arrange
    $comicId = new ComicId('20260807');
    $comic = new ComicPage($comicId, 'Comicseite', 'Test', null, null, [], '', '');

    // Comic existiert noch nicht
    $this->comicRepoMock->expects($this->once())
        ->method('findById')
        ->willReturn(null);

    // Es darf KEIN Snapshot erstellt werden
    $this->revisionRepoMock->expects($this->never())
        ->method('createSnapshot');

    // Comic muss gespeichert werden
    $this->comicRepoMock->expects($this->once())
        ->method('save')
        ->with($comic);

    // SiteGenerator muss getriggert werden
    $this->siteGenMock->expects($this->once())
        ->method('generateAll');

    // Act
    $this->service->saveComic($comic);
});

it('creates a revision when saving an existing comic', function () {
    // Arrange
    $comicId = new ComicId('20260807');
    $existingComic = new ComicPage($comicId, 'Comicseite', 'Old Name', null, null, [], '', '');
    $updatedComic = new ComicPage($comicId, 'Comicseite', 'New Name', null, null, [], '', '');

    // Comic existiert bereits
    $this->comicRepoMock->expects($this->once())
        ->method('findById')
        ->willReturn($existingComic);

    // Es MUSS ein Snapshot vom ALTZUSTAND erstellt werden
    $this->revisionRepoMock->expects($this->once())
        ->method('createSnapshot')
        ->with($existingComic);

    $this->comicRepoMock->expects($this->once())
        ->method('save')
        ->with($updatedComic);

    // Act
    $this->service->saveComic($updatedComic);
});
