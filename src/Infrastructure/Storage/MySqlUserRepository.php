<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Infrastructure\Database\Table;
use PDO;

final readonly class MySqlUserRepository implements UserRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private PDO $pdo)
    {
    }

    public function findById(string $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::USERS . '` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $validRow */
        $validRow = $row;

        return $this->hydrateEntity(User::class, $validRow);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::USERS . '` WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $validRow */
        $validRow = $row;

        return $this->hydrateEntity(User::class, $validRow);
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::USERS . '` WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $validRow */
        $validRow = $row;

        return $this->hydrateEntity(User::class, $validRow);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::USERS . '` ORDER BY created_at DESC');
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!\is_array($rows)) {
            return [];
        }

        /** @var array<int, User> $users */
        $users = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            /** @var array<string, mixed> $validRow */
            $validRow = $row;

            $users[] = $this->hydrateEntity(User::class, $validRow);
        }

        return $users;
    }

    public function save(User $user): void
    {
        $data = $this->extractEntity($user);
        $this->executeUpsert(Table::USERS, $data, ['id', 'created_at']);
    }

    public function delete(string $id): void
    {
        // 1. Physisches Avatar-Bild löschen
        $user = $this->findById($id);
        if ($user instanceof User && $user->avatarUrl !== null) {
            $avatarPath = \dirname(__DIR__, 3) . '/public/assets/images/avatars/' . $user->avatarUrl;
            if (\file_exists($avatarPath)) {
                \unlink($avatarPath);
            }
        }

        // 2. Kaskadierendes Löschen der User-ID aus ALLEN Comics (Die JSON Magie)
        $sql = 'UPDATE `' . Table::COMICS . "`
                SET `user_ids` = JSON_REMOVE(`user_ids`, JSON_UNQUOTE(JSON_SEARCH(`user_ids`, 'one', ?)))
                WHERE JSON_SEARCH(`user_ids`, 'one', ?) IS NOT NULL";
        $stmtClean = $this->pdo->prepare($sql);
        $stmtClean->execute([$id, $id]);

        // 3. Reports anonymisieren (DSGVO & Datenhygiene)
        $stmtReports = $this->pdo->prepare('UPDATE `' . Table::REPORTS . "` SET `user_id` = NULL, `submitter_name` = 'Gelöschter Nutzer' WHERE `user_id` = ?"); // phpcs:ignore Generic.Files.LineLength.TooLong
        $stmtReports->execute([$id]);

        // 4. User löschen
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::USERS . '` WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function deleteUnverifiedAccounts(int $olderThanMinutes): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::USERS . "` WHERE role_id = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)");  // phpcs:ignore Generic.Files.LineLength.TooLong
        $stmt->execute([$olderThanMinutes]);

        return $stmt->rowCount();
    }

    public function findNewsletterSubscribers(bool $transcriptOnly = false): array
    {
        $column = $transcriptOnly ? 'wants_newsletter_transcript' : 'wants_newsletter';

        // Da wir dynamische Spaltennamen nutzen (die sicher von uns kommen), ist das absolut SQL-Injection-sicher
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::USERS . "` WHERE {$column} = 1");
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!\is_array($rows)) {
            return [];
        }

        /** @var array<int, User> $users */
        $users = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            /** @var array<string, mixed> $validRow */
            $validRow = $row;

            $users[] = $this->hydrateEntity(User::class, $validRow);
        }

        return $users;
    }
}
