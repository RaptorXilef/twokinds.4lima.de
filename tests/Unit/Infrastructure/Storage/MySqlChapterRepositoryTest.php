<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Core\Entity\Chapter;
use App\Infrastructure\Storage\MySqlChapterRepository;
use PDO;
use PDOStatement;

\uses()->group('infrastructure', 'storage', 'database');

\it('returns all chapters from database hydrated as entities', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('query')
        ->with($this->stringContains('SELECT * FROM `chapters`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('fetchAll')
        ->with(PDO::FETCH_ASSOC)
        ->willReturn([
            ['id' => '1', 'title' => 'Prolog', 'description' => 'Es begann hier...'],
            ['id' => '2', 'title' => 'Die Reise', 'description' => 'Geht weiter...'],
        ]);

    $repo = new MySqlChapterRepository($pdo);
    $chapters = $repo->findAll();

    \expect($chapters)->toHaveCount(2)
        ->and($chapters[0])->toBeInstanceOf(Chapter::class)
        ->and($chapters[0]->id)->toBe('1')
        ->and($chapters[0]->title)->toBe('Prolog')
        ->and($chapters[1]->title)->toBe('Die Reise');
})->covers(MySqlChapterRepository::class);

\it('saves a chapter using the dynamic upsert trait', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    // DynamicSqlTrait executeUpsert will prepare and execute the query
    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `chapters` (`id`, `title`, `description`) VALUES (:id, :title, :description) ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`)'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with([
            'id' => '3',
            'title' => 'Neues Kapitel',
            'description' => 'Test Inhalt',
        ])
        ->willReturn(true);

    $repo = new MySqlChapterRepository($pdo);
    $chapter = new Chapter('3', 'Neues Kapitel', 'Test Inhalt');

    $repo->save($chapter);
})->covers(MySqlChapterRepository::class);

\it('deletes a chapter by its id', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with('DELETE FROM `chapters` WHERE id = ?')
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with(['4'])
        ->willReturn(true);

    $repo = new MySqlChapterRepository($pdo);
    $repo->delete('4');
})->covers(MySqlChapterRepository::class);
