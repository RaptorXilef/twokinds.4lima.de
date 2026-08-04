<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\AuthService;
use App\Core\Service\ComicService;
use App\Core\ValueObject\ComicId;

#[ActionRoute('api_delete_comic')]
final readonly class ApiDeleteComicAction implements ActionInterface
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
            $id = \trim((string) ($request->post['comic_id'] ?? ''));
            if ($id === '') {
                throw ValidationException::withMessage('Keine Comic-ID zum Löschen angegeben.');
            }

            $this->comicService->deleteComic(new ComicId($id));

            return JsonResponse::success(['message' => "Comic $id wurde erfolgreich gelöscht."]);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Löschen: ' . $e->getMessage(), 500);
        }
    }
}
