<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\LoginAttemptRepositoryInterface;
use App\Core\Entity\LoginAttempt;
use App\Core\ValueObject\IpAddress;

final readonly class MySqlLoginAttemptRepository implements LoginAttemptRepositoryInterface
{
    use DynamicSqlTrait;

    public function __construct(
        private \PDO $pdo,
    ) {
    }

    public function findByIp(string $ip): ?LoginAttempt
    {
        $stmt = $this->pdo->prepare('SELECT attempts, last_attempt FROM `login_attempts` WHERE ip_address = ?');
        $stmt->execute([$ip]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            return new LoginAttempt(
                new IpAddress($ip === 'unknown' || $ip === '' ? '0.0.0.0' : $ip),
                (int) $row['attempts'],
                new \DateTimeImmutable($row['last_attempt']),
            );
        }

        return null;
    }

    public function save(LoginAttempt $attempt): void
    {
        $data = [
            'ip_address'   => $attempt->ipAddress->value,
            'attempts'     => $attempt->attempts,
            'last_attempt' => $attempt->lastAttempt->format('Y-m-d H:i:s'),
        ];

        $this->executeUpsert('login_attempts', $data, ['ip_address']);
    }

    public function deleteByIp(string $ip): void
    {
        $this->pdo->prepare('DELETE FROM `login_attempts` WHERE ip_address = ?')->execute([$ip]);
    }

    public function deleteOlderThan(int $minutes): void
    {
        $this->pdo->prepare('DELETE FROM `login_attempts` WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? MINUTE)')->execute([$minutes]);
    }
}
