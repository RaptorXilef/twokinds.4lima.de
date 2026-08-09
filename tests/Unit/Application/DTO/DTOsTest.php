<?php

declare(strict_types=1);

namespace Tests\Unit\Application\DTO;

use App\Application\DTO\ComicViewRequest;
use App\Application\DTO\SaveSingleCharacterRequest;
use App\Application\DTO\SaveSingleComicRequest;
use App\Application\DTO\SubmitReportRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;

\uses()->group('application', 'dto');

\it('ComicViewRequest throws if comic id is missing', function (): void {
    $req = new ServerRequest(get: []);
    ComicViewRequest::fromRequest($req);
})->throws(ValidationException::class, 'Keine Comic-ID angegeben')->covers(ComicViewRequest::class);

\it('ComicViewRequest succeeds with valid id', function (): void {
    $req = new ServerRequest(get: ['id' => '20260810']);
    $dto = ComicViewRequest::fromRequest($req);
    \expect($dto->comicId)->toBe('20260810');
})->covers(ComicViewRequest::class);

\it('SaveSingleCharacterRequest throws if name is empty', function (): void {
    $req = new ServerRequest(post: ['name' => '  ']);
    SaveSingleCharacterRequest::fromRequest($req);
})->throws(ValidationException::class, 'Der Name des Charakters darf nicht leer sein')->covers(SaveSingleCharacterRequest::class);

\it('SaveSingleComicRequest throws if id is invalid', function (): void {
    $req = new ServerRequest(post: ['comic_id' => 'invalid_id']);
    SaveSingleComicRequest::fromRequest($req);
})->throws(ValidationException::class, 'Ungültige oder fehlende Comic-ID')->covers(SaveSingleComicRequest::class);

\it('SubmitReportRequest triggers honeypot block', function (): void {
    $req = new ServerRequest(post: ['report_honeypot' => 'bot_filled_this']);
    SubmitReportRequest::fromRequest($req);
})->throws(ValidationException::class, 'HONEYPOT_TRIGGERED')->covers(SubmitReportRequest::class);

\it('SubmitReportRequest requires report type', function (): void {
    $req = new ServerRequest(post: ['report_type' => '']);
    SubmitReportRequest::fromRequest($req);
})->throws(ValidationException::class, 'Bitte wähle eine Fehler-Kategorie aus')->covers(SubmitReportRequest::class);

\it('SubmitReportRequest requires suggestion for transcripts', function (): void {
    $req = new ServerRequest(post: [
        'report_type' => 'transcript',
        'report_description' => '',
        'report_transcript_suggestion' => '',
    ]);
    SubmitReportRequest::fromRequest($req);
})->throws(ValidationException::class, 'Bitte gib eine Beschreibung oder einen Transkript-Vorschlag an')->covers(SubmitReportRequest::class);
