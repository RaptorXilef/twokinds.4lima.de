<?php

declare(strict_types=1);

namespace App\Application\Routing;

use App\Application\Contracts\ActionInterface;
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

    public function create(string $className): ?ActionInterface
    {
        if (\class_exists($className)) {
            $instance = $this->container->get($className);
            if ($instance instanceof ActionInterface) {
                return $instance;
            }
        }

        return null;
    }
}
