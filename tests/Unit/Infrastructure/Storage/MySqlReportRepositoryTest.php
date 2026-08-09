<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Core\Entity\Report;
use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\ReportId;
use App\Infrastructure\Storage\MySqlReportRepository;
use DateTimeImmutable;
use PDO;
use PDOStatement;

\uses()->group('infrastructure', 'storage', 'database');

\it('saves a report using the dynamic upsert trait', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `reports`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->willReturn(true);

    $repo = new MySqlReportRepository($pdo);
    $report = new Report(
        new ReportId('report_123'),
        new ComicId('20260810'),
        'usr_1',
        new DateTimeImmutable(),
        'open',
        'hash123',
        'Submitter',
        true,
        'transcript',
        null,
        'Test',
        'Sugg',
        'Orig',
        '{}',
    );

    $repo->save($report);
})->covers(MySqlReportRepository::class);

\it('finds a report by id including submitter avatar via JOIN', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('SELECT r.*, u.avatar_url as submitter_avatar_url FROM `reports`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with(['report_123']);

    $stmt->expects($this->once())
        ->method('fetch')
        ->with(PDO::FETCH_ASSOC)
        ->willReturn([
            'id' => 'report_123',
            'status' => 'open',
            'type' => 'other',
            'submitter_name' => 'Trace',
            'ip_hash' => 'hash',
            'date' => '2026-08-10 12:00:00',
            'wants_credit' => 1,
            'description' => 'Test',
            'transcript_suggestion' => '',
            'transcript_original' => '',
            'debug_info' => '{}',
            'submitter_avatar_url' => 'trace.webp',
        ]);

    $repo = new MySqlReportRepository($pdo);
    $report = $repo->findById(new ReportId('report_123'));

    \expect($report)->toBeInstanceOf(Report::class)
        ->and($report->submitterName)->toBe('Trace')
        ->and($report->submitterAvatarUrl)->toBe('trace.webp');
})->covers(MySqlReportRepository::class);

\it('counts recent reports by ip hash', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('SELECT COUNT(*) FROM `reports` WHERE ip_hash = ? AND date >='))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute');

    $stmt->expects($this->once())
        ->method('fetchColumn')
        ->willReturn(3);

    $repo = new MySqlReportRepository($pdo);
    $count = $repo->countRecentByIpHash('hash123', new DateTimeImmutable());

    \expect($count)->toBe(3);
})->covers(MySqlReportRepository::class);

\it('finds all reports and handles empty result gracefully', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('query')
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('fetchAll')
        ->willReturn([]);

    $repo = new MySqlReportRepository($pdo);
    \expect($repo->findAll())->toBeArray()->toBeEmpty();
})->covers(MySqlReportRepository::class);

\it('finds reports by status', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('WHERE r.status = ?'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with(['spam']);

    $stmt->expects($this->once())
        ->method('fetchAll')
        ->willReturn([
            [
                'id' => 'report_1',
                'status' => 'spam',
                'type' => 'other',
                'submitter_name' => 'Spammer',
                'ip_hash' => 'hash',
                'date' => '2026-08-10 12:00:00',
                'wants_credit' => 0,
                'description' => 'Test',
                'transcript_suggestion' => '',
                'transcript_original' => '',
                'debug_info' => '{}',
            ],
        ]);

    $repo = new MySqlReportRepository($pdo);
    $reports = $repo->findByStatus('spam');

    \expect($reports)->toHaveCount(1)
        ->and($reports[0]->status)->toBe('spam');
})->covers(MySqlReportRepository::class);
