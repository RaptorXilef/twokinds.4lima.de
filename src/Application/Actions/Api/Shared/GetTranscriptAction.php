<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Shared;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\ValueObject\ComicId;

#[Route('GET', '/api/get_transcript')]
final readonly class GetTranscriptAction implements ActionInterface
{
    public function __construct(private ComicRepositoryInterface $comicRepo)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        $idStr = \trim((string) ($request->get['id'] ?? ''));
        if ($idStr === '' || ! \preg_match('/^\d{8}[a-z]?$/i', $idStr)) {
            return JsonResponse::error('Ungültige oder fehlende Comic-ID.', 400);
        }

        $comic = $this->comicRepo->findById(new ComicId($idStr));
        if (! $comic) {
            return JsonResponse::error('Comic nicht gefunden.', 404);
        }

        return JsonResponse::success([
            'transcript' => $comic->transcript ?? '',
        ]);
    }
}
