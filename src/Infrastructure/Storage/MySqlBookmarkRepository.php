<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Core\Entity\Bookmark;

final readonly class MySqlBookmarkRepository implements BookmarkRepositoryInterface
{
    use DynamicSqlTrait;

    public function __construct(private \PDO $pdo)
    {
    }

    public function findByUser(string $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `user_bookmarks` WHERE user_id = ? ORDER BY added_at DESC');
        $stmt->execute([$userId]);

        $bookmarks = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $bookmarks[] = new Bookmark(
                $row['user_id'],
                $row['comic_id'],
                new \DateTimeImmutable($row['added_at']),
            );
        }

        return $bookmarks;
    }

    public function add(string $userId, string $comicId): void
    {
        $data = [
            'user_id'  => $userId,
            'comic_id' => $comicId,
            'added_at' => \date('Y-m-d H:i:s'),
        ];
        // Nutzt REPLACE / INSERT IGNORE Logik
        $this->executeUpsert('user_bookmarks', $data, ['user_id', 'comic_id']);
    }

    public function remove(string $userId, string $comicId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `user_bookmarks` WHERE user_id = ? AND comic_id = ?');
        $stmt->execute([$userId, $comicId]);
    }

    public function replaceUserBookmarks(string $userId, array $comicIds): void
    {
        $this->pdo->beginTransaction();

        try {
            // Erst alle löschen
            $stmtDel = $this->pdo->prepare('DELETE FROM `user_bookmarks` WHERE user_id = ?');
            $stmtDel->execute([$userId]);

            // Dann die neuen sauber einfügen
            $stmtInsert = $this->pdo->prepare('INSERT INTO `user_bookmarks` (user_id, comic_id, added_at) VALUES (?, ?, ?)');
            $now        = \date('Y-m-d H:i:s');

            // Duplikate vermeiden
            $uniqueIds = \array_unique($comicIds);
            foreach ($uniqueIds as $cid) {
                $stmtInsert->execute([$userId, $cid, $now]);
            }

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }
}
