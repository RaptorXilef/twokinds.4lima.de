<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;

#[Route('GET', '/charaktere')]
final readonly class CharacterListAction implements ActionInterface
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
        $groups = $this->groupRepo->findAll();

        // Dynamische Filter-Optionen generieren
        $filterData = [
            'gender' => [],
            'age' => [],
            'rank' => [],
            'species' => [],
            'subspecies' => [],
            'languages' => [],
        ];

        foreach ($characters as $c) {
            if ($c->gender !== null && $c->gender !== '') {
                $filterData['gender'][$c->gender] = true;
            }
            if ($c->age !== null && $c->age !== '') {
                $filterData['age'][$c->age] = true;
            }
            if ($c->species !== null && $c->species !== '') {
                $filterData['species'][$c->species] = true;
            }
            if ($c->subspecies !== null && $c->subspecies !== '') {
                $filterData['subspecies'][$c->subspecies] = true;
            }

            // Komma-separierte Listen (Ränge, Sprachen) aufteilen
            if ($c->rank !== null && $c->rank !== '') {
                foreach (\array_map(trim(...), \explode(',', $c->rank)) as $r) {
                    if ($r === '') {
                        continue;
                    }

                    $filterData['rank'][$r] = true;
                }
            }
            if ($c->languages === null) {
                continue;
            }
            if ($c->languages === '') {
                continue;
            }

            foreach (\array_map(trim(...), \explode(',', $c->languages)) as $l) {
                if ($l === '') {
                    continue;
                }

                $filterData['languages'][$l] = true;
            }
        }

        // Keys extrahieren und natürlich alphabetisch sortieren
        foreach ($filterData as $key => $val) {
            $keys = \array_keys($val);
            \natcasesort($keys);
            $filterData[$key] = \array_values($keys);
        }

        return $this->renderer->render('pages/frontend/character_list', [
            'characters' => $characters,
            'groups' => $groups,
            'filterData' => $filterData, // Neue Variable ans Template übergeben
            'pageTitle' => 'Charaktere',
            'siteDescription' => 'Lerne die Hauptcharaktere von TwoKinds kennen.',
        ]);
    }
}
