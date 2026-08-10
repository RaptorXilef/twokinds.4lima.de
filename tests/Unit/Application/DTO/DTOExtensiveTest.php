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

// ComicViewRequest
\it('ComicViewRequest throws on empty id', fn () => ComicViewRequest::fromRequest(new ServerRequest(get: ['id' => '  '])))->throws(ValidationException::class)->covers(ComicViewRequest::class);
\it('ComicViewRequest parses valid id', fn () => \expect(ComicViewRequest::fromRequest(new ServerRequest(get: ['id' => '20260810']))->comicId)->toBe('20260810'))->covers(ComicViewRequest::class);

// SaveSingleCharacterRequest
\it('SaveSingleCharacterRequest parses id fallback to new', fn () => \expect(SaveSingleCharacterRequest::fromRequest(new ServerRequest(post: ['name' => 'Trace']))->id)->toBe('new'))->covers(SaveSingleCharacterRequest::class);
\it('SaveSingleCharacterRequest parses all properties', function (): void {
    $req = new ServerRequest(post: [
        'id' => 'char_123', 'name' => 'Trace', 'pic_url' => 'trace.webp', 'description' => '<p>Hi</p>',
        'full_name' => 'Trace Legacy', 'gender' => 'Male', 'age' => '24', 'rank' => 'Templar', 'species' => 'Human',
    ]);
    $dto = SaveSingleCharacterRequest::fromRequest($req);
    \expect($dto->id)->toBe('char_123')->and($dto->species)->toBe('Human')->and($dto->picUrl)->toBe('trace.webp');
})->covers(SaveSingleCharacterRequest::class);

// SaveSingleComicRequest
\it('SaveSingleComicRequest parses type default', fn () => \expect(SaveSingleComicRequest::fromRequest(new ServerRequest(post: ['comic_id' => '20260810']))->type)->toBe('Comicseite'))->covers(SaveSingleComicRequest::class);
\it('SaveSingleComicRequest throws on invalid id format', fn () => SaveSingleComicRequest::fromRequest(new ServerRequest(post: ['comic_id' => 'abc'])))->throws(ValidationException::class)->covers(SaveSingleComicRequest::class);
\it('SaveSingleComicRequest appends keenspot url to original_url if missing http', function (): void {
    $req = new ServerRequest(post: ['comic_id' => '20260810', 'url_originalbild' => '123.jpg']);
    \expect(SaveSingleComicRequest::fromRequest($req)->originalUrl)->toBe('https://cdn.twokinds.keenspot.com/comics/123.jpg');
})->covers(SaveSingleComicRequest::class);
\it('SaveSingleComicRequest appends twokindscomic url to sketch_url if missing http', function (): void {
    $req = new ServerRequest(post: ['comic_id' => '20260810', 'url_originalsketch' => '123_sketch.jpg']);
    \expect(SaveSingleComicRequest::fromRequest($req)->sketchUrl)->toBe('https://twokindscomic.com/images/123_sketch.jpg');
})->covers(SaveSingleComicRequest::class);
\it('SaveSingleComicRequest leaves http original_url untouched', function (): void {
    $req = new ServerRequest(post: ['comic_id' => '20260810', 'url_originalbild' => 'https://external.com/123.jpg']);
    \expect(SaveSingleComicRequest::fromRequest($req)->originalUrl)->toBe('https://external.com/123.jpg');
})->covers(SaveSingleComicRequest::class);

// SubmitReportRequest
\it('SubmitReportRequest honeypot triggers block', fn () => SubmitReportRequest::fromRequest(new ServerRequest(post: ['report_honeypot' => 'bot'])))->throws(ValidationException::class, 'HONEYPOT_TRIGGERED')->covers(SubmitReportRequest::class);
\it('SubmitReportRequest missing type throws', fn () => SubmitReportRequest::fromRequest(new ServerRequest(post: ['report_type' => ''])))->throws(ValidationException::class, 'Bitte wähle eine Fehler-Kategorie')->covers(SubmitReportRequest::class);
\it('SubmitReportRequest valid transcript report requires suggestion', fn () => SubmitReportRequest::fromRequest(new ServerRequest(post: ['report_type' => 'transcript'])))->throws(ValidationException::class, 'Transkript-Vorschlag')->covers(SubmitReportRequest::class);
\it('SubmitReportRequest valid image report requires description', fn () => SubmitReportRequest::fromRequest(new ServerRequest(post: ['report_type' => 'image', 'report_description' => ''])))->throws(ValidationException::class, 'Fehlerbeschreibung')->covers(SubmitReportRequest::class);
\it('SubmitReportRequest sanitizes debug info', function (): void {
    $req = new ServerRequest(post: ['report_type' => 'image', 'report_description' => 'desc', 'report_debug_info' => '<script>alert(1)</script>']);
    \expect(SubmitReportRequest::fromRequest($req)->debugInfo)->toBe('alert(1)');
})->covers(SubmitReportRequest::class);
