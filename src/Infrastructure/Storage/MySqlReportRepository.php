<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ReportRepositoryInterface;
use App\Core\Entity\Report;
use App\Core\ValueObject\ReportId;
use App\Infrastructure\Database\Table;

final readonly class MySqlReportRepository implements ReportRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private \PDO $pdo)
    {
    }

    public function save(Report $report): void
    {
        $data = $this->extractEntity($report);

        // Die Spalte 'submitter_avatar_url' existiert in unserer Entität,
        // wird aber durch einen JOIN geladen. Daher entfernen wir sie vor dem Speichern in die reine 'reports' Tabelle.
        unset($data['submitter_avatar_url']);

        // Trait aufrufen. 'id', 'comic_id', 'date' etc. sollen beim Update nicht überschrieben werden.
        $this->executeUpsert(Table::REPORTS, $data, ['id', 'comic_id', 'date', 'ip_hash', 'submitter_name', 'type']);
    }

    public function findById(ReportId $id): ?Report
    {
        $stmt = $this->pdo->prepare('SELECT r.*, u.avatar_url as submitter_avatar_url FROM `' . Table::REPORTS . '` r LEFT JOIN `' . Table::USERS . '` u ON r.user_id = u.id WHERE r.id = ? LIMIT 1');
        $stmt->execute([$id->value]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->hydrateEntity(Report::class, $row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT r.*, u.avatar_url as submitter_avatar_url FROM `' . Table::REPORTS . '` r LEFT JOIN `' . Table::USERS . '` u ON r.user_id = u.id ORDER BY r.date DESC');

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map(fn (array $row): object => $this->hydrateEntity(Report::class, $row), $rows);
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare('SELECT r.*, u.avatar_url as submitter_avatar_url FROM `' . Table::REPORTS . '` r LEFT JOIN `' . Table::USERS . '` u ON r.user_id = u.id WHERE r.status = ? ORDER BY r.date DESC');
        $stmt->execute([$status]);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map(fn (array $row): object => $this->hydrateEntity(Report::class, $row), $rows);
    }

    public function countRecentByIpHash(string $ipHash, \DateTimeImmutable $since): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `' . Table::REPORTS . '` WHERE ip_hash = ? AND date >= ?');
        $stmt->execute([$ipHash, $since->format('Y-m-d H:i:s')]);

        return (int) $stmt->fetchColumn();
    }
}
