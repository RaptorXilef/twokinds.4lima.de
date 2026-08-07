<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SaveSingleCharacterRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Core\Entity\Character;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;

#[Route('POST', '/api/save_single_character')]
#[RequiresAuth]
final readonly class SaveSingleCharacterAction implements ActionInterface
{
    public function __construct(
        private CharacterService $characterService,
        private CharacterRepositoryInterface $charRepo,
        private ConfigInterface $config,
        private MediaServiceInterface $mediaService,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('characters.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

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

            $warnings = [];
            $safeName = \pathinfo(Sanitizer::slugify($dto->name), \PATHINFO_FILENAME);
            if ($safeName === '') {
                $safeName = $charIdStr;
            }

            // Alles an die Infrastruktur delegieren!
            $processedMedia = $this->mediaService->processCharacterImages($safeName, $request->files);
            $warnings       = $processedMedia['warnings'];

            // Profilbild zuweisen
            if ($processedMedia['profile'] !== null) {
                $picUrl = $processedMedia['profile'];
            } elseif ($picUrl !== null && $picUrl !== '') {
                $picUrl = \str_replace(' ', '_', $picUrl);
                // Simple Endungs-Ergänzung falls nicht vorhanden
                if (! \preg_match('/\.[a-z0-9]+$/i', $picUrl)) {
                    $picUrl .= '.webp'; // Standard-Fallback annehmen
                }
            } else {
                $picUrl = null;
            }

            // Hauptbild
            if ($processedMedia['main'] !== null) {
                $mainPic = $processedMedia['main'];
            } elseif (isset($request->post['main_pic_url'])) {
                $mainPicUrl = \trim((string) ($request->post['main_pic_url'] ?? ''));
                $mainPic    = $mainPicUrl !== '' ? \str_replace(' ', '_', $mainPicUrl) : null;
            }

            // Swatch
            if ($processedMedia['swatch'] !== null) {
                $swatchPic = $processedMedia['swatch'];
            } elseif (isset($request->post['swatch_pic_url'])) {
                $swatchPicUrl = \trim((string) ($request->post['swatch_pic_url'] ?? ''));
                $swatchPic    = $swatchPicUrl !== '' ? \str_replace(' ', '_', $swatchPicUrl) : null;
            }

            // Manuelle URLs für Reference Sheets
            if (isset($request->post['ref_sheets_urls'])) {
                $refSheetsUrlsRaw = \trim((string) ($request->post['ref_sheets_urls'] ?? ''));
                $refSheets        = [];
                if ($refSheetsUrlsRaw !== '') {
                    $refSheets = \array_values(\array_filter(\array_map(fn ($s): string => \str_replace(' ', '_', \trim($s)), \explode(',', $refSheetsUrlsRaw))));
                }
            }

            // Neu hochgeladene Refs hinzufügen
            if (! empty($processedMedia['refs'])) {
                $refSheets = \array_merge($refSheets, $processedMedia['refs']);
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
            if ($warnings !== []) {
                $msg .= "<br><br><strong style='color:#856404;'><i class='fa-solid fa-triangle-exclamation'></i> Warnungen:</strong><br>- " . \implode('<br>- ', $warnings);
            }

            return JsonResponse::success([
                'message'      => $msg,
                'character_id' => $charIdStr,
            ]);
        } catch (ValidationException | \InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
}
