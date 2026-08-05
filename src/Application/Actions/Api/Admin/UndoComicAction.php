<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\Route;
use App\Application\Attribute\RequiresAuth;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\AuthService;
use App\Core\Service\ComicService;
use App\Core\ValueObject\ComicId;

#[Route('POST', '/api/undo_comic')]
#[RequiresAuth]
final readonly class UndoComicAction implements ActionInterface
{
    public function __construct(
        private ComicService $comicService,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('comics.delete')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $idStr = \trim((string) ($request->post['comic_id'] ?? ''));
            if ($idStr === '') {
                throw new \InvalidArgumentException('Keine Comic-ID angegeben.');
            }

            $this->comicService->restoreLatestRevision(new ComicId($idStr));

            return JsonResponse::success(['message' => "Der Comic {$idStr} wurde auf die vorherige Version zurückgesetzt."]);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Rückgängigmachen: ' . $e->getMessage(), 500);
        }
    }
}
