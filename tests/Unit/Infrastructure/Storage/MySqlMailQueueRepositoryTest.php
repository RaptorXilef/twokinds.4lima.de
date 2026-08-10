<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\MailJob;
use App\Infrastructure\Storage\MySqlMailQueueRepository;
use DateTimeImmutable;
use PDO;
use PDOStatement;

\uses()->group('infrastructure', 'storage', 'database');

\it('enqueues a mail job using dynamic upsert', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $json = $this->createStub(JsonHelperInterface::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `mail_queue`'))
        ->willReturn($stmt);
    $stmt->expects($this->once())->method('execute')->willReturn(true);

    $repo = new MySqlMailQueueRepository($pdo, $json);
    $job = new MailJob('mq_1', 'a@b.de', 'Sub', 'tpl', [], 0, 10, new DateTimeImmutable());
    $repo->enqueue($job);
})->covers(MySqlMailQueueRepository::class);

\it('processBatch returns 0 if lock cannot be acquired', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmtLock = $this->createMock(PDOStatement::class);
    $json = $this->createStub(JsonHelperInterface::class);

    $pdo->expects($this->once())->method('query')->with("SELECT GET_LOCK('tk_mail_queue', 2)")->willReturn($stmtLock);
    $stmtLock->expects($this->once())->method('fetchColumn')->willReturn(0); // Lock failed

    $repo = new MySqlMailQueueRepository($pdo, $json);
    $count = $repo->processBatch(5, fn () => null);

    \expect($count)->toBe(0);
})->covers(MySqlMailQueueRepository::class);

\it('processBatch processes emails successfully and deletes them', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmtLock = $this->createMock(PDOStatement::class);
    $stmtUpdate = $this->createMock(PDOStatement::class);
    $stmtSelect = $this->createMock(PDOStatement::class);
    $stmtDel = $this->createMock(PDOStatement::class);
    $json = $this->createStub(JsonHelperInterface::class);

    // Get Lock
    $pdo->expects($this->exactly(2))
        ->method('query')
        ->willReturnMap([
            ["SELECT GET_LOCK('tk_mail_queue', 2)", $stmtLock],
            ["SELECT RELEASE_LOCK('tk_mail_queue')", $this->createStub(PDOStatement::class)],
        ]);
    $stmtLock->expects($this->once())->method('fetchColumn')->willReturn(1);

    $pdo->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnMap([
            ['UPDATE `mail_queue` SET attempts = attempts + 100 WHERE attempts < 3  ORDER BY priority DESC, created_at ASC LIMIT 5', $stmtUpdate],
            ['SELECT * FROM `mail_queue` WHERE attempts >= 100  ORDER BY priority DESC, created_at ASC', $stmtSelect],
            ['DELETE FROM `mail_queue` WHERE id = ?', $stmtDel],
        ]);

    $stmtUpdate->expects($this->once())->method('execute');
    $stmtSelect->expects($this->once())->method('execute');
    $stmtSelect->expects($this->once())->method('fetchAll')->willReturn([
        ['id' => 'mq_1', 'recipient' => 'a@b.de', 'subject' => 'S', 'template' => 't', 'data' => '{}', 'attempts' => 100],
    ]);

    $json->method('decode')->willReturn([]);
    $stmtDel->expects($this->once())->method('execute')->with(['mq_1']);

    $repo = new MySqlMailQueueRepository($pdo, $json);
    $processed = 0;

    $count = $repo->processBatch(5, function () use (&$processed): void {
        ++$processed;
    });

    \expect($count)->toBe(1)->and($processed)->toBe(1);
})->covers(MySqlMailQueueRepository::class);

\it('findById returns null if not found', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $json = $this->createStub(JsonHelperInterface::class);

    $pdo->expects($this->once())->method('prepare')->willReturn($stmt);
    $stmt->expects($this->once())->method('fetch')->willReturn(false);

    $repo = new MySqlMailQueueRepository($pdo, $json);
    \expect($repo->findById('mq_xxx'))->toBeNull();
})->covers(MySqlMailQueueRepository::class);

\it('findAllQueue fetches all items', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $json = $this->createStub(JsonHelperInterface::class);

    $pdo->expects($this->once())->method('query')->willReturn($stmt);
    $stmt->expects($this->once())->method('fetchAll')->willReturn([['id' => 'mq_1']]);

    $repo = new MySqlMailQueueRepository($pdo, $json);
    \expect($repo->findAllQueue())->toHaveCount(1);
})->covers(MySqlMailQueueRepository::class);
