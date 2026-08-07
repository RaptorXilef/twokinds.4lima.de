<?php

declare(strict_types=1);

use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\IpAddress;
use App\Core\ValueObject\ReportId;
use App\Core\ValueObject\Username;

// --- CharacterId ---
\it('accepts a valid CharacterId', function (): void {
    $id = new CharacterId('char_0016');
    \expect($id->value)->toBe('char_0016')
        ->and((string) $id)->toBe('char_0016');
})->covers(CharacterId::class);

\it('rejects an invalid CharacterId', function (): void {
    new CharacterId('karen_0016');
})->throws(\InvalidArgumentException::class)->covers(CharacterId::class);

// --- ComicId ---
\it('accepts a valid ComicId and lowercases it', function (): void {
    $id1 = new ComicId('20260807');
    $id2 = new ComicId('20260807A'); // Test mit Suffix

    \expect($id1->value)->toBe('20260807')
        ->and($id2->value)->toBe('20260807a'); // Wurde lowercase gemacht
})->covers(ComicId::class);

\it('rejects an invalid ComicId', function (): void {
    new ComicId('2026-08-07'); // Bindestriche sind nicht erlaubt
})->throws(\InvalidArgumentException::class)->covers(ComicId::class);

// --- EmailAddress ---
\it('accepts a valid EmailAddress and lowercases it', function (): void {
    $email = new EmailAddress('  USER@Test.de  ');
    \expect($email->value)->toBe('user@test.de'); // Wurde getrimmt und lowercase
})->covers(EmailAddress::class);

\it('rejects an invalid EmailAddress', function (): void {
    new EmailAddress('keine-email');
})->throws(\InvalidArgumentException::class)->covers(EmailAddress::class);

// --- IpAddress ---
\it('accepts a valid IPv4, IPv6 and 0.0.0.0', function (): void {
    \expect((new IpAddress('127.0.0.1'))->value)->toBe('127.0.0.1')
        ->and((new IpAddress('::1'))->value)->toBe('::1')
        ->and((new IpAddress('0.0.0.0'))->value)->toBe('0.0.0.0');
})->covers(IpAddress::class);

\it('rejects an invalid IP address', function (): void {
    new IpAddress('999.999.999.999');
})->throws(\InvalidArgumentException::class)->covers(IpAddress::class);

// --- ReportId ---
\it('accepts a valid ReportId', function (): void {
    $id = new ReportId('report_64d1f2');
    \expect($id->value)->toBe('report_64d1f2');
})->covers(ReportId::class);

\it('rejects an invalid ReportId', function (): void {
    new ReportId('rpt_123');
})->throws(\InvalidArgumentException::class)->covers(ReportId::class);

// --- Username ---
\it('accepts a valid Username and strips tags', function (): void {
    $user = new Username('  Super-User.123_  ');
    \expect($user->value)->toBe('Super-User.123_');
})->covers(Username::class);

\it('rejects a Username that is too short', function (): void {
    new Username('Ab');
})->throws(\InvalidArgumentException::class, 'mindestens 3 Zeichen')->covers(Username::class);

\it('rejects a Username with invalid characters', function (): void {
    new Username('Hacker<script>');
})->throws(\InvalidArgumentException::class, 'enthält ungültige Zeichen')->covers(Username::class);
