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

    public function getRegistry(): ActionRegistry
    {
        return $this->registry;
    }

    public function create(string $className): ActionInterface|ViewActionInterface|null
    {
        if (\class_exists($className)) {
            return $this->container->get($className);
        }

        return null;
    }
}
