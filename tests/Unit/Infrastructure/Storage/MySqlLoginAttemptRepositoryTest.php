<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Core\Entity\LoginAttempt;
use App\Core\ValueObject\IpAddress;
use App\Infrastructure\Storage\MySqlLoginAttemptRepository;
use DateTimeImmutable;
use PDO;
use PDOStatement;

\uses()->group('infrastructure', 'storage', 'database');

\it('finds login attempt by ip and handles unknown ips safely', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('SELECT ip_address'))
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->with(['unknown']);

    $stmt->expects($this->once())
        ->method('fetch')
        ->with(PDO::FETCH_ASSOC)
        ->willReturn([
            'ip_address' => 'unknown',
            'attempts' => 3,
            'last_attempt' => '2026-08-10 12:00:00',
        ]);

    $repo = new MySqlLoginAttemptRepository($pdo);
    $attempt = $repo->findByIp('unknown');

    \expect($attempt)->toBeInstanceOf(LoginAttempt::class)
        ->and($attempt->ipAddress->value)->toBe('0.0.0.0') // 'unknown' falls back to 0.0.0.0 in repository
        ->and($attempt->attempts)->toBe(3);
})->covers(MySqlLoginAttemptRepository::class);

\it('returns null if no login attempt exists for ip', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->method('prepare')->willReturn($stmt);
    $stmt->method('fetch')->willReturn(false);

    $repo = new MySqlLoginAttemptRepository($pdo);
    \expect($repo->findByIp('192.168.0.1'))->toBeNull();
})->covers(MySqlLoginAttemptRepository::class);

\it('saves login attempt using dynamic upsert', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `login_attempts`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->willReturn(true);

    $repo = new MySqlLoginAttemptRepository($pdo);
    $attempt = new LoginAttempt(new IpAddress('10.0.0.1'), 1, new DateTimeImmutable());

    $repo->save($attempt);
})->covers(MySqlLoginAttemptRepository::class);

\it('deletes old login attempts by minutes', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('DELETE FROM `login_attempts` WHERE last_attempt < DATE_SUB'))
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->with([15])->willReturn(true);

    $repo = new MySqlLoginAttemptRepository($pdo);
    $repo->deleteOlderThan(15);
})->covers(MySqlLoginAttemptRepository::class);
