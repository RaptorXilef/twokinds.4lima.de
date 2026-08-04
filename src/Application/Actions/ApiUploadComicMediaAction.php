<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Service\AuthService;
use App\Core\Service\MediaService;
use App\Core\Service\SiteGeneratorService;
use App\Core\ValueObject\ComicId;

#[ActionRoute('api_upload_comic_media')]
final readonly class ApiUploadComicMediaAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private MediaService $mediaService,
        private SiteGeneratorService $siteGenerator,
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
            $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comic';

            $hiresUploaded  = isset($files['upload_hires']) && $files['upload_hires']['error'] === \UPLOAD_ERR_OK;
            $lowresUploaded = isset($files['upload_lowres']) && $files['upload_lowres']['error'] === \UPLOAD_ERR_OK;

            if (! $hiresUploaded && ! $lowresUploaded) {
                return JsonResponse::error('Keine gültigen Bilder hochgeladen.', 400);
            }

            // Ordnerstruktur sicherstellen
            foreach (['hires', 'lowres', 'thumbnails', 'socialmedia'] as $sub) {
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
                $socialPath = "$targetDir/socialmedia/{$comicIdStr}.jpg";

                // Temporärer Auto-Crop (bis der User es manuell im Cropper ändert)
                // Dies erfordert, dass dein MediaService->generateSquareCrop theoretisch auch Rechtecke kann,
                // andernfalls bauen wir hier kurz einen simplen GD-Aufruf für den Auto-Zuschnitt.
                // Da wir aber ohnehin den manuellen Cropper nutzen, können wir es hier einfach vorerst auf Thumbnail-Basis belassen
                // oder einen statischen Zuschnitt machen.
                $this->autoGenerateSocialMediaJpg($baseProcessPath, $socialPath);
            }

            // Zeitstempel für RSS / Cachebusting aktualisieren (NUR WENN COMIC EXISTIERT!)
            if ($comic) {
                $updatedComic = new \App\Core\Entity\ComicPage(
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

    private function autoGenerateSocialMediaJpg(string $sourcePath, string $targetPath): void
    {
        // Ein schneller, dreckiger Auto-Center-Crop auf 1200x630, der später vom manuellen Cropper überschrieben werden kann.
        $img = @\imagecreatefromstring(\file_get_contents($sourcePath));
        if (! $img) {
            return;
        }

        $width  = \imagesx($img);
        $height = \imagesy($img);

        // Wir wollen ein Verhältnis von 1.91:1 (z.B. 1200 / 630)
        $targetRatio = 1200 / 630;
        $sourceRatio = $width / $height;

        $cropW = $width;
        $cropH = $height;

        if ($sourceRatio > $targetRatio) {
            // Bild ist zu breit
            $cropW = (int) ($height * $targetRatio);
        } else {
            // Bild ist zu hoch (z.B. eine Comicseite)
            $cropH = (int) ($width / $targetRatio);
        }

        $cropX = (int) (($width - $cropW) / 2);
        $cropY = (int) (($height - $cropH) / 2);

        $this->mediaService->generateManualCrop(
            $sourcePath,
            $targetPath,
            $cropX,
            $cropY,
            $cropW,
            $cropH,
            1200,
            630,
        );
    }
}
