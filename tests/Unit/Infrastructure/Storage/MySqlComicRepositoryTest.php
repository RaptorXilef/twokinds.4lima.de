<?php

declare(strict_types=1);

use App\Core\Entity\ComicPage;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use App\Infrastructure\Storage\MySqlComicRepository;

\uses()->group('infrastructure', 'storage', 'database');

\it('saves a comic page using dynamic upsert', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `comics`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->willReturn(true);

    $repo = new MySqlComicRepository($pdo);
    $comic = new ComicPage(
        new ComicId('20260810'),
        'Comicseite',
        'Test Comic',
        null,
        null,
        [new CharacterId('char_0001')],
        '',
        '',
    );

    $repo->save($comic);
})->covers(MySqlComicRepository::class);

\it('finds a comic by id and hydrates it', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('SELECT * FROM `comics` WHERE id = ?'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with(['20260810']);

    $stmt->expects($this->once())
        ->method('fetch')
        ->with(\PDO::FETCH_ASSOC)
        ->willReturn([
            'id' => '20260810',
            'type' => 'Comicseite',
            'name' => 'Trace wakes up',
            'transcript' => 'Uh...',
            'chapter_id' => '1',
            'character_ids' => '["char_0001"]',
            'user_ids' => '["usr_123"]',
            'original_url' => '123.jpg',
            'sketch_url' => '',
            'image_updated_at' => 1234567890,
        ]);

    $repo = new MySqlComicRepository($pdo);
    $comic = $repo->findById(new ComicId('20260810'));

    \expect($comic)->toBeInstanceOf(ComicPage::class)
        ->and($comic->name)->toBe('Trace wakes up')
        ->and($comic->characterIds)->toHaveCount(1)
        ->and($comic->characterIds[0]->value)->toBe('char_0001');
})->covers(MySqlComicRepository::class);

\it('returns all comics ordered by id descending', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())
        ->method('query')
        ->with($this->stringContains('SELECT * FROM `comics` ORDER BY id DESC'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('fetchAll')
        ->with(\PDO::FETCH_ASSOC)
        ->willReturn([
            ['id' => '20260811', 'type' => 'Comicseite', 'name' => 'Next', 'original_url' => '', 'sketch_url' => ''],
            ['id' => '20260810', 'type' => 'Comicseite', 'name' => 'Prev', 'original_url' => '', 'sketch_url' => ''],
        ]);

    $repo = new MySqlComicRepository($pdo);
    $comics = $repo->findAll();

    \expect($comics)->toHaveCount(2)
        ->and($comics[0]->id->value)->toBe('20260811');
})->covers(MySqlComicRepository::class);

\it('renames a comic id across all related tables using transactions', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())->method('beginTransaction');
    $pdo->expects($this->once())->method('commit');
    $pdo->expects($this->never())->method('rollBack');

    $pdo->expects($this->exactly(3))
        ->method('prepare')
        ->willReturn($stmt);

    $stmt->expects($this->exactly(3))
        ->method('execute')
        ->with(['20260811', '20260810'])
        ->willReturn(true);

    $repo = new MySqlComicRepository($pdo);
    $repo->renameComicId(new ComicId('20260810'), new ComicId('20260811'));
})->covers(MySqlComicRepository::class);
