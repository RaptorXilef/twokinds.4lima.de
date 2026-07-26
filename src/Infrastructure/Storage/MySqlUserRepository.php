<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;

final readonly class MySqlUserRepository implements UserRepositoryInterface
{
    use DynamicSqlTrait;

    public function __construct(
        private \PDO $pdo,
    ) {
    }

    public function findById(string $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `users` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `users` WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `users` WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function save(User $user): void
    {
        $data = [
            'id'                          => $user->id,
            'username'                    => $user->username,
            'email'                       => $user->email,
            'password_hash'               => $user->passwordHash,
            'role_id'                     => $user->roleId,
            'wants_newsletter'            => (int) $user->wantsNewsletter,
            'wants_newsletter_transcript' => (int) $user->wantsNewsletterTranscript,
            'wants_notification_report'   => (int) $user->wantsNotificationReport,
            'created_at'                  => $user->createdAt->format('Y-m-d H:i:s'),
        ];

        // ID und created_at werden bei Updates nicht überschrieben!
        $this->executeUpsert('users', $data, ['id', 'created_at']);
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `users` WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function mapToEntity(array $row): User
    {
        return new User(
            $row['id'],
            $row['username'],
            $row['email'],
            $row['password_hash'],
            $row['role_id'],
            new \DateTimeImmutable($row['created_at']),
            (bool) ($row['wants_newsletter'] ?? false),
            (bool) ($row['wants_newsletter_transcript'] ?? false),
            (bool) ($row['wants_notification_report'] ?? false), // NEU
        );
    }

    public function deleteUnverifiedAccounts(int $olderThanMinutes): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM `users` WHERE role_id = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$olderThanMinutes]);

        return $stmt->rowCount();
    }

    public function findNewsletterSubscribers(bool $transcriptOnly = false): array
    {
        $column = $transcriptOnly ? 'wants_newsletter_transcript' : 'wants_newsletter';

        // Da wir dynamische Spaltennamen nutzen (die sicher von uns kommen), ist das absolut SQL-Injection-sicher
        $stmt = $this->pdo->query("SELECT * FROM `users` WHERE {$column} = 1");

        $users = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $users[] = $this->mapToEntity($row);
        }

        return $users;
    }
}
