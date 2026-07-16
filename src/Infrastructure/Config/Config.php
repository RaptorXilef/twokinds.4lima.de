<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Contracts\Config\ConfigInterface;

/**
 * Konfigurations-Infrastruktur-Provider der Anwendung.
 * Kapselt das aggregierte Einstellungs-Array und berechnet bei Bedarf dynamisch
 * die korrekten HTTPS-Basis-URLs sowie Tarifpreise für Fahrzeugtypen.
 * Kontext: Technische Implementierung des Config-Dienstes.
 *
 * Zentrales Konfigurations-Objekt.
 *
 * Verwaltet alle Anwendungseinstellungen und ermöglicht den Zugriff auf
 * Mail-Templates und Provider-Daten.
 *
 * @immutable
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class Config implements ConfigInterface
{
    /**
     * @param array<string, mixed> $settings Das rohe, zusammengeführte Konfigurations-Array.
     */
    public function __construct(
        private array $settings,
    ) {
    }

    /**
     * Holt einen Wert direkt aus dem Einstellungs-Array.
     *
     * (Der wichtigste universelle Getter)
     *
     * @param string $key     Der exakte Array-Schlüssel.
     * @param mixed  $default Fallback bei Nichtexistenz.
     *
     * @return mixed Der gespeicherte Wert.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $val = $this->settings[$key] ?? $default;

        // TODO Auto-Heal auch für andere Speichereinstellungen ergänzen
        // Auto-Heal: Ergänzt fehlende storage_config Einträge (z.B. nach Updates),
        // damit das System nicht abstürzt, wenn die JSON-Datei nicht aktuell ist.
        if ($key === 'storage_config' && \is_array($val) && ! isset($val['audit_logs'])) {
            $val['audit_logs'] = [
                'type'  => $val['permits']['type'] ?? 'json', // Nimmt klugerweise das Format der Permits
                'table' => 'audit_logs',
                'file'  => 'audit_logs.json',
            ];
        }

        return $val;
    }

    /**
     * Liefert den Mail- und SMTP-Spezifischen Konfigurationsblock.
     *
     * @return array<string, mixed>
     */
    public function getMailSettings(): array
    {
        // Wir casten auf array, damit PHPStan sicher ist, dass wir das Interface erfüllen
        return (array) $this->get('mail', []);
    }

    /**
     * Gibt an, ob das System im Test- oder Sandbox-Modus operiert.
     */
    public function isTestMode(): bool
    {
        return (bool) $this->get('test_mode', true);
    }

    /**
     * Gibt die Standard-Dauer für Genehmigungen zurück.
     */
    public function getPermitDuration(): int
    {
        // Standardmäßig 5 Tage, falls nichts in der config.php steht
        return (int) $this->get('permit_duration', 5);
    }

    /**
     * Ermittelt den Preis für einen Fahrzeugtyp. Falls kein expliziter Preis
     * hinterlegt ist, wird der Preis des ersten konfigurierten Fahrzeugtyps als Fallback genutzt.
     *
     * @param string $type Der Typ-Schlüssel (z.B. 'pkw', 'lkw').
     *
     * @return float Der Brutto-Preis.
     */
    public function getPriceForType(string $type): float
    {
        $vConfig     = $this->get('vehicle_types', []);
        $defaultType = empty($vConfig) ? 'pkw' : \array_key_first($vConfig);

        // Wir schauen in das Preise-Mapping (pkw)
        $prices = $this->get('prices', []);

        // Fallback-Logik: Wenn für den Typ kein Preis da ist, nimm den Standardpreis (z.B. PKW)
        return (float) ($prices[$type] ?? ($prices[$defaultType] ?? 0.00));
    }

    /**
     * Ermittelt die Basis-URL der Installation.
     * Falls 'base_url' in der Konfiguration leer ist, wird die URL automatisch
     * anhand der $_SERVER-Umgebungsvariablen (Protokoll, Host, Script-Name) dynamisch generiert.
     *
     * (Komplexester Getter mit Logik ans Ende)
     *
     * @return string Bereinigte URL mit abschließendem Schrägstrich.
     */
    public function getBaseUrl(): string
    {
        $configured = $this->get('base_url');
        if (! empty($configured)) {
            return \rtrim((string) $configured, '/') . '/';
        }

        // Fallback NUR für lokale Entwicklungsumgebungen (.local, localhost)
        if ($this->get('is_local_env', false)) {
            $protocol = $this->get('server_protocol', 'http://');
            $host     = $this->get('server_host', 'localhost');
            $path     = \rtrim(\dirname($this->get('server_script', '')), '/\\');
            $path     = \str_replace('/api', '', $path);

            return $protocol . $host . $path . '/';
        }

        // Harter Abbruch im Live-Betrieb, um Host Header Injection zu verhindern
        throw new \RuntimeException('Sicherheits-Abbruch: "base_url" ist in der config/organization.php nicht gesetzt! Host-Header-Fallback ist deaktiviert.');
    }

    // TODO DOCBLOCK
    public function getStoragePath(string $fileName): string
    {
        return \rtrim((string) $this->get('root_path'), '/\\') . '/' .
               \ltrim((string) $this->get('storage_path_prefix'), '/\\') . $fileName;
    }
}
