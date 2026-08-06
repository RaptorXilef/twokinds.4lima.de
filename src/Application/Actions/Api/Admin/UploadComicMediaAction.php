<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\AuthService;
use App\Core\ValueObject\ComicId;

#[Route('POST', '/api/upload_comic_media')]
#[RequiresAuth]
final readonly class UploadComicMediaAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private MediaServiceInterface $mediaService,
        private SiteGeneratorInterface $siteGenerator,
        private ConfigInterface $config,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('comics.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $comicIdStr = \trim((string) ($request->post['comic_id'] ?? ''));
            if ($comicIdStr === '') {
                return JsonResponse::error('Keine Comic-ID übermittelt.', 400);
            }

            $comicId = new ComicId($comicIdStr);
            $comic   = $this->comicRepo->findById($comicId);
            $force   = (bool) ($request->post['force'] ?? false);

            // Wenn Comic nicht existiert UND kein force-Flag gesetzt ist, brich ab und frag nach
            if (! $comic && ! $force) {
                return JsonResponse::sendPayload([
                    'success' => false,
                    'error'   => 'COMIC_NOT_FOUND',
                    'message' => "Comic {$comicIdStr} existiert nicht.",
                ], 404);
            }

            $files     = $request->files;
            $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comics';

            $hiresUploaded  = isset($files['upload_hires']) && $files['upload_hires']['error'] === \UPLOAD_ERR_OK;
            $lowresUploaded = isset($files['upload_lowres']) && $files['upload_lowres']['error'] === \UPLOAD_ERR_OK;

            if (! $hiresUploaded && ! $lowresUploaded) {
                return JsonResponse::error('Keine gültigen Bilder hochgeladen.', 400);
            }

            // Ordnerstruktur sicherstellen
            foreach (['hires', 'lowres', 'thumbnails', 'social'] as $sub) {
                $path = "$targetDir/$sub";
                if (! \is_dir($path)) {
                    @\mkdir($path, 0o755, true);
                }
            }

            $baseProcessPath = '';
            $hiresPath       = "$targetDir/hires/{$comicIdStr}.webp";

            if ($hiresUploaded) {
                $this->mediaService->generateScaledImage($files['upload_hires']['tmp_name'], $hiresPath, 4000);
                $baseProcessPath = $hiresPath;
            }

            if ($lowresUploaded) {
                $lowresPath = "$targetDir/lowres/{$comicIdStr}.webp";
                $this->mediaService->generateScaledImage($files['upload_lowres']['tmp_name'], $lowresPath, 1500);
                $baseProcessPath = $lowresPath;
            } elseif ($hiresUploaded && \file_exists($hiresPath)) {
                $lowresPath = "$targetDir/lowres/{$comicIdStr}.webp";
                $this->mediaService->generateScaledImage($hiresPath, $lowresPath, 1080);
                $baseProcessPath = $lowresPath;
            }

            if ($baseProcessPath !== '') {
                $this->mediaService->generateScaledImage($baseProcessPath, "$targetDir/thumbnails/{$comicIdStr}.webp", 200);

                // SOCIAL MEDIA: Als .jpg speichern.
                // Wir nutzen hier vorübergehend noch einen simplen Center-Crop für den automatischen Massenupload,
                // aber mit dem neuen 1200x630 (1.91:1) Breitbild-Verhältnis!
                $socialPath = "$targetDir/social/{$comicIdStr}.jpg";

                // Temporärer Auto-Crop (bis der User es manuell im Cropper ändert)
                // Dies erfordert, dass dein MediaService->generateSquareCrop theoretisch auch Rechtecke kann,
                // andernfalls bauen wir hier kurz einen simplen GD-Aufruf für den Auto-Zuschnitt.
                // Da wir aber ohnehin den manuellen Cropper nutzen, können wir es hier einfach vorerst auf Thumbnail-Basis belassen
                // oder einen statischen Zuschnitt machen.
                $this->mediaService->autoGenerateSocialMediaJpg($baseProcessPath, $socialPath);
            }

            // Zeitstempel für RSS / Cachebusting aktualisieren (NUR WENN COMIC EXISTIERT!)
            if ($comic) {
                $updatedComic = new ComicPage(
                    id: $comic->id,
                    type: $comic->type,
                    name: $comic->name,
                    transcript: $comic->transcript,
                    chapterId: $comic->chapterId,
                    characterIds: $comic->characterIds,
                    originalUrl: $comic->originalUrl,
                    sketchUrl: $comic->sketchUrl,
                    imageUpdatedAt: \time(),
                );
                $this->comicRepo->save($updatedComic);
                $this->siteGenerator->generateAll();
            }

            return JsonResponse::success(['message' => "Medien für {$comicIdStr} erfolgreich verarbeitet!"]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
