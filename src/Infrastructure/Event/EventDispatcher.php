<?php

declare(strict_types=1);

namespace App\Infrastructure\Event;

use App\Contracts\Event\EventDispatcherInterface;

/**
 * Magiefreier, synchroner Event Dispatcher.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, callable[]>
     */
    private array $listeners = [];

    public function addListener(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $eventClass = \get_class($event);

        // Prüfen, ob jemand auf dieses spezielle Event lauscht
        foreach ($this->listeners[$eventClass] ?? [] as $listener) {
            $listener($event);
        }
    }
}
