<?php

declare(strict_types=1);

namespace App\Contracts\Security;

/**
 * Interface für den Rate-Limiter zum Schutz vor Brute-Force-Angriffen.
 *
 * Definiert die Methoden zur Erfassung, Prüfung und Zurücksetzung von IP-basierten
 * Blockaden bei zu vielen Fehlversuchen (z.B. Logins).
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
interface RateLimiterInterface
{
    /**
     * Prüft, ob eine IP-Adresse aufgrund zu vieler Fehlversuche blockiert ist.
     *
     * @param string $ip Die zu prüfende IP-Adresse.
     *
     * @return bool True, wenn die IP derzeit blockiert wird.
     */
    public function isBlocked(string $ip): bool;

    /**
     * Protokolliert einen fehlgeschlagenen Zugriffsversuch für eine IP-Adresse.
     *
     * @param string $ip Die betroffene IP-Adresse.
     */
    public function recordFailedAttempt(string $ip): void;

    /**
     * Setzt die Fehlversuche für eine IP-Adresse zurück (z.B. nach erfolgreichem Login).
     *
     * @param string $ip Die betroffene IP-Adresse.
     */
    public function clearAttempts(string $ip): void;
}
