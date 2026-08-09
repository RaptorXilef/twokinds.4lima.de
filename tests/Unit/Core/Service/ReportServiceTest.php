<?php

declare(strict_types=1);

use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\Report;
use App\Core\Exception\RateLimitExceededException;
use App\Core\Service\ReportService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

function setupReportTest(mixed $test): object
{
    $mock = \Closure::bind(fn (string $c) => $test->createMock($c), $test, $test::class);
    $stub = \Closure::bind(fn (string $c) => $test->createStub($c), $test, $test::class);

    return new class(
        $mock(ReportRepositoryInterface::class),
        $stub(ClockInterface::class),
    ) {
        public ReportService $service;

        public function __construct(
            public MockObject&ReportRepositoryInterface $reportRepo,
            public Stub&ClockInterface $clock,
        ) {
            // Service mit gemockten Abhängigkeiten instanziieren
            $this->service = new ReportService($this->reportRepo, $this->clock);
        }
    };
}

\it('throws RateLimitExceededException if user submits too many reports', function (): void {
    $app = \setupReportTest($this);

    // Arrange
    $now = new \DateTimeImmutable('2026-08-07 12:00:00');
    $app->clock->method('now')->willReturn($now);

    // Wir simulieren, dass diese IP bereits 5 Reports gesendet hat (Limit)
    $app->reportRepo->expects($this->once())
        ->method('countRecentByIpHash')
        ->willReturn(5);

    // Act & Assert
    $app->service->submitReport(
        '20240101',
        'usr_123',
        '127.0.0.1',
        'Tester',
        false,
        'other',
        null,
        'Fehler!',
        '',
        '',
        '',
    );
})->throws(RateLimitExceededException::class)->covers(ReportService::class);

\it('successfully creates and saves a report if rate limit is not reached', function (): void {
    $app = \setupReportTest($this);

    // Arrange
    $now = new \DateTimeImmutable('2026-08-07 12:00:00');
    $app->clock->method('now')->willReturn($now);

    $app->reportRepo->expects($this->once())
        ->method('countRecentByIpHash')
        ->willReturn(0); // Keine vorherigen Reports

    // Wir erwarten, dass save() genau einmal mit einer Report-Entität aufgerufen wird
    $app->reportRepo->expects($this->once())
        ->method('save')
        ->with($this->isInstanceOf(Report::class));

    // Act
    $report = $app->service->submitReport(
        '20240101a',
        null,
        '192.168.1.1',
        'Gast',
        false,
        'transcript',
        null,
        'Tippfehler',
        'Neuer Text',
        'Alter Text',
        '{"browser":"Firefox"}',
    );

    // Assert
    \expect($report)->toBeInstanceOf(Report::class)
        ->and($report->comicId?->value)->toBe('20240101a')
        ->and($report->ipHash)->toBe(\hash('sha256', '192.168.1.1'))
        ->and($report->status)->toBe('open');
})->covers(ReportService::class);
