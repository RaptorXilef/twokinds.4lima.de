<?php

/**
 * Core-Systemsteuerung (Infrastruktur & Umgebung)
 *
 * Diese Datei definiert die grundlegende Laufzeitumgebung des Servers.
 * Änderungen hier sollten nur durch den Systemadministrator vorgenommen werden.
 *
 * Path: config/config.php
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */

declare(strict_types=1);

return [
    // --- WARTUNGSMODUS (MAINTENANCE) ---
    'maintenance_mode'       => false, // true = Sperrt das öffentliche Pächter-Formular
    'maintenance_mode_admin' => false, // true = Sperrt zusätzlich das gesamte Admin-Dashboard

    // --- UMGEBUNGSSTEUERUNG ---
    'test_mode'      => false, // true = Sandbox-Modus (PayPal & Mails blockiert) | false = Produktion
    'admin_dev_mode' => false, // true = Hebelt Admin-Login aus (Nur für lokale Entwicklung!)
];
