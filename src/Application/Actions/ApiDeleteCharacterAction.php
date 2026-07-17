<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;

#[ActionRoute('api_delete_character')]
final readonly class ApiDeleteCharacterAction implements ActionInterface
{
    public function __construct(private CharacterService $characterService)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $id = \trim((string) ($request->post['character_id'] ?? ''));
            if ($id === '') {
                throw ValidationException::withMessage('Keine Charakter-ID zum Löschen angegeben.');
            }

            $this->characterService->deleteCharacter(new CharacterId($id));

            return JsonResponse::success(['message' => 'Charakter wurde erfolgreich gelöscht und aus allen Gruppen entfernt.']);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Löschen: ' . $e->getMessage(), 500);
        }
    }
}
