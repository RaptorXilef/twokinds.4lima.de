<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Entity;

use App\Core\Entity\Bookmark;
use App\Core\Entity\Chapter;
use App\Core\Entity\Character;
use App\Core\Entity\CharacterGroup;
use App\Core\Entity\ComicPage;
use App\Core\Entity\LoginAttempt;
use App\Core\Entity\MailJob;
use App\Core\Entity\MailLogEntry;
use App\Core\Entity\Report;
use App\Core\Entity\Role;
use App\Core\Entity\User;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\IpAddress;
use App\Core\ValueObject\ReportId;
use App\Core\ValueObject\Username;
use DateTimeImmutable;

\uses()->group('core', 'entity');

\it('Role initializes correctly', function (): void {
    $role = new Role('admin', 'Administrator', ['*']);
    \expect($role->id)->toBe('admin')->and($role->name)->toBe('Administrator')->and($role->permissions)->toBe(['*']);
})->covers(Role::class);

\it('User initializes with correct defaults', function (): void {
    $user = new User('usr_1', new Username('Test'), new EmailAddress('a@b.de'), 'hash', 'user', new DateTimeImmutable());
    \expect($user->wantsNewsletter)->toBeFalse()
        ->and($user->publicBookmarks)->toBeFalse()
        ->and($user->avatarUrl)->toBeNull();
})->covers(User::class);

\it('Bookmark initializes', function (): void {
    $bm = new Bookmark('usr_1', '20260810', new DateTimeImmutable());
    \expect($bm->userId)->toBe('usr_1')->and($bm->comicId)->toBe('20260810');
})->covers(Bookmark::class);

\it('Chapter initializes', function (): void {
    $chap = new Chapter('1', 'Prolog', 'Intro');
    \expect($chap->id)->toBe('1')->and($chap->title)->toBe('Prolog')->and($chap->description)->toBe('Intro');
})->covers(Chapter::class);

\it('Character initializes full', function (): void {
    $char = new Character(new CharacterId('char_0001'), 'Trace', 'trace.webp', 'Templar', 'Trace Legacy', 'Master', 'Male', '24', 'Mage', 'Human', null, 'English', null, null, null, 'main.webp', 'swatch.webp', ['ref1.webp'], false);
    \expect($char->name)->toBe('Trace')->and($char->refSheets)->toHaveCount(1)->and($char->gender)->toBe('Male');
})->covers(Character::class);

\it('Character calculates Keidran age equivalent using math curve', function (): void {
    $char = new Character(new CharacterId('char_0002'), 'Flora', null, null, null, null, null, '11', 'Mage', 'Keidran', 'Tiger', null, null, null, null, null, null, [], false);
    // 0.12*11^2 + 0.8*11 + 2 = 25.32 -> 25
    \expect($char->getKeidranAgeEquivalent())->toBe('25');

    $charSpan = new Character(new CharacterId('char_0003'), 'Flora', null, null, null, null, null, '11-16', 'Mage', 'Keidran', 'Tiger', null, null, null, null, null, null, [], false);
    // 0.12*16^2 + 0.8*16 + 2 = 45.52 -> 46
    \expect($charSpan->getKeidranAgeEquivalent())->toBe('25-46');
})->covers(Character::class);

\it('CharacterGroup validates empty CharacterIds', function (): void {
    $group = new CharacterGroup('Empty', []);
    \expect($group->characterIds)->toBeEmpty()->and($group->manualSort)->toBeFalse();
})->covers(CharacterGroup::class);

\it('ComicPage initializes correctly', function (): void {
    $comic = new ComicPage(new ComicId('20260810'), 'Comicseite', 'Name', 'Transcript', '1', [new CharacterId('char_0001')], 'url1', 'url2', ['usr_1'], 1234567890);
    \expect($comic->id->value)->toBe('20260810')->and($comic->userIds)->toBe(['usr_1'])->and($comic->imageUpdatedAt)->toBe(1234567890);
})->covers(ComicPage::class);

\it('LoginAttempt initializes', function (): void {
    $attempt = new LoginAttempt(new IpAddress('127.0.0.1'), 5, new DateTimeImmutable());
    \expect($attempt->attempts)->toBe(5)->and($attempt->ipAddress->value)->toBe('127.0.0.1');
})->covers(LoginAttempt::class);

\it('MailJob initializes', function (): void {
    $job = new MailJob('mq_1', 'a@b.de', 'Subj', 'tpl', ['foo' => 'bar'], 0, 10, new DateTimeImmutable());
    \expect($job->priority)->toBe(10)->and($job->data['foo'])->toBe('bar');
})->covers(MailJob::class);

\it('MailLogEntry initializes', function (): void {
    $log = new MailLogEntry('ml_1', new DateTimeImmutable(), 'a@b.de', 'Subj', 'tpl', 'Erfolg', []);
    \expect($log->isSuccess())->toBeTrue();
})->covers(MailLogEntry::class);

\it('Report validates properties', function (): void {
    $report = new Report(new ReportId('report_1'), new ComicId('20260810'), 'usr_1', new DateTimeImmutable(), 'open', 'hash', 'Trace', true, 'image', null, 'Desc', '', '', '{}');
    \expect($report->wantsCredit)->toBeTrue()->and($report->type)->toBe('image')->and($report->status)->toBe('open');
})->covers(Report::class);
