<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\Report;
use App\Core\Exception\EntityNotFoundException;
use App\Core\Exception\RateLimitExceededException;
use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\ReportId;

final readonly class ReportService
{
    private const int RATE_LIMIT_COUNT          = 5;
    private const int RATE_LIMIT_WINDOW_SECONDS = 600; // 10 Minuten

    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private ClockInterface $clock,
    ) {
    }

    public function submitReport(
        string $comicIdStr,
        string $ipAddress,
        string $submitterName,
        string $type,
        string $description,
        string $transcriptSuggestion,
        string $transcriptOriginal,
        string $debugInfo,
    ): Report {
        $now    = $this->clock->now();
        $ipHash = \hash('sha256', $ipAddress);

        // 1. High-Performance Rate-Limiting via MySQL
        $since         = $now->modify('-' . self::RATE_LIMIT_WINDOW_SECONDS . ' seconds');
        $recentReports = $this->reportRepository->countRecentByIpHash($ipHash, $since);

        if ($recentReports >= self::RATE_LIMIT_COUNT) {
            throw new RateLimitExceededException('Du hast das Limit für Meldungen erreicht. Bitte versuche es später noch einmal.');
        }

        // 2. Report Entity aufbauen (Validierung passiert automatisch in den VOs und der Entity)
        $report = new Report(
            id: new ReportId(\uniqid('report_', true)),
            comicId: new ComicId($comicIdStr),
            date: $now,
            status: 'open',
            ipHash: $ipHash,
            submitterName: \htmlspecialchars(\strip_tags($submitterName), \ENT_QUOTES, 'UTF-8'),
            type: $type,
            description: \htmlspecialchars(\strip_tags($description), \ENT_QUOTES, 'UTF-8'),
            transcriptSuggestion: $transcriptSuggestion, // HTML-Purify machen wir später im DTO oder der Action
            transcriptOriginal: $transcriptOriginal,
            debugInfo: \htmlspecialchars(\strip_tags($debugInfo), \ENT_QUOTES, 'UTF-8'),
        );

        // 3. Speichern
        $this->reportRepository->save($report);

        return $report;
    }

    public function updateReportStatus(ReportId $id, string $newStatus): void
    {
        $report = $this->reportRepository->findById($id);

        if (! $report instanceof Report) {
            throw new EntityNotFoundException("Report mit der ID {$id->value} nicht gefunden.");
        }

        $updatedReport = new Report(
            id: $report->id,
            comicId: $report->comicId,
            date: $report->date,
            status: $newStatus,
            ipHash: $report->ipHash,
            submitterName: $report->submitterName,
            type: $report->type,
            description: $report->description,
            transcriptSuggestion: $report->transcriptSuggestion,
            transcriptOriginal: $report->transcriptOriginal,
            debugInfo: $report->debugInfo,
        );

        $this->reportRepository->save($updatedReport);
    }
}
