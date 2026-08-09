<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SaveSingleComicRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\System\MediaServiceInterface;
use App\Contracts\System\RemoteResourceProberInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\AuthService;
use App\Core\Service\ComicService;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use InvalidArgumentException;
use Throwable;

#[Route('POST', '/api/save_single_comic')]
#[RequiresAuth]
final readonly class SaveSingleComicAction implements ActionInterface
{
    public function __construct(
        private ComicService $comicService,
        private MediaServiceInterface $mediaService,
        private AuthService $auth,
        private RemoteResourceProberInterface $prober,
        private ClockInterface $clock,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('comics.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $dto = SaveSingleComicRequest::fromRequest($request);

            // Auto-Detect für fehlende Dateiendungen bei Keenspot / Twokinds URLs
            $originalUrl = $dto->originalUrl;
            $sketchUrl = $dto->sketchUrl;

            // Dateiendungen auflösen (Dank curl_multi nun blitzschnell)
            // cURL Logik ersetzt durch das Interface
            if ($originalUrl !== '' && \preg_match('/\.[a-z0-9]{3,4}$/i', $originalUrl) !== 1) {
                $originalUrl .= '.' . $this->prober->probeExtension($originalUrl);
            }

            // Die clevere Sketch-Erkennung
            if ($sketchUrl !== '' && \preg_match('/\.[a-z0-9]{3,4}$/i', $sketchUrl) !== 1) {
                // Wenn es nicht schon auf _sketch endet, hängen wir es an
                if (!\str_ends_with($sketchUrl, '_sketch')) {
                    $sketchUrl .= '_sketch';
                }
                $sketchUrl .= '.' . $this->prober->probeExtension($sketchUrl);
            }

            $charIds = [];
            foreach ($dto->characterIds as $cId) {
                if (!\is_string($cId)) {
                    continue;
                }

                try {
                    $charIds[] = new CharacterId($cId);
                } catch (InvalidArgumentException) {
                }
            }

            $oldIdStrRaw = $request->post['old_comic_id'] ?? '';
            $oldIdStr = \is_scalar($oldIdStrRaw) ? \trim((string) $oldIdStrRaw) : '';
            $newIdStr = $dto->id;

            // --- DEEP RENAMING LOGIK ---
            if ($oldIdStr !== '' && $oldIdStr !== $newIdStr) {
                // 1. Datenbank kaskadierend umbenennen (Comics, Reports, Revisions)
                $this->comicService->renameComic(new ComicId($oldIdStr), new ComicId($newIdStr));
                // 2. Physische Dateien auf der Festplatte umbenennen
                $this->mediaService->renameComicMedia($oldIdStr, $newIdStr);
            }

            // --- BILD UPLOAD LOGIK ---
            $files = $request->files;
            $hasNewImage = false;

            $uploadHires = $files['upload_hires'] ?? null;
            $hiresUploaded = \is_array($uploadHires) && isset($uploadHires['error']) && $uploadHires['error'] === \UPLOAD_ERR_OK; // phpcs:ignore Generic.Files.LineLength.TooLong
            $tmpHires = $hiresUploaded && isset($uploadHires['tmp_name']) && \is_string($uploadHires['tmp_name']) ? $uploadHires['tmp_name'] : null; // phpcs:ignore Generic.Files.LineLength.TooLong

            $uploadLowres = $files['upload_lowres'] ?? null;
            $lowresUploaded = \is_array($uploadLowres) && isset($uploadLowres['error']) && $uploadLowres['error'] === \UPLOAD_ERR_OK; // phpcs:ignore Generic.Files.LineLength.TooLong
            $tmpLowres = $lowresUploaded && isset($uploadLowres['tmp_name']) && \is_string($uploadLowres['tmp_name']) ? $uploadLowres['tmp_name'] : null; // phpcs:ignore Generic.Files.LineLength.TooLong

            if ($hiresUploaded || $lowresUploaded) {
                $hasNewImage = true;

                // Gesamte Datei-System und Skalierungslogik an Infrastruktur delegiert!
                $this->mediaService->processAndStoreComicMedia($dto->id, $tmpHires, $tmpLowres);
            }

            $userIds = [];
            foreach ($dto->userIds as $uid) {
                if (!\is_string($uid)) {
                    continue;
                }

                $userIds[] = $uid;
            }

            $comic = new ComicPage(
                id: new ComicId($dto->id),
                type: $dto->type,
                name: $dto->name,
                transcript: $dto->transcript,
                chapterId: $dto->chapterId,
                characterIds: $charIds,
                originalUrl: $originalUrl,
                sketchUrl: $sketchUrl,
                userIds: $userIds,
                imageUpdatedAt: $hasNewImage ? $this->clock->now()->getTimestamp() : null,
            );

            $this->comicService->saveComic($comic);

            return JsonResponse::success(['message' => "Comic {$dto->id} erfolgreich gespeichert."]);
        } catch (ValidationException|InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
}
