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
use App\Core\Entity\Character;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;

#[ActionRoute('api_save_single_character')]
final readonly class ApiSaveSingleCharacterAction implements ActionInterface
{
    public function __construct(
        private CharacterService $characterService,
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

            $picUrl = $dto->picUrl;
            // Leerzeichen durch Unterstriche ersetzen
            if ($picUrl !== null && $picUrl !== '') {
                $picUrl = \str_replace(' ', '_', $picUrl);
            }
            $warnings  = [];
            $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/profiles';

            // 1. Priorität: Wurde ein Bild hochgeladen?
            if (isset($request->files['profile_image']) && $request->files['profile_image']['error'] === \UPLOAD_ERR_OK) {
                if (! \is_dir($targetDir)) {
                    @\mkdir($targetDir, 0o755, true);
                }

                $file = $request->files['profile_image'];
                $ext  = \strtolower(\pathinfo($file['name'], \PATHINFO_EXTENSION));
                if ($ext === '') {
                    $ext = 'webp';
                }

                // Wir speichern das Bild als "charid_timestamp.ext" um Caching-Probleme zu vermeiden
                $fileName = $charIdStr . '_' . \time() . '.' . $ext;

                if (\move_uploaded_file($file['tmp_name'], $targetDir . '/' . $fileName)) {
                    $picUrl = $fileName;
                } else {
                    $warnings[] = 'Das hochgeladene Bild konnte nicht auf dem Server gespeichert werden.';
                }
            }
            // 2. Priorität: Intelligente Endungs-Erkennung für das Textfeld
            elseif ($picUrl !== null && $picUrl !== '') {
                // Hat der String KEINE Dateiendung? (z.B. "trace" statt "trace.jpg")
                if (! \preg_match('/\.[a-z0-9]+$/i', $picUrl)) {
                    $found = false;
                    foreach (['webp', 'png', 'jpg', 'jpeg', 'gif'] as $ext) {
                        if (\file_exists($targetDir . '/' . $picUrl . '.' . $ext)) {
                            $picUrl .= '.' . $ext;
                            $found = true;

                            break;
                        }
                    }
                    if (! $found) {
                        $warnings[] = "Warnung: Es wurde kein Bild mit dem Namen '{$picUrl}' (webp, png, jpg...) auf dem Server gefunden.";
                    }
                }
            }

            $character = new Character(
                id: new CharacterId($charIdStr),
                name: $dto->name,
                picUrl: $picUrl,
                description: $dto->description,
                altNames: $dto->altNames,
                rank: $dto->rank,
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
