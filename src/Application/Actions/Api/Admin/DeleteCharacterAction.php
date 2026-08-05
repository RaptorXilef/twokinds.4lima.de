<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\Route;
use App\Application\Attribute\RequiresAuth;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\AuthService;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;

#[Route('POST', '/api/delete_character')]
#[RequiresAuth]
final readonly class DeleteCharacterAction implements ActionInterface
{
    public function __construct(
        private CharacterService $characterService,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('characters.delete')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

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
