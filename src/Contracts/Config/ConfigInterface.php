<?php

declare(strict_types=1);

namespace App\Contracts\Config;

/**
 * Interface für den globalen Konfigurations-Provider der Anwendung.
 *
 * Definiert Methoden für den Zugriff auf geschachtelte Konfigurations-Arrays,
 * die System-URLs, Testmodi sowie spezifische Preis- und Mail-Einstellungen.
 * Kontext: Abstraktionsschicht für das zentrale Konfigurationsmanagement.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
interface ConfigInterface
{
    /**
     * Ruft einen Konfigurationswert anhand seines Keys ab.
     * Unterstützt standardmäßig Fallbacks, falls der Key nicht existiert.
     *
     * @param string $key     Der Identifikations-Key (z.B. 'database.host').
     * @param mixed  $default Standard-Rückgabewert bei Nichtexistenz.
     *
     * @return mixed Der konfigurierte Wert oder das übergebene Default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Liefert die vollständig qualifizierte Basis-URL der Anwendung.
     *
     * @return string Die URL inklusive abschließendem Slash (z.B. 'https://domain.de/').
     */
    public function getBaseUrl(): string;

    /**
     * Prüft, ob sich die Anwendung aktuell im Testmodus befindet.
     *
     * @return bool True, wenn der Test- oder Sandboxmodus aktiv ist.
     */
    public function isTestMode(): bool;

    /**
     * Ermittelt den hinterlegten Preis für einen bestimmten Fahrzeug- oder Antragstyp.
     *
     * @param string $type Der Fahrzeugtyp-Key aus der Konfiguration.
     *
     * @return float Der Preis als numerischer Fließkommawert.
     */
    public function getPriceForType(string $type): float;

    /**
     * Gibt die gesammelten SMTP- und Mail-Server-Einstellungen zurück.
     *
     * @return array<string, mixed> Array mit Host, Port, Credentials und Empfänger-Routen.
     */
    public function getMailSettings(): array;

    /**
     * Baut den absoluten Systempfad für eine Speicher-Datei zusammen.
     *
     * @param  string $fileName Der Name der Zieldatei (z.B. 'users.json').
     * @return string Der vollständige, validierte Pfad.
     */
    public function getStoragePath(string $fileName): string;
}
