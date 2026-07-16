<?php

declare(strict_types=1);

namespace App\Bootstrap\Providers;

use App\Application\Listener\DeleteGroupImageListener;
use App\Contracts\DependencyInjection\ContainerInterface;
use App\Contracts\Event\EventDispatcherInterface;
use App\Core\Event\GroupDeletedEvent;
use App\Infrastructure\Event\EventDispatcher;

/**
 * Zentraler Event-Verteiler-Provider. Verknüpft alle Domain-Events mit ihren Listenern.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class EventServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // Wir binden nur das Interface
        $container->bind(EventDispatcherInterface::class, fn () => new EventDispatcher());

        $dispatcher = $container->get(EventDispatcherInterface::class);

        // Der Container baut die konkreten Listener vollautomatisch (Autowiring) zusammen!
        $dispatcher->addListener(GroupDeletedEvent::class, fn ($event) => $container->get(
            DeleteGroupImageListener::class,
        )->handle($event));
    }
}
