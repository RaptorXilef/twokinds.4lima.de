<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\System\ImageStorageInterface;

final readonly class MediaService
{
    public function __construct(
        private ImageStorageInterface $imageStorage,
    ) {
    }

    /**
     * Verarbeitet einen neuen Comic-Upload und entscheidet anhand der Schwellenwerte,
     * ob er im HiRes- oder LowRes-Ordner landet.
     */
    public function processComicUpload(string $comicId, array $fileData, int $thresholdWidth, int $thresholdHeight): array
    {
        if ($fileData['error'] !== \UPLOAD_ERR_OK) {
            throw new \RuntimeException('Fehler beim Upload der Datei.');
        }

        $info = \getimagesize($fileData['tmp_name']);
        if (! $info) {
            throw new \RuntimeException('Ungültige Bilddatei.');
        }

        $width  = $info[0];
        $height = $info[1];

        $folder = ($width >= $thresholdWidth && $height >= $thresholdHeight) ? 'comic/hires' : 'comic/lowres';

        if (! $this->imageStorage->uploadImage($folder, $comicId, $fileData)) {
            throw new \RuntimeException("Fehler beim Verschieben des Bildes in $folder.");
        }

        return [
            'folder' => $folder,
            'width'  => $width,
            'height' => $height,
        ];
    }
}
