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
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Core\Entity\Character;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;
use InvalidArgumentException;
use Throwable;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[Route('POST', '/api/save_single_character')]
#[RequiresAuth]
final readonly class SaveSingleCharacterAction implements ActionInterface
{
    public function __construct(
        private CharacterService $characterService,
        private CharacterRepositoryInterface $charRepo,
        private MediaServiceInterface $mediaService,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('characters.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $dto = SaveSingleCharacterRequest::fromRequest($request);

            $charIdStr = $dto->id;
            if ($charIdStr === 'new') {
                $charIdStr = 'char_' . \str_pad((string) \random_int(1, 9999), 4, '0', \STR_PAD_LEFT);
            }

            $existing = $this->charRepo->findById(new CharacterId($charIdStr));
            $safeName = \pathinfo(Sanitizer::slugify($dto->name), \PATHINFO_FILENAME);
            if ($safeName === '') {
                $safeName = $charIdStr;
            }

            $media = $this->resolveMediaUrls($request, $dto, $existing, $safeName);

            $character = new Character(
                id: new CharacterId($charIdStr),
                name: $dto->name,
                picUrl: $media['picUrl'],
                description: $dto->description,
                fullName: $dto->fullName,
                altNames: $dto->altNames,
                gender: $dto->gender,
                age: $dto->age,
                rank: $dto->rank,
                species: $dto->species,
                subspecies: $dto->subspecies,
                languages: $dto->languages,
                mainPic: $media['mainPic'],
                swatchPic: $media['swatchPic'],
                refSheets: $media['refSheets'],
            );

            $this->characterService->saveCharacter($character);

            $msg = "Charakter '{$dto->name}' erfolgreich gespeichert.";
            if ($media['warnings'] !== []) {
                $msg .= "<br><br><strong style='color:#856404;'><i class='fa-solid fa-triangle-exclamation'></i> Warnungen:</strong><br>- " // phpcs:ignore Generic.Files.LineLength.TooLong
                    . \implode('<br>- ', $media['warnings']);
            }

            return JsonResponse::success([
                'message' => $msg,
                'character_id' => $charIdStr,
            ]);
        } catch (ValidationException|InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPER
    // =========================================================================

    /**
     * @return array{
     *     picUrl: ?string,
     *     mainPic: ?string,
     *     swatchPic: ?string,
     *     refSheets: array<int, string>,
     *     warnings: array<int, string>
     * }
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function resolveMediaUrls(ServerRequest $request, SaveSingleCharacterRequest $dto, ?Character $existing, string $safeName): array // phpcs:ignore Generic.Files.LineLength.TooLong
    {
        /** @var array{profile: ?string, main: ?string, swatch: ?string, refs: array<int, string>, warnings: array<int, string>} $processedMedia */
        $processedMedia = $this->mediaService->processCharacterImages($safeName, $request->files);

        // --- Profilbild ---
        $picUrl = $dto->picUrl !== '' ? $dto->picUrl : null;
        if ($picUrl !== null) {
            $picUrl = \str_replace(' ', '_', $picUrl);
            // Simple Endungs-Ergänzung falls nicht vorhanden
            if (\preg_match('/\.[a-z0-9]+$/i', $picUrl) !== 1) {
                $picUrl .= '.webp'; // Standard-Fallback annehmen
            }
        }
        if ($processedMedia['profile'] !== null) {
            $picUrl = $processedMedia['profile'];
        }

        // --- Hauptbild ---
        $mainPicUrlRaw = $request->post['main_pic_url'] ?? '';
        $mainPicUrl = \is_string($mainPicUrlRaw) ? \trim($mainPicUrlRaw) : '';
        $mainPic = $mainPicUrl !== '' ? \str_replace(' ', '_', $mainPicUrl) : $existing?->mainPic;
        if ($processedMedia['main'] !== null) {
            $mainPic = $processedMedia['main'];
        }

        // --- Swatch ---
        $swatchPicUrlRaw = $request->post['swatch_pic_url'] ?? '';
        $swatchPicUrl = \is_string($swatchPicUrlRaw) ? \trim($swatchPicUrlRaw) : '';
        $swatchPic = $swatchPicUrl !== '' ? \str_replace(' ', '_', $swatchPicUrl) : $existing?->swatchPic;
        if ($processedMedia['swatch'] !== null) {
            $swatchPic = $processedMedia['swatch'];
        }

        // --- Ref Sheets ---
        $refSheets = $existing->refSheets ?? [];
        if (isset($request->post['ref_sheets_urls'])) {
            $refSheetsUrlsRaw = $request->post['ref_sheets_urls'];
            $refSheetsUrlsStr = \is_string($refSheetsUrlsRaw) ? \trim($refSheetsUrlsRaw) : '';

            if ($refSheetsUrlsStr !== '') {
                $refSheets = \array_values(\array_filter(
                    \array_map(
                        fn ($sheetUrl): string => \str_replace(' ', '_', \trim($sheetUrl)),
                        \explode(',', $refSheetsUrlsStr),
                    ),
                    fn ($val): bool => $val !== '',
                ));
            }
        }

        if (isset($processedMedia['refs']) && \is_array($processedMedia['refs']) && $processedMedia['refs'] !== []) {
            $refSheets = \array_merge($refSheets, $processedMedia['refs']);
        }

        return [
            'picUrl' => $picUrl,
            'mainPic' => $mainPic,
            'swatchPic' => $swatchPic,
            'refSheets' => $refSheets,
            'warnings' => $processedMedia['warnings'],
        ];
    }
}
