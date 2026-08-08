<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Core\Entity\Bookmark;
use App\Infrastructure\Database\Table;
use Exception;
use PDO;

final readonly class MySqlBookmarkRepository implements BookmarkRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private PDO $pdo)
    {
    }

    public function findByUser(string $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::USER_BOOKMARKS . '` WHERE user_id = ? ORDER BY added_at DESC');
        $stmt->execute([$userId]);

        $bookmarks = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bookmarks[] = $this->hydrateEntity(Bookmark::class, $row);
        }

        return $bookmarks;
    }

    public function add(string $userId, string $comicId): void
    {
        $data = [
            'user_id' => $userId,
            'comic_id' => $comicId,
            'added_at' => \date('Y-m-d H:i:s'),
        ];
        // Nutzt REPLACE / INSERT IGNORE Logik
        $this->executeUpsert(Table::USER_BOOKMARKS, $data, ['user_id', 'comic_id']);
    }

    public function remove(string $userId, string $comicId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::USER_BOOKMARKS . '` WHERE user_id = ? AND comic_id = ?');
        $stmt->execute([$userId, $comicId]);
    }

    public function replaceUserBookmarks(string $userId, array $comicIds): void
    {
        $this->pdo->beginTransaction();

        try {
            // Erst alle löschen
            $stmtDel = $this->pdo->prepare('DELETE FROM `' . Table::USER_BOOKMARKS . '` WHERE user_id = ?');
            $stmtDel->execute([$userId]);

            // Dann die neuen sauber einfügen
            $stmtInsert = $this->pdo->prepare('INSERT INTO `' . Table::USER_BOOKMARKS . '` (user_id, comic_id, added_at) VALUES (?, ?, ?)');
            $now = \date('Y-m-d H:i:s');
            $uniqueIds = \array_unique($comicIds);

            // Duplikate vermeiden
            foreach ($uniqueIds as $cid) {
                $stmtInsert->execute([$userId, $cid, $now]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }
}
