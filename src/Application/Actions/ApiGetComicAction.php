<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\ValueObject\ComicId;

#[ActionRoute('api_get_comic')]
final readonly class ApiGetComicAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $idStr = \trim((string) ($request->get['id'] ?? ''));
        if ($idStr === '') {
            return JsonResponse::error('Keine Comic-ID angegeben.', 400);
        }

        try {
            $comic = $this->comicRepo->findById(new ComicId($idStr));
            if (! $comic) {
                return JsonResponse::error('Comic nicht gefunden.', 404);
            }

            // Für den Editor das Char-Array in einfache Strings umwandeln
            $charIds = \array_map(fn ($id) => $id->value, $comic->characterIds);

            $comicData = [
                'id'          => $comic->id->value,
                'type'        => $comic->type,
                'name'        => $comic->name,
                'transcript'  => $comic->transcript ?? '',
                'chapterId'   => $comic->chapterId ?? '',
                'characters'  => $charIds,
                'originalUrl' => $comic->originalUrl,
                'sketchUrl'   => $comic->sketchUrl,
            ];

            return JsonResponse::success(['comic' => $comicData]);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error('Ungültige Comic-ID.', 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
