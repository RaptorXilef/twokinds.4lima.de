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
use App\Core\Service\MediaService;
use App\Core\ValueObject\CharacterId;

#[ActionRoute('api_save_single_character')]
final readonly class ApiSaveSingleCharacterAction implements ActionInterface
{
    public function __construct(
        private CharacterService $characterService,
        private CharacterRepositoryInterface $charRepo,
        private ConfigInterface $config,
        private MediaService $mediaService,
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

            // Text-Eingaben auslesen
            $mainPicUrl       = \trim((string) ($request->post['main_pic_url'] ?? ''));
            $swatchPicUrl     = \trim((string) ($request->post['swatch_pic_url'] ?? ''));
            $refSheetsUrlsRaw = \trim((string) ($request->post['ref_sheets_urls'] ?? ''));

            $fullName  = \trim((string) ($request->post['full_name'] ?? '')) ?: null;
            $gender    = \trim((string) ($request->post['gender'] ?? '')) ?: null;
            $age       = \trim((string) ($request->post['age'] ?? '')) ?: null;
            $species   = \trim((string) ($request->post['species'] ?? '')) ?: null;
            $languages = \trim((string) ($request->post['languages'] ?? '')) ?: null;

            // 1. Profilbild (Klein) - auf max 1000px skaliert
            if (isset($request->files['profile_image']) && $request->files['profile_image']['error'] === \UPLOAD_ERR_OK) {
                $file     = $request->files['profile_image'];
                $fileName = $safeName . '_profile.webp'; // IMMER webp!
                if ($this->mediaService->generateScaledImage($file['tmp_name'], $baseTargetDir . '/profiles/' . $fileName, 1000)) {
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
            } else {
                $picUrl = null;
            }

            // 2. Hauptbild (Groß) - auf max 2000px skaliert
            if (isset($request->files['main_pic']) && $request->files['main_pic']['error'] === \UPLOAD_ERR_OK) {
                $file     = $request->files['main_pic'];
                $fileName = $safeName . '_main.webp';
                if ($this->mediaService->generateScaledImage($file['tmp_name'], $baseTargetDir . '/main/' . $fileName, 2000)) {
                    $mainPic = $fileName;
                }
            } elseif (isset($request->post['main_pic_url'])) {
                $mainPic = $mainPicUrl !== '' ? \str_replace(' ', '_', $mainPicUrl) : null;
            }

            // 3. Farbpalette (Swatch) - auf max 1500px skaliert
            if (isset($request->files['swatch_pic']) && $request->files['swatch_pic']['error'] === \UPLOAD_ERR_OK) {
                $file     = $request->files['swatch_pic'];
                $fileName = $safeName . '_swatch.webp';
                if ($this->mediaService->generateScaledImage($file['tmp_name'], $baseTargetDir . '/swatches/' . $fileName, 1500)) {
                    $swatchPic = $fileName;
                }
            } elseif (isset($request->post['swatch_pic_url'])) {
                $swatchPic = $swatchPicUrl !== '' ? \str_replace(' ', '_', $swatchPicUrl) : null;
            }

            // 4. Reference Sheets (Array) - auf max 3000px skaliert
            if (isset($request->post['ref_sheets_urls'])) {
                $refSheets = [];
                if ($refSheetsUrlsRaw !== '') {
                    $refSheets = \array_values(\array_filter(\array_map(fn ($s) => \str_replace(' ', '_', \trim($s)), \explode(',', $refSheetsUrlsRaw))));
                }
            }
            if (isset($request->files['ref_sheets']) && \is_array($request->files['ref_sheets']['name'])) {
                $refFiles = $request->files['ref_sheets'];
                for ($i = 0; $i < \count($refFiles['name']); ++$i) {
                    if ($refFiles['error'][$i] === \UPLOAD_ERR_OK) {
                        $fileName = $safeName . '_ref_' . \uniqid() . '.webp';
                        if ($this->mediaService->generateScaledImage($refFiles['tmp_name'][$i], $baseTargetDir . '/refsheets/' . $fileName, 3000)) {
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
                fullName: $dto->fullName,
                altNames: $dto->altNames,
                gender: $dto->gender,
                age: $dto->age,
                rank: $dto->rank,
                species: $dto->species,
                subspecies: $dto->subspecies,
                languages: $dto->languages,
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
