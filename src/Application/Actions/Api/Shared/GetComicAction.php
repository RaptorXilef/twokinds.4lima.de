<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Shared;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\AuthService;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;

#[Route('GET', '/api/get_comic')]
#[RequiresAuth]
final readonly class GetComicAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('comics.edit') && ! $this->auth->hasPermission('reports.resolve')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $idRaw = $request->get['id'] ?? '';
        $idStr = \is_scalar($idRaw) ? \trim((string) $idRaw) : '';

        if ($idStr === '') {
            return JsonResponse::error('Keine Comic-ID angegeben.', 400);
        }

        try {
            $comic = $this->comicRepo->findById(new ComicId($idStr));
            if (! $comic instanceof ComicPage) {
                return JsonResponse::error('Comic nicht gefunden.', 404);
            }

            // Für den Editor das Char-Array in einfache Strings umwandeln
            $charIds = \array_map(fn (CharacterId $id): string => $id->value, $comic->characterIds);

            $comicData = [
                'id'          => $comic->id->value,
                'type'        => $comic->type,
                'name'        => $comic->name,
                'transcript'  => $comic->transcript ?? '',
                'chapterId'   => $comic->chapterId ?? '',
                'characters'  => $charIds,
                'users'       => $comic->userIds, // Keine ?? [] mehr nötig, da array<int, string> garantiert
                'originalUrl' => $comic->originalUrl,
                'sketchUrl'   => $comic->sketchUrl,
            ];

            return JsonResponse::success(['comic' => $comicData]);
        } catch (\InvalidArgumentException) {
            return JsonResponse::error('Ungültige Comic-ID.', 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
