<?php

declare(strict_types=1);

use App\Core\Entity\MagicLink;
use App\Core\Entity\MailLogEntry;
use App\Core\ValueObject\EmailAddress;

\uses()->group('core', 'entity');

\it('MagicLink isExpired returns true if expiration date is in the past', function (): void {
    $now = new \DateTimeImmutable('2026-08-10 12:00:00');
    $expiredTime = new \DateTimeImmutable('2026-08-10 11:50:00');
    $futureTime = new \DateTimeImmutable('2026-08-10 12:10:00');

    $expiredLink = new MagicLink('token', new EmailAddress('t@t.de'), '123', $expiredTime);
    $validLink = new MagicLink('token', new EmailAddress('t@t.de'), '123', $futureTime);

    \expect($expiredLink->isExpired($now))->toBeTrue()
        ->and($validLink->isExpired($now))->toBeFalse();
})->covers(MagicLink::class);

\it('MailLogEntry isSuccess returns true only on exact match', function (): void {
    $successLog = new MailLogEntry('1', new \DateTimeImmutable(), 'a@b.de', 'Sub', 'tpl', 'Erfolg', []);
    $errorLog = new MailLogEntry('2', new \DateTimeImmutable(), 'a@b.de', 'Sub', 'tpl', 'Fehler: Timeout', []);

    \expect($successLog->isSuccess())->toBeTrue()
        ->and($errorLog->isSuccess())->toBeFalse();
})->covers(MailLogEntry::class);
