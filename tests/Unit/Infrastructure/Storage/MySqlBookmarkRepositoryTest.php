<?php

declare(strict_types=1);

use App\Core\Entity\Bookmark;
use App\Infrastructure\Storage\MySqlBookmarkRepository;

\uses()->group('infrastructure', 'storage', 'database');

\it('finds bookmarks by user', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('SELECT * FROM `user_bookmarks` WHERE user_id = ?'))
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->with(['usr_1']);
    $stmt->expects($this->once())
        ->method('fetchAll')
        ->with(\PDO::FETCH_ASSOC)
        ->willReturn([
            ['user_id' => 'usr_1', 'comic_id' => '20260810', 'added_at' => '2026-08-10 12:00:00'],
        ]);

    $repo = new MySqlBookmarkRepository($pdo);
    $bookmarks = $repo->findByUser('usr_1');

    \expect($bookmarks)->toHaveCount(1)
        ->and($bookmarks[0])->toBeInstanceOf(Bookmark::class)
        ->and($bookmarks[0]->comicId)->toBe('20260810');
})->covers(MySqlBookmarkRepository::class);

\it('adds a new bookmark via upsert', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `user_bookmarks`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->willReturn(true);

    $repo = new MySqlBookmarkRepository($pdo);
    $repo->add('usr_1', '20260811');
})->covers(MySqlBookmarkRepository::class);

\it('removes a bookmark', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('DELETE FROM `user_bookmarks` WHERE user_id = ? AND comic_id = ?'))
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->with(['usr_1', '20260811'])->willReturn(true);

    $repo = new MySqlBookmarkRepository($pdo);
    $repo->remove('usr_1', '20260811');
})->covers(MySqlBookmarkRepository::class);

\it('replaces all bookmarks using transactions', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmtDel = $this->createMock(\PDOStatement::class);
    $stmtIns = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())->method('beginTransaction');
    $pdo->expects($this->once())->method('commit');

    $pdo->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnMap([
            ['DELETE FROM `user_bookmarks` WHERE user_id = ?', [], $stmtDel],
            ['INSERT INTO `user_bookmarks` (user_id, comic_id, added_at) VALUES (?, ?, ?)', [], $stmtIns],
        ]);

    $stmtDel->expects($this->once())->method('execute')->with(['usr_1']);
    // Should execute twice because we pass 2 unique IDs
    $stmtIns->expects($this->exactly(2))->method('execute');

    $repo = new MySqlBookmarkRepository($pdo);
    $repo->replaceUserBookmarks('usr_1', ['20260810', '20260811', '20260810']); // 1 is duplicate
})->covers(MySqlBookmarkRepository::class);
