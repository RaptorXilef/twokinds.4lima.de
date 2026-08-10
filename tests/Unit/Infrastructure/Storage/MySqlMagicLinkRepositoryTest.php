<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Core\Entity\MagicLink;
use App\Core\ValueObject\EmailAddress;
use App\Infrastructure\Storage\MySqlMagicLinkRepository;
use DateTimeImmutable;
use PDO;
use PDOStatement;

\uses()->group('infrastructure', 'storage', 'database');

\it('loads all magic links and parses dates', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('query')
        ->with($this->stringContains('SELECT * FROM `magic_links`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('fetchAll')
        ->with(PDO::FETCH_ASSOC)
        ->willReturn([
            ['token' => 'tok_1', 'email' => 'a@b.de', 'code' => 'ABC', 'expires' => '2026-08-10 12:00:00'],
        ]);

    $repo = new MySqlMagicLinkRepository($pdo);
    $links = $repo->loadAll();

    \expect($links)->toHaveCount(1)
        ->and($links)->toHaveKey('tok_1')
        ->and($links['tok_1']->email->value)->toBe('a@b.de')
        ->and($links['tok_1']->code)->toBe('ABC');
})->covers(MySqlMagicLinkRepository::class);

\it('saves all magic links wiping old records via transaction', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())->method('beginTransaction');
    $pdo->expects($this->once())->method('commit');
    $pdo->expects($this->once())->method('exec')->with($this->stringContains('DELETE FROM `magic_links`'));

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('REPLACE INTO `magic_links`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with([
            'token' => 'tok_2',
            'email' => 'b@c.de',
            'code' => 'XYZ',
            'expires' => '2026-08-10 12:00:00',
        ])
        ->willReturn(true);

    $repo = new MySqlMagicLinkRepository($pdo);
    $link = new MagicLink('tok_2', new EmailAddress('b@c.de'), 'XYZ', new DateTimeImmutable('2026-08-10 12:00:00'));

    $repo->saveAll(['tok_2' => $link]);
})->covers(MySqlMagicLinkRepository::class);

\it('deletes expired links and returns affected rows', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('DELETE FROM `magic_links` WHERE expires < NOW()'))
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->willReturn(true);
    $stmt->expects($this->once())->method('rowCount')->willReturn(4);

    $repo = new MySqlMagicLinkRepository($pdo);
    $deleted = $repo->deleteExpired();

    \expect($deleted)->toBe(4);
})->covers(MySqlMagicLinkRepository::class);
