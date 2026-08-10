<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Actions\Api\Shared;

use App\Application\Actions\Api\Shared\GetTranscriptAction;
use App\Application\Http\ServerRequest;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\ComicId;

\uses()->group('application', 'actions', 'api', 'shared');

\it('GetTranscriptAction returns 400 if id is missing', function (): void {
    $repo = $this->createStub(ComicRepositoryInterface::class);
    $action = new GetTranscriptAction($repo);
    \expect($action->execute(new ServerRequest())->statusCode)->toBe(400);
})->covers(GetTranscriptAction::class);

\it('GetTranscriptAction returns 404 if comic not found', function (): void {
    $repo = $this->createStub(ComicRepositoryInterface::class);
    $repo->method('findById')->willReturn(null);
    $action = new GetTranscriptAction($repo);
    \expect($action->execute(new ServerRequest(get: ['id' => '20260810']))->statusCode)->toBe(404);
})->covers(GetTranscriptAction::class);

\it('GetTranscriptAction returns 200 with transcript', function (): void {
    $repo = $this->createStub(ComicRepositoryInterface::class);
    $comic = new ComicPage(new ComicId('20260810'), 'Comicseite', 'Name', 'Test-Transcript', null, [], '', '');
    $repo->method('findById')->willReturn($comic);

    $action = new GetTranscriptAction($repo);
    $res = $action->execute(new ServerRequest(get: ['id' => '20260810']));

    \expect($res->statusCode)->toBe(200)
        ->and($res->data['transcript'])->toBe('Test-Transcript');
})->covers(GetTranscriptAction::class);
