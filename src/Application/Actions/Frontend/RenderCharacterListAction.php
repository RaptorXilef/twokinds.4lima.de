<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ViewActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;

#[ActionRoute('render_character_list')]
final readonly class RenderCharacterListAction implements ViewActionInterface
{
    public function __construct(
        private CharacterRepositoryInterface $charRepo,
        private CharacterGroupRepositoryInterface $groupRepo,
        private TemplateRenderer $renderer,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $characters = $this->charRepo->findAll();
        $groups     = $this->groupRepo->findAll();
        // Auf neuen template-Pfad geändert:
        $this->renderer->render('frontend/character_list', [
            'characters'      => $characters,
            'groups'          => $groups,
            'pageTitle'       => 'Charaktere',
            'siteDescription' => 'Lerne die Hauptcharaktere von TwoKinds kennen.',
        ]);

        return null;
    }
}
