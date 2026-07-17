<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ReportRepositoryInterface;
use App\Core\Entity\Report;
use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\ReportId;

final readonly class MySqlReportRepository implements ReportRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function save(Report $report): void
    {
        $sql = 'INSERT INTO `reports`
                (id, comic_id, date, status, ip_hash, submitter_name, type, description, transcript_suggestion, transcript_original, debug_info)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                status = VALUES(status), description = VALUES(description),
                transcript_suggestion = VALUES(transcript_suggestion)';

        $this->pdo->prepare($sql)->execute([
            $report->id->value,
            $report->comicId->value,
            $report->date->format('Y-m-d H:i:s'),
            $report->status,
            $report->ipHash,
            $report->submitterName,
            $report->type,
            $report->description,
            $report->transcriptSuggestion,
            $report->transcriptOriginal,
            $report->debugInfo,
        ]);
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
            comicId: new ComicId($row['comic_id']),
            date: new \DateTimeImmutable($row['date']),
            status: $row['status'],
            ipHash: $row['ip_hash'],
            submitterName: $row['submitter_name'],
            type: $row['type'],
            description: $row['description'] ?? '',
            transcriptSuggestion: $row['transcript_suggestion'] ?? '',
            transcriptOriginal: $row['transcript_original'] ?? '',
            debugInfo: $row['debug_info'] ?? '',
        );
    }
}
