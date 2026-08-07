<?php

declare(strict_types=1);

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\MagicLink;
use App\Core\Service\MagicLinkService;
use App\Core\ValueObject\EmailAddress;
use PHPUnit\Framework\MockObject\MockObject;

function setupMagicLinkTest(mixed $test): object
{
    $mock = \Closure::bind(fn (string $c) => $test->createMock($c), $test, $test::class);

    return new class($mock(ClockInterface::class), $mock(ConfigInterface::class), $mock(MagicLinkRepositoryInterface::class)) {
        public MagicLinkService $service;

        public function __construct(
            public MockObject&ClockInterface $clock,
            public MockObject&ConfigInterface $config,
            public MockObject&MagicLinkRepositoryInterface $repo,
        ) {
            $this->service = new MagicLinkService($this->clock, $this->config, $this->repo);
        }
    };
}

\it('creates a new token and saves it to the repository', function (): void {
    $app = \setupMagicLinkTest($this);

    $now = new \DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);

    $app->config->expects($this->once())
        ->method('get')
        ->with('magic_link_duration', 15)
        ->willReturn(30); // 30 Minuten Lebensdauer konfigurieren

    $app->repo->expects($this->once())
        ->method('loadAll')
        ->willReturn([]);

    $app->repo->expects($this->once())
        ->method('saveAll')
        ->with($this->callback(function (array $links) {
            if (\count($links) !== 1) {
                return false;
            }
            $link = \reset($links);

            return $link instanceof MagicLink
                && $link->email->value === 'test@twokinds.de'
                && \strlen($link->token) === 64
                && \strlen($link->code) === 6
                // Prüfen ob exakt 30 Minuten addiert wurden
                && $link->expires->format('Y-m-d H:i:s') === '2026-08-07 10:30:00';
        }));

    $result = $app->service->createToken('test@twokinds.de');

    \expect($result)->toHaveKeys(['token', 'code']);
})->covers(MagicLinkService::class);

\it('peeks token and returns email if not expired', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new \DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);

    $validLink = new MagicLink(
        'valid_token',
        new EmailAddress('user@test.de'),
        '123456',
        $now->modify('+10 minutes'),
    );

    $app->repo->expects($this->once())
        ->method('loadAll')
        ->willReturn(['valid_token' => $validLink]);

    $email = $app->service->peekToken('valid_token');
    \expect($email)->toBe('user@test.de');
})->covers(MagicLinkService::class);

\it('peek returns null if token is expired or invalid', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new \DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);

    $expiredLink = new MagicLink(
        'expired_token',
        new EmailAddress('user@test.de'),
        '123456',
        $now->modify('-5 minutes'), // Liegt in der Vergangenheit
    );

    $app->repo->method('loadAll')->willReturn(['expired_token' => $expiredLink]);

    \expect($app->service->peekToken('expired_token'))->toBeNull()
        ->and($app->service->peekToken('unknown_token'))->toBeNull();
})->covers(MagicLinkService::class);

\it('verifies a token, deletes it, and returns the email', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new \DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);

    $validLink = new MagicLink(
        'very_long_secret_token_123',
        new EmailAddress('user@test.de'),
        'ABCDEF',
        $now->modify('+10 minutes'),
    );

    // loadAll gibt den Link zurück
    $app->repo->method('loadAll')->willReturn(['very_long_secret_token_123' => $validLink]);

    // saveAll wird erwartet, aber diesmal MUSS das Array leer sein (Link wurde verbraucht)
    $app->repo->expects($this->once())
        ->method('saveAll')
        ->with([]);

    $email = $app->service->verifyAny('very_long_secret_token_123');
    \expect($email)->toBe('user@test.de');
})->covers(MagicLinkService::class);

\it('verifies a token by short code, deletes it, and returns the email', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new \DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);

    $validLink = new MagicLink(
        'very_long_secret_token_123',
        new EmailAddress('user@test.de'),
        'ABCDEF',
        $now->modify('+10 minutes'),
    );

    $app->repo->method('loadAll')->willReturn(['very_long_secret_token_123' => $validLink]);

    // saveAll wird erwartet, auch hier muss das Array geleert werden
    $app->repo->expects($this->once())
        ->method('saveAll')
        ->with([]);

    // Wir übergeben den 6-stelligen Short Code (case-insensitive)
    $email = $app->service->verifyAny('abcdef');
    \expect($email)->toBe('user@test.de');
})->covers(MagicLinkService::class);
