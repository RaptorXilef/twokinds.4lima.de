<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Service\MediaService;

#[ActionRoute('api_upload_media')]
final readonly class ApiUploadMediaAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private MediaService $mediaService,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('media.upload')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }
        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/profiles';

        if (! \is_dir($targetDir)) {
            @\mkdir($targetDir, 0o755, true);
        }

        if (! isset($_FILES['files']) || ! \is_array($_FILES['files']['name'])) {
            return JsonResponse::error('Keine Dateien hochgeladen.', 400);
        }

        $files          = $_FILES['files'];
        $count          = \count($files['name']);
        $processedCount = 0;

        for ($i = 0; $i < $count; ++$i) {
            if ($files['error'][$i] === \UPLOAD_ERR_OK) {
                $tmpName      = $files['tmp_name'][$i];
                $originalName = $files['name'][$i];

                // Dateinamen bereinigen
                $nameWithoutExt = \pathinfo($originalName, \PATHINFO_FILENAME);
                $safeName       = \preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nameWithoutExt);
                $targetPath     = $targetDir . '/' . $safeName . '.webp';

                // In WebP umwandeln (Max 1000px für Profile)
                if ($this->mediaService->generateScaledImage($tmpName, $targetPath, 1000)) {
                    ++$processedCount;
                }
            }
        }

        return JsonResponse::success(['message' => "{$processedCount} Bild(er) erfolgreich verarbeitet und hochgeladen!"]);
    }
}
