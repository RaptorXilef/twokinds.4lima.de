<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\MagicLink;
use App\Core\Service\MagicLinkService;
use App\Core\ValueObject\EmailAddress;
use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('core', 'service');

function setupMagicLinkTest(mixed $test): object
{
    $mock = Closure::bind(fn (string $c): MockObject => $test->createMock($c), $test, $test::class);
    $stub = Closure::bind(fn (string $c): Stub => $test->createStub($c), $test, $test::class);

    return new class($stub(ClockInterface::class), $stub(ConfigInterface::class), $mock(MagicLinkRepositoryInterface::class)) {
        public MagicLinkService $service;

        public function __construct(
            public Stub&ClockInterface $clock,
            public Stub&ConfigInterface $config,
            public MockObject&MagicLinkRepositoryInterface $repo,
        ) {
            $this->service = new MagicLinkService($this->clock, $this->config, $this->repo);
        }
    };
}

\it('creates a new token and saves it to the repository', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);
    $app->config->method('get')->willReturn(30);

    $app->repo->expects($this->once())
        ->method('loadAll')
        ->willReturn([]);

    $app->repo->expects($this->once())
        ->method('saveAll')
        ->with($this->callback(function (array $links): bool {
            if (\count($links) !== 1) {
                return false;
            }
            $link = \reset($links);

            return $link instanceof MagicLink
                && $link->email->value === 'test@twokinds.de'
                && \strlen($link->token) === 64
                && \strlen($link->code) === 6
                && $link->expires->format('Y-m-d H:i:s') === '2026-08-07 10:30:00';
        }));

    $result = $app->service->createToken('test@twokinds.de');
    \expect($result)->toHaveKeys(['token', 'code']);
})->covers(MagicLinkService::class);

\it('peeks token and returns email if not expired', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new DateTimeImmutable('2026-08-07 10:00:00');
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

    $app->repo->expects($this->never())
        ->method('saveAll');

    $email = $app->service->peekToken('valid_token');
    \expect($email)->toBe('user@test.de');
})->covers(MagicLinkService::class);

\it('peek returns null if token is expired or invalid', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);

    $expiredLink = new MagicLink(
        'expired_token',
        new EmailAddress('user@test.de'),
        '123456',
        $now->modify('-5 minutes'),
    );

    // We call peekToken twice in the assertions below, so loadAll is called exactly twice.
    $app->repo->expects($this->exactly(2))
        ->method('loadAll')
        ->willReturn(['expired_token' => $expiredLink]);

    // Peeking should NEVER trigger a database save.
    $app->repo->expects($this->never())
        ->method('saveAll');

    \expect($app->service->peekToken('expired_token'))->toBeNull()
        ->and($app->service->peekToken('unknown_token'))->toBeNull();
})->covers(MagicLinkService::class);

\it('verifies a token, deletes it, and returns the email', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);

    $validLink = new MagicLink(
        'very_long_secret_token_123',
        new EmailAddress('user@test.de'),
        'ABCDEF',
        $now->modify('+10 minutes'),
    );

    $app->repo->expects($this->once())
        ->method('loadAll')
        ->willReturn(['very_long_secret_token_123' => $validLink]);

    $app->repo->expects($this->once())
        ->method('saveAll')
        ->with([]);

    $email = $app->service->verifyAny('very_long_secret_token_123');
    \expect($email)->toBe('user@test.de');
})->covers(MagicLinkService::class);

\it('verifies a token by short code, deletes it, and returns the email', function (): void {
    $app = \setupMagicLinkTest($this);
    $now = new DateTimeImmutable('2026-08-07 10:00:00');
    $app->clock->method('now')->willReturn($now);

    $validLink = new MagicLink(
        'very_long_secret_token_123',
        new EmailAddress('user@test.de'),
        'ABCDEF',
        $now->modify('+10 minutes'),
    );

    $app->repo->expects($this->once())
        ->method('loadAll')
        ->willReturn(['very_long_secret_token_123' => $validLink]);

    $app->repo->expects($this->once())
        ->method('saveAll')
        ->with([]);

    $email = $app->service->verifyAny('abcdef');
    \expect($email)->toBe('user@test.de');
})->covers(MagicLinkService::class);
