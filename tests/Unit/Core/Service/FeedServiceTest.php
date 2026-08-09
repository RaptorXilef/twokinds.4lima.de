<?php

declare(strict_types=1);

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\FeedService;
use App\Core\ValueObject\ComicId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

function setupFeedTest(mixed $test): object
{
    $mock = \Closure::bind(fn (string $c) => $test->createMock($c), $test, $test::class);
    $stub = \Closure::bind(fn (string $c) => $test->createStub($c), $test, $test::class);

    return new class (
        $mock(ComicRepositoryInterface::class),
        $stub(ConfigInterface::class),
        $stub(ClockInterface::class),
    ) {
        public FeedService $service;

        public function __construct(
            public MockObject&ComicRepositoryInterface $comicRepo,
            public Stub&ConfigInterface $config,
            public Stub&ClockInterface $clock,
        ) {
            $this->service = new FeedService($this->comicRepo, $this->config, $this->clock);
        }
    };
}

\it('generates a valid RSS XML feed with comic items', function (): void {
    $app = \setupFeedTest($this);

    // Arrange
    $now = new \DateTimeImmutable('2026-08-07 12:00:00');
    $app->clock->method('now')->willReturn($now);

    $app->config->method('getBaseUrl')->willReturn('https://twokinds.4lima.local');
    $app->config->method('get')->willReturn('Twokinds auf Deutsch'); // Standard-Titel

    $comic = new ComicPage(
        new ComicId('20260807'),
        'Comicseite',
        'Test Comic',
        '<p>Test</p>',
        null,
        [],
        '',
        '',
        [],
        $now->getTimestamp(),
    );

    $app->comicRepo->expects($this->once())
        ->method('findAll')
        ->willReturn([$comic]);

    // Act
    $xml = $app->service->generateRssXml(10);

    // Assert
    // Wir prüfen die XML-Elemente separat, da die Reihenfolge variieren kann
    \expect($xml)->toContain('<rss')
        ->and($xml)->toContain('version="2.0"')
        ->and($xml)->toContain('xmlns:atom="http://www.w3.org/2005/Atom"')
        ->and($xml)->toContain('<title>Twokinds auf Deutsch</title>')
        ->and($xml)->toContain('<title>Test Comic</title>')
        ->and($xml)->toContain('https://twokinds.4lima.local/comic/20260807');
})->covers(FeedService::class);
