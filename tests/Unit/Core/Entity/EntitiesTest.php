<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Entity;

use App\Core\Entity\CharacterGroup;
use App\Core\Entity\ComicPage;
use App\Core\Entity\Report;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\ReportId;
use DateTimeImmutable;
use InvalidArgumentException;

\uses()->group('core', 'entity');

\it('CharacterGroup accepts valid CharacterIds', function (): void {
    $group = new CharacterGroup('Heroes', [new CharacterId('char_0001')]);
    \expect($group->name)->toBe('Heroes')
        ->and($group->characterIds)->toHaveCount(1);
})->covers(CharacterGroup::class);

\it('CharacterGroup rejects invalid items in array', function (): void {
    new CharacterGroup('Heroes', ['char_0001']); // array of strings instead of CharacterId
})->throws(InvalidArgumentException::class)->covers(CharacterGroup::class);

\it('ComicPage accepts valid CharacterIds', function (): void {
    $comic = new ComicPage(
        new ComicId('20260810'),
        'Comicseite',
        'Title',
        null,
        null,
        [new CharacterId('char_0001')],
        '',
        '',
    );
    \expect($comic->characterIds)->toHaveCount(1);
})->covers(ComicPage::class);

\it('ComicPage rejects invalid items in array', function (): void {
    new ComicPage(
        new ComicId('20260810'),
        'Comicseite',
        'Title',
        null,
        null,
        ['char_0001'], // invalid!
        '',
        '',
    );
})->throws(InvalidArgumentException::class)->covers(ComicPage::class);

\it('Report rejects invalid status', function (): void {
    new Report(
        new ReportId('report_1'),
        null,
        null,
        new DateTimeImmutable(),
        'INVALID_STATUS', // throws
        'hash',
        'Name',
        false,
        'image',
        null,
        'desc',
        '',
        '',
        '{}',
    );
})->throws(InvalidArgumentException::class, 'Ungültiger Report-Status')->covers(Report::class);

\it('Report rejects invalid type', function (): void {
    new Report(
        new ReportId('report_1'),
        null,
        null,
        new DateTimeImmutable(),
        'open',
        'hash',
        'Name',
        false,
        'INVALID_TYPE', // throws
        null,
        'desc',
        '',
        '',
        '{}',
    );
})->throws(InvalidArgumentException::class, 'Ungültiger Report-Typ')->covers(Report::class);
