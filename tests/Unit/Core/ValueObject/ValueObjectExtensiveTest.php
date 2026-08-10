<?php

declare(strict_types=1);

namespace Tests\Unit\Core\ValueObject;

use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\IpAddress;
use App\Core\ValueObject\ReportId;
use App\Core\ValueObject\Username;
use InvalidArgumentException;

\uses()->group('core', 'value-object');

// IpAddress
\it('IpAddress accepts valid IPv4', fn () => \expect((new IpAddress('192.168.178.1'))->value)->toBe('192.168.178.1'))->covers(IpAddress::class);
\it('IpAddress accepts valid IPv6', fn () => \expect((new IpAddress('2001:0db8:85a3:0000:0000:8a2e:0370:7334'))->value)->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334'))->covers(IpAddress::class);
\it('IpAddress accepts 0.0.0.0 fallback', fn () => \expect((new IpAddress('0.0.0.0'))->value)->toBe('0.0.0.0'))->covers(IpAddress::class);
\it('IpAddress trims whitespace', fn () => \expect((new IpAddress('  10.0.0.1  '))->value)->toBe('10.0.0.1'))->covers(IpAddress::class);
\it('IpAddress rejects invalid IP', fn () => new IpAddress('256.256.256.256'))->throws(InvalidArgumentException::class)->covers(IpAddress::class);

// EmailAddress
\it('EmailAddress lowercases and trims', fn () => \expect((new EmailAddress(' Test@DOMAIN.com '))->value)->toBe('test@domain.com'))->covers(EmailAddress::class);
\it('EmailAddress rejects empty string', fn () => new EmailAddress('   '))->throws(InvalidArgumentException::class, 'darf nicht leer sein')->covers(EmailAddress::class);
\it('EmailAddress rejects missing @', fn () => new EmailAddress('testdomain.com'))->throws(InvalidArgumentException::class, 'Ungültiges E-Mail-Format')->covers(EmailAddress::class);

// CharacterId
\it('CharacterId accepts char_0000', fn () => \expect((new CharacterId('char_0000'))->value)->toBe('char_0000'))->covers(CharacterId::class);
\it('CharacterId accepts char_999999', fn () => \expect((new CharacterId('char_999999'))->value)->toBe('char_999999'))->covers(CharacterId::class);
\it('CharacterId rejects char_abc', fn () => new CharacterId('char_abc'))->throws(InvalidArgumentException::class)->covers(CharacterId::class);
\it('CharacterId rejects missing prefix', fn () => new CharacterId('0016'))->throws(InvalidArgumentException::class)->covers(CharacterId::class);

// ComicId
\it('ComicId accepts 8 digits', fn () => \expect((new ComicId('20260810'))->value)->toBe('20260810'))->covers(ComicId::class);
\it('ComicId accepts 8 digits and one char', fn () => \expect((new ComicId('20260810a'))->value)->toBe('20260810a'))->covers(ComicId::class);
\it('ComicId lowercases the suffix char', fn () => \expect((new ComicId('20260810Z'))->value)->toBe('20260810z'))->covers(ComicId::class);
\it('ComicId rejects 7 digits', fn () => new ComicId('2026081'))->throws(InvalidArgumentException::class)->covers(ComicId::class);
\it('ComicId rejects 9 digits', fn () => new ComicId('202608105'))->throws(InvalidArgumentException::class)->covers(ComicId::class);
\it('ComicId rejects symbols', fn () => new ComicId('20260810_'))->throws(InvalidArgumentException::class)->covers(ComicId::class);

// ReportId
\it('ReportId accepts report_ prefix', fn () => \expect((new ReportId('report_abc123'))->value)->toBe('report_abc123'))->covers(ReportId::class);
\it('ReportId rejects rpt_ prefix', fn () => new ReportId('rpt_abc123'))->throws(InvalidArgumentException::class)->covers(ReportId::class);

// Username
\it('Username trims and strips tags', fn () => \expect((new Username(' <b>Felix</b> '))->value)->toBe('Felix'))->covers(Username::class);
\it('Username allows dots dashes underscores', fn () => \expect((new Username('Felix.May-wald_123'))->value)->toBe('Felix.May-wald_123'))->covers(Username::class);
\it('Username rejects length less than 3', fn () => new Username('Fe'))->throws(InvalidArgumentException::class, 'mindestens 3 Zeichen')->covers(Username::class);
\it('Username rejects length more than 50', fn () => new Username(\str_repeat('a', 51)))->throws(InvalidArgumentException::class, 'maximal 50 Zeichen')->covers(Username::class);
\it('Username rejects special symbols like @ or !', fn () => new Username('Felix!@#'))->throws(InvalidArgumentException::class, 'enthält ungültige Zeichen')->covers(Username::class);
