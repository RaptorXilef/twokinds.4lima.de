<?php

declare(strict_types=1);

namespace App\Application\Routing;

use App\Application\Contracts\ActionInterface;
use App\Application\Contracts\ViewActionInterface;
use App\Contracts\DependencyInjection\ContainerInterface;

final readonly class UniversalActionFactory
{
    public function __construct(
        private ActionRegistry $registry,
        private ContainerInterface $container,
    ) {
    }

    public function create(string $actionKey): ActionInterface|ViewActionInterface|null
    {
        $class = $this->registry->getActionClass($actionKey);

        if ($class !== null) {
            return $this->container->get($class);
        }

        return null;
    }
}
