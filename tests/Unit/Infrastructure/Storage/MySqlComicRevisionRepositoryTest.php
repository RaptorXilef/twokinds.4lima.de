<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use App\Infrastructure\Storage\MySqlComicRevisionRepository;
use PDO;
use PDOStatement;

\uses()->group('infrastructure', 'storage', 'database');

\it('creates snapshot and cleans up old ones', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $clock = $this->createStub(ClockInterface::class);
    $config = $this->createStub(ConfigInterface::class);

    $config->method('get')->willReturn(10); // Limit 10

    // Prepare is called twice: once for INSERT, once for DELETE (Cleanup)
    $pdo->expects($this->exactly(2))->method('prepare')->willReturn($stmt);
    $stmt->expects($this->exactly(2))->method('execute')->willReturn(true);

    $repo = new MySqlComicRevisionRepository($pdo, $clock, $config);
    $comic = new ComicPage(new ComicId('20260810'), 'Comicseite', 'Test', null, null, [new CharacterId('char_0001')], '', '');

    $repo->createSnapshot($comic);
})->covers(MySqlComicRevisionRepository::class);

\it('pops latest revision and returns array', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $clock = $this->createStub(ClockInterface::class);
    $config = $this->createStub(ConfigInterface::class);

    $pdo->expects($this->exactly(2))->method('prepare')->willReturn($stmt);
    $stmt->expects($this->exactly(2))->method('execute');

    // First query is SELECT, second is DELETE
    $stmt->expects($this->once())->method('fetch')->willReturn([
        'id' => 1,
        'revision_data' => '{"type": "Comicseite", "name": "Old"}',
    ]);

    $repo = new MySqlComicRevisionRepository($pdo, $clock, $config);
    $data = $repo->popLatestRevision(new ComicId('20260810'));

    \expect($data)->toBeArray()
        ->and($data['name'])->toBe('Old');
})->covers(MySqlComicRevisionRepository::class);

\it('pops latest deleted revision and returns array', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $clock = $this->createStub(ClockInterface::class);
    $config = $this->createStub(ConfigInterface::class);

    $pdo->expects($this->once())->method('query')->willReturn($stmt); // SELECT
    $pdo->expects($this->once())->method('prepare')->willReturn($stmt); // DELETE

    $stmt->expects($this->once())->method('fetch')->willReturn([
        'id' => 2,
        'comic_id' => '20260811',
        'revision_data' => '{"name": "Deleted"}',
    ]);

    $repo = new MySqlComicRevisionRepository($pdo, $clock, $config);
    $data = $repo->popLatestDeletedRevision();

    \expect($data)->toBeArray()
        ->and($data['comic_id'])->toBe('20260811')
        ->and($data['name'])->toBe('Deleted');
})->covers(MySqlComicRevisionRepository::class);
