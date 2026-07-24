<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ReportRepositoryInterface;
use App\Core\Entity\Report;
use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\ReportId;

final readonly class MySqlReportRepository implements ReportRepositoryInterface
{
    use DynamicSqlTrait; // Trait einbinden

    public function __construct(private \PDO $pdo)
    {
    }

    public function save(Report $report): void
    {
        // Nur noch das Array definieren. Das Schema diktiert die Keys.
        $data = [
            'id'                    => $report->id->value,
            'comic_id'              => $report->comicId?->value, // ? Optional
            'date'                  => $report->date->format('Y-m-d H:i:s'),
            'status'                => $report->status,
            'ip_hash'               => $report->ipHash,
            'submitter_name'        => $report->submitterName,
            'wants_credit'          => (int) $report->wantsCredit,
            'type'                  => $report->type,
            'screenshot_url'        => $report->screenshotUrl,
            'description'           => $report->description,
            'transcript_suggestion' => $report->transcriptSuggestion,
            'transcript_original'   => $report->transcriptOriginal,
            'debug_info'            => $report->debugInfo,
        ];

        // Trait aufrufen. 'id', 'comic_id', 'date' etc. sollen beim Update nicht überschrieben werden.
        $this->executeUpsert('reports', $data, ['id', 'comic_id', 'date', 'ip_hash', 'submitter_name', 'type']);
    }

    public function findById(ReportId $id): ?Report
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `reports` WHERE id = ? LIMIT 1');
        $stmt->execute([$id->value]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `reports` ORDER BY date DESC');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map($this->mapToEntity(...), $rows);
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `reports` WHERE status = ? ORDER BY date DESC');
        $stmt->execute([$status]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map($this->mapToEntity(...), $rows);
    }

    private function mapToEntity(array $row): Report
    {
        return new Report(
            id: new ReportId($row['id']),
            comicId: ! empty($row['comic_id']) ? new ComicId($row['comic_id']) : null,
            date: new \DateTimeImmutable($row['date']),
            status: $row['status'],
            ipHash: $row['ip_hash'],
            submitterName: $row['submitter_name'],
            wantsCredit: (bool) ($row['wants_credit'] ?? false),
            type: $row['type'],
            screenshotUrl: $row['screenshot_url'] ?? null,
            description: $row['description'] ?? '',
            transcriptSuggestion: $row['transcript_suggestion'] ?? '',
            transcriptOriginal: $row['transcript_original'] ?? '',
            debugInfo: $row['debug_info'] ?? '',
        );
    }

    public function countRecentByIpHash(string $ipHash, \DateTimeImmutable $since): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `reports` WHERE ip_hash = ? AND date >= ?');
        $stmt->execute([$ipHash, $since->format('Y-m-d H:i:s')]);

        return (int) $stmt->fetchColumn();
    }
}
