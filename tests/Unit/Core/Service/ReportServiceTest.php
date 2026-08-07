<?php
declare(strict_types=1);

use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\Report;
use App\Core\Exception\RateLimitExceededException;
use App\Core\Service\ReportService;

beforeEach(function () {
    // Mocks initialisieren
    $this->reportRepoMock = $this->createMock(ReportRepositoryInterface::class);
    $this->clockMock = $this->createMock(ClockInterface::class);

    // Service mit gemockten Abhängigkeiten instanziieren
    $this->service = new ReportService(
        $this->reportRepoMock,
        $this->clockMock
    );
});

it('throws RateLimitExceededException if user submits too many reports', function () {
    // Arrange
    $now = new \DateTimeImmutable('2026-08-07 12:00:00');
    $this->clockMock->method('now')->willReturn($now);

    // Wir simulieren, dass diese IP bereits 5 Reports gesendet hat (Limit)
    $this->reportRepoMock->expects($this->once())
        ->method('countRecentByIpHash')
        ->willReturn(5);

    // Act & Assert
    $this->service->submitReport(
        '20240101', 'usr_123', '127.0.0.1', 'Tester', false, 'other', null, 'Fehler!', '', '', ''
    );
})->throws(RateLimitExceededException::class);

it('successfully creates and saves a report if rate limit is not reached', function () {
    // Arrange
    $now = new \DateTimeImmutable('2026-08-07 12:00:00');
    $this->clockMock->method('now')->willReturn($now);

    $this->reportRepoMock->expects($this->once())
        ->method('countRecentByIpHash')
        ->willReturn(0); // Keine vorherigen Reports

    // Wir erwarten, dass save() genau einmal mit einer Report-Entität aufgerufen wird
    $this->reportRepoMock->expects($this->once())
        ->method('save')
        ->with($this->isInstanceOf(Report::class));

    // Act
    $report = $this->service->submitReport(
        '20240101a', null, '192.168.1.1', 'Gast', false, 'transcript', null, 'Tippfehler', 'Neuer Text', 'Alter Text', '{"browser":"Firefox"}'
    );

    // Assert
    expect($report)->toBeInstanceOf(Report::class)
        ->and($report->comicId?->value)->toBe('20240101a')
        ->and($report->ipHash)->toBe(hash('sha256', '192.168.1.1'))
        ->and($report->status)->toBe('open');
});
