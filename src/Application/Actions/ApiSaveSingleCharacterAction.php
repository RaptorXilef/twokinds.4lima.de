<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SaveSingleCharacterRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Core\Entity\Character;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;

#[ActionRoute('api_save_single_character')]
final readonly class ApiSaveSingleCharacterAction implements ActionInterface
{
    public function __construct(
        private CharacterService $characterService,
        private CharacterRepositoryInterface $charRepo,
        private ConfigInterface $config,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $dto = SaveSingleCharacterRequest::fromRequest($request);

            $charIdStr = $dto->id;

            // Wenn der Client "new" sendet, generieren wir eine einzigartige ID.
            if ($charIdStr === 'new') {
                // In einer echten Umgebung idealerweise über eine Factory oder DB Sequence,
                // hier zur Sicherheit ein zufälliger Suffix nach dem Format 'char_XXXX'
                $charIdStr = 'char_' . \str_pad((string) \random_int(1, 9999), 4, '0', \STR_PAD_LEFT);
            }

            // Existierenden Charakter holen, um alte Bildpfade zu erhalten
            $existing = $this->charRepo->findById(new CharacterId($charIdStr));

            $picUrl    = $dto->picUrl;
            $mainPic   = $existing ? $existing->mainPic : null;
            $swatchPic = $existing ? $existing->swatchPic : null;
            $refSheets = $existing ? $existing->refSheets : [];

            $warnings      = [];
            $baseTargetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters';

            // Verzeichnisse sicherstellen
            foreach (['profiles', 'main', 'swatches', 'refsheets'] as $sub) {
                $dir = $baseTargetDir . '/' . $sub;
                if (! \is_dir($dir)) {
                    @\mkdir($dir, 0o755, true);
                }
            }

            $safeName = \preg_replace('/[^a-zA-Z0-9_-]/', '', \str_replace(' ', '_', $dto->name));
            if ($safeName === '') {
                $safeName = $charIdStr;
            }

            // 1. Profilbild (Klein)
            if (isset($request->files['profile_image']) && $request->files['profile_image']['error'] === \UPLOAD_ERR_OK) {
                $file     = $request->files['profile_image'];
                $ext      = \strtolower(\pathinfo($file['name'], \PATHINFO_EXTENSION)) ?: 'webp';
                $fileName = $safeName . '_profile.' . $ext;
                if (\move_uploaded_file($file['tmp_name'], $baseTargetDir . '/profiles/' . $fileName)) {
                    $picUrl = $fileName;
                }
            } elseif ($picUrl !== null && $picUrl !== '') {
                $picUrl = \str_replace(' ', '_', $picUrl);
                if (! \preg_match('/\.[a-z0-9]+$/i', $picUrl)) {
                    foreach (['webp', 'png', 'jpg', 'jpeg', 'gif'] as $ext) {
                        if (\file_exists($baseTargetDir . '/profiles/' . $picUrl . '.' . $ext)) {
                            $picUrl .= '.' . $ext;

                            break;
                        }
                    }
                }
            }

            // 2. Hauptbild (Groß)
            if (isset($request->files['main_pic']) && $request->files['main_pic']['error'] === \UPLOAD_ERR_OK) {
                $file     = $request->files['main_pic'];
                $ext      = \strtolower(\pathinfo($file['name'], \PATHINFO_EXTENSION)) ?: 'webp';
                $fileName = $safeName . '_main.' . $ext;
                if (\move_uploaded_file($file['tmp_name'], $baseTargetDir . '/main/' . $fileName)) {
                    $mainPic = $fileName;
                }
            }

            // 3. Farbpalette (Swatch)
            if (isset($request->files['swatch_pic']) && $request->files['swatch_pic']['error'] === \UPLOAD_ERR_OK) {
                $file     = $request->files['swatch_pic'];
                $ext      = \strtolower(\pathinfo($file['name'], \PATHINFO_EXTENSION)) ?: 'webp';
                $fileName = $safeName . '_swatch.' . $ext;
                if (\move_uploaded_file($file['tmp_name'], $baseTargetDir . '/swatches/' . $fileName)) {
                    $swatchPic = $fileName;
                }
            }

            // 4. Reference Sheets (Array)
            if (isset($request->files['ref_sheets']) && \is_array($request->files['ref_sheets']['name'])) {
                $refFiles = $request->files['ref_sheets'];
                for ($i = 0; $i < \count($refFiles['name']); ++$i) {
                    if ($refFiles['error'][$i] === \UPLOAD_ERR_OK) {
                        $ext      = \strtolower(\pathinfo($refFiles['name'][$i], \PATHINFO_EXTENSION)) ?: 'webp';
                        $fileName = $safeName . '_ref_' . \uniqid() . '.' . $ext;
                        if (\move_uploaded_file($refFiles['tmp_name'][$i], $baseTargetDir . '/refsheets/' . $fileName)) {
                            // Einfach an das bestehende Array anhängen
                            $refSheets[] = $fileName;
                        }
                    }
                }
            }

            $character = new Character(
                id: new CharacterId($charIdStr),
                name: $dto->name,
                picUrl: $picUrl === '' ? null : $picUrl,
                description: $dto->description,
                altNames: $dto->altNames,
                rank: $dto->rank,
                mainPic: $mainPic,
                swatchPic: $swatchPic,
                refSheets: $refSheets,
            );

            $this->characterService->saveCharacter($character);

            $msg = "Charakter '{$dto->name}' erfolgreich gespeichert.";
            if (! empty($warnings)) {
                $msg .= ' ' . \implode(' ', $warnings);
            }

            return JsonResponse::success([
                'message'      => $msg,
                'character_id' => $charIdStr,
            ]);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
}
