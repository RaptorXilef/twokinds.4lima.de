<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\AuthService;
use App\Core\Service\ComicService;
use App\Core\ValueObject\ComicId;

#[Route('POST', '/api/delete_comic')]
#[RequiresAuth]
final readonly class DeleteComicAction implements ActionInterface
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
        } catch (ValidationException | \InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Löschen: ' . $e->getMessage(), 500);
        }
    }
}
