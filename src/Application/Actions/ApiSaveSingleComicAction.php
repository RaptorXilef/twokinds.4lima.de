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

            // Auto-Detect für fehlende Dateiendungen bei Keenspot / Twokinds URLs
            $originalUrl = $dto->originalUrl;
            $sketchUrl   = $dto->sketchUrl;

            if ($originalUrl !== '' && ! \preg_match('/\.[a-z0-9]{3,4}$/i', $originalUrl)) {
                $originalUrl .= '.' . $this->probeRemoteExtension($originalUrl);
            }
            if ($sketchUrl !== '' && ! \preg_match('/\.[a-z0-9]{3,4}$/i', $sketchUrl)) {
                $sketchUrl .= '.' . $this->probeRemoteExtension($sketchUrl);
            }

            $charIds = [];
            foreach ($dto->characterIds as $cId) {
                try {
                    $charIds[] = new CharacterId((string) $cId);
                } catch (\InvalidArgumentException) {
                }
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
                imageUpdatedAt: null,
            );

            $this->comicService->saveComic($comic);

            return JsonResponse::success(['message' => "Comic {$dto->id} erfolgreich gespeichert."]);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }

    // Pinged den Server blitzschnell an (HEAD request), um zu sehen, welche Dateiendung existiert
    private function probeRemoteExtension(string $baseUrl): string
    {
        foreach (['jpg', 'png', 'gif', 'jpeg'] as $ext) { // TODO ggf. ins Interface
            $ch = \curl_init($baseUrl . '.' . $ext);
            \curl_setopt($ch, \CURLOPT_NOBODY, true); // Nur Header laden, spart Bandbreite
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 2);
            \curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) TwokindsAdminProbe/1.0');
            \curl_exec($ch);
            $code = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
            \curl_close($ch);

            if ($code === 200 || $code === 301 || $code === 302) {
                return $ext;
            }
        }

        return 'jpg'; // Fallback
    }
}
