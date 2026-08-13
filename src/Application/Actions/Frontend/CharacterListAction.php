<?php

declare(strict_types=1);

namespace App\Application\Actions\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\View\TemplateRenderer;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Core\Entity\Character;

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
        unset($request); // Interface Vorgabe

        $characters = $this->charRepo->findAll();
        $groups = $this->groupRepo->findAll();
        $filterData = $this->buildFilterData($characters);

        return $this->renderer->render(
            'pages/frontend/character_list',
            [
                'characters' => $characters,
                'groups' => $groups,
                'filterData' => $filterData,
                'pageTitle' => 'Charaktere',
                'siteDescription' => 'Lerne die Charaktere von TwoKinds kennen.',
            ],
        );
    }

    /**
     * @param array<array-key, Character> $characters
     *
     * @return array<string, array<int, string>>
     */
    private function buildFilterData(array $characters): array
    {
        $filterData = [
            'gender' => [],
            'rank' => [],
            'species' => [],
            'subspecies' => [],
            'languages' => [],
            'hairColor' => [],
            'eyeColor' => [],
            'furColor' => [],
        ];

        foreach ($characters as $char) {
            $this->appendCommaSeparatedFilters($filterData, $char);
        }

        // Keys extrahieren und natürlich alphabetisch sortieren
        foreach ($filterData as $key => $val) {
            $keys = \array_keys($val);
            \natcasesort($keys);
            $filterData[$key] = \array_values($keys);
        }

        return $filterData;
    }

    /**
     * @param array<string, array<string, bool>> &$filterData
     */
    private function appendCommaSeparatedFilters(array &$filterData, Character $char): void
    {
        $this->extractCommaItems($filterData['gender'], $char->gender);
        $this->extractCommaItems($filterData['species'], $char->species);
        $this->extractCommaItems($filterData['subspecies'], $char->subspecies);
        $this->extractCommaItems($filterData['rank'], $char->rank);
        $this->extractCommaItems($filterData['languages'], $char->languages);
        $this->extractCommaItems($filterData['hairColor'], $char->hairColor);
        $this->extractCommaItems($filterData['eyeColor'], $char->eyeColor);
        $this->extractCommaItems($filterData['furColor'], $char->furColor);
    }

    /**
     * @param array<string, bool> &$targetArray
     */
    private function extractCommaItems(array &$targetArray, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        foreach (\array_map(trim(...), \explode(',', $value)) as $item) {
            if ($item === '') {
                continue;
            }

            $targetArray[$item] = true;
        }
    }
}
