<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\LoginAttemptRepositoryInterface;
use App\Core\Entity\LoginAttempt;
use App\Infrastructure\Database\Table;
use PDO;

final readonly class MySqlLoginAttemptRepository implements LoginAttemptRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function findByIp(string $ip): ?LoginAttempt
    {
        $stmt = $this->pdo->prepare('SELECT ip_address, attempts, last_attempt FROM `' . Table::LOGIN_ATTEMPTS . '` WHERE ip_address = ?');
        $stmt->execute([$ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Falls der IP string kaputt war, greift der Konstruktor-Schutz unserer Entität. Wir fangen das ab.
            if ($row['ip_address'] === '' || $row['ip_address'] === 'unknown') {
                $row['ip_address'] = '0.0.0.0';
            }

            return $this->hydrateEntity(LoginAttempt::class, $row);
        }

        return null;
    }

    public function save(LoginAttempt $attempt): void
    {
        $data = $this->extractEntity($attempt);
        $this->executeUpsert(Table::LOGIN_ATTEMPTS, $data, ['ip_address']);
    }

    public function deleteByIp(string $ip): void
    {
        $this->pdo->prepare('DELETE FROM `' . Table::LOGIN_ATTEMPTS . '` WHERE ip_address = ?')->execute([$ip]);
    }

    public function deleteOlderThan(int $minutes): void
    {
        $this->pdo->prepare('DELETE FROM `' . Table::LOGIN_ATTEMPTS . '` WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? MINUTE)')->execute([$minutes]);
    }
}
