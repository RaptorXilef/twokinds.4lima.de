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

#[Route('POST', '/api/restore_deleted_comic')]
#[RequiresAuth]
final readonly class RestoreDeletedComicAction implements ActionInterface
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
            $restored = $this->comicService->restoreDeletedComic();

            return JsonResponse::success([
                'message' => "Der gelöschte Comic '{$restored['id']}' wurde erfolgreich wiederhergestellt!",
            ]);
        } catch (\DomainException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
