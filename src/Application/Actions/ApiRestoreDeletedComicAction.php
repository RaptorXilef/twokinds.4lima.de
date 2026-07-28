<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\ComicService;

#[ActionRoute('api_restore_deleted_comic')]
final readonly class ApiRestoreDeletedComicAction implements ActionInterface
{
    public function __construct(private ComicService $comicService)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
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
