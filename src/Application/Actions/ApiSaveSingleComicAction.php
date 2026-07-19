<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SaveSingleComicRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\ComicService;
use App\Core\Service\MediaService;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;

#[ActionRoute('api_save_single_comic')]
final readonly class ApiSaveSingleComicAction implements ActionInterface
{
    public function __construct(
        private ComicService $comicService,
        private MediaService $mediaService,
        private ConfigInterface $config,
    ) {
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

            // Die clevere Sketch-Erkennung
            if ($sketchUrl !== '' && ! \preg_match('/\.[a-z0-9]{3,4}$/i', $sketchUrl)) {
                // Wenn es nicht schon auf _sketch endet, hängen wir es an
                if (! \str_ends_with($sketchUrl, '_sketch')) {
                    $sketchUrl .= '_sketch';
                }
                $sketchUrl .= '.' . $this->probeRemoteExtension($sketchUrl);
            }

            $charIds = [];
            foreach ($dto->characterIds as $cId) {
                try {
                    $charIds[] = new CharacterId((string) $cId);
                } catch (\InvalidArgumentException) {
                }
            }

            // --- BILD UPLOAD LOGIK ---
            $files       = $request->files;
            $targetDir   = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comic';
            $hasNewImage = false;

            $hiresUploaded  = isset($files['upload_hires']) && $files['upload_hires']['error'] === \UPLOAD_ERR_OK;
            $lowresUploaded = isset($files['upload_lowres']) && $files['upload_lowres']['error'] === \UPLOAD_ERR_OK;

            if ($hiresUploaded || $lowresUploaded) {
                $hasNewImage = true;

                // Ordner-Struktur sicherstellen
                foreach (['hires', 'lowres', 'thumbnails', 'socialmedia'] as $sub) {
                    $path = "$targetDir/$sub";
                    if (! \is_dir($path)) {
                        @\mkdir($path, 0o755, true);
                    }
                }

                $baseProcessPath = '';
                $hiresPath       = "$targetDir/hires/{$dto->id}.webp";

                // 1. Hires verarbeiten
                if ($hiresUploaded) {
                    $tmpHires = $files['upload_hires']['tmp_name'];
                    // Speichert Hires als WebP (mit Safenet-Breite von max 4000px)
                    $this->mediaService->generateScaledImage($tmpHires, $hiresPath, 4000);
                    $baseProcessPath = $hiresPath;
                }

                // 2. Lowres verarbeiten
                if ($lowresUploaded) {
                    $tmpLowres  = $files['upload_lowres']['tmp_name'];
                    $lowresPath = "$targetDir/lowres/{$dto->id}.webp";
                    // Manuelles Lowres einfach in WebP umwandeln
                    $this->mediaService->generateScaledImage($tmpLowres, $lowresPath, 1500);
                    $baseProcessPath = $lowresPath;
                } elseif ($hiresUploaded && \file_exists($hiresPath)) { // Extra Check zur Sicherheit
                    // Kein manuelles Lowres da -> Wir generieren es automatisch aus dem Hires!
                    $lowresPath = "$targetDir/lowres/{$dto->id}.webp";
                    // Skalieren auf max 1080px Breite
                    $this->mediaService->generateScaledImage($hiresPath, $lowresPath, 1080);
                    $baseProcessPath = $lowresPath;
                }

                // 3. Thumbnails & Social Media Crop generieren
                if ($baseProcessPath !== '') {
                    $this->mediaService->generateScaledImage($baseProcessPath, "$targetDir/thumbnails/{$dto->id}.webp", 200);
                    $this->mediaService->generateSquareCrop($baseProcessPath, "$targetDir/socialmedia/{$dto->id}.webp", 600);
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
                imageUpdatedAt: $hasNewImage ? \time() : null, // ComicService behält alten Timestamp, wenn null übergeben wird
            );

            $this->comicService->saveComic($comic);

            return JsonResponse::success(['message' => "Comic {$dto->id} erfolgreich gespeichert."]);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }

    // Pinged den Server blitzschnell an (HEAD request) und prüft ZWINGEND auf Content-Type: image/*
    private function probeRemoteExtension(string $baseUrl): string
    {
        // Fallback für lokale Server (wie XAMPP), bei denen cURL deaktiviert ist
        if (! \function_exists('curl_init')) {
            $context = \stream_context_create(['http' => ['method' => 'HEAD', 'timeout' => 2]]);
            foreach (['png', 'jpg', 'gif', 'jpeg', 'webp'] as $ext) {
                // HIER IST DER FIX FÜR DEN 500 ERROR (true statt 1)
                $headers = @\get_headers($baseUrl . '.' . $ext, true, $context);
                if ($headers !== false) {
                    $status = $headers[0] ?? '';
                    if (\str_contains($status, '200')) {
                        $contentType = $headers['Content-Type'] ?? ($headers['content-type'] ?? '');
                        if (\is_array($contentType)) {
                            $contentType = \end($contentType);
                        }
                        if (\is_string($contentType) && \str_starts_with($contentType, 'image/')) {
                            return $ext;
                        }
                    }
                }
            }

            return 'png'; // Fallback
        }

        // Standard-Weg mit cURL (schneller & ressourcenschonender)
        foreach (['png', 'jpg', 'gif', 'jpeg', 'webp'] as $ext) { // TODO ggf. ins Interface?
            $ch = \curl_init($baseUrl . '.' . $ext);
            \curl_setopt($ch, \CURLOPT_NOBODY, true); // Nur Header laden, spart Bandbreite
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 2);
            \curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) TwokindsAdminProbe/1.0');
            \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
            \curl_exec($ch);

            $code        = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
            $contentType = \curl_getinfo($ch, \CURLINFO_CONTENT_TYPE);
            \curl_close($ch);

            // Keenspot Custom 404s geben 200 + text/html zurück. Wir MÜSSEN prüfen, ob es ein Bild ist!
            if ($code === 200 && \is_string($contentType) && \str_starts_with($contentType, 'image/')) {
                return $ext;
            }
        }

        return 'png'; // Fallback auf das wahrscheinlichste Format
    }
}
