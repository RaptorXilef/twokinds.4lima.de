<?php

declare(strict_types=1);

namespace App\Bootstrap\Providers;

use App\Contracts\DependencyInjection\ContainerInterface;
use App\Contracts\Event\EventDispatcherInterface;
use App\Infrastructure\Event\EventDispatcher;

/**
 * Zentraler Event-Verteiler-Provider. Verknüpft alle Domain-Events mit ihren Listenern.
 */
final class EventServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        // Bindet den konkreten Event-Dispatcher an das Interface
        $container->bind(EventDispatcherInterface::class, fn (): EventDispatcher => new EventDispatcher());

        /*
         * Sobald wir Events haben (z.B. ComicPublishedEvent),
         * binden wir die Listener hier an den Dispatcher:
         *
         * $dispatcher = $container->get(EventDispatcherInterface::class);
         * $dispatcher->addListener(
         *     ComicPublishedEvent::class,
         *     fn($event) => $container->get(PingDiscordListener::class)->handle($event)
         * );
         */
    }
}
