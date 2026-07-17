<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SaveSingleComicRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Entity\ComicPage;
use App\Core\Service\ComicService;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;

#[ActionRoute('api_save_single_comic')]
final readonly class ApiSaveSingleComicAction implements ActionInterface
{
    public function __construct(private ComicService $comicService)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $dto = SaveSingleComicRequest::fromRequest($request);

            $charIds = [];
            foreach ($dto->characterIds as $cId) {
                try {
                    $charIds[] = new CharacterId((string) $cId);
                } catch (\InvalidArgumentException) {
                    continue;
                }
            }

            $comic = new ComicPage(
                id: new ComicId($dto->id),
                type: $dto->type,
                name: $dto->name,
                transcript: $dto->transcript,
                chapterId: $dto->chapterId,
                characterIds: $charIds,
                originalUrl: $dto->originalUrl,
                sketchUrl: $dto->sketchUrl,
                imageUpdatedAt: null, // Wird im Service intelligent bewahrt
            );

            $this->comicService->saveComic($comic);

            return JsonResponse::success(['message' => "Comic {$dto->id} erfolgreich gespeichert."]);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
}
