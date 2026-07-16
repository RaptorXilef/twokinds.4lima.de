<?php

declare(strict_types=1);

namespace App\Contracts\Event;

/**
 * Interface für den Event Dispatcher.
 * Leitet aufgetretene Ereignisse (Events) an die registrierten Lauscher (Listeners) weiter.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
interface EventDispatcherInterface
{
    /**
     * Verteilt ein Event an alle registrierten Listener.
     *
     * @param object $event Das Event-Objekt (z.B. PermitCreated).
     */
    public function dispatch(object $event): void;

    /**
     * Registriert einen neuen Listener für ein spezifisches Event.
     *
     * @param string   $eventClass Der vollqualifizierte Klassenname des Events.
     * @param callable $listener   Die Funktion/Klasse, die aufgerufen werden soll.
     */
    public function addListener(string $eventClass, callable $listener): void;
}
