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
    // Sperrt ausschließlich das öffentliche Frontend für Besucher.
    // Das Admin-Panel unter /admin bleibt weiterhin erreichbar.
    'maintenance_mode' => false,

    // Sperrt ausschließlich das Backend (Admin-Panel).
    // Das Frontend bleibt für Leser erreichbar. Setze beide auf "true", um alles zu sperren.
    'maintenance_mode_admin' => false,

    // --- UMGEBUNGSSTEUERUNG ---
    // Sandbox-Modus: Wenn auf "true", werden keine echten E-Mails via SMTP versendet,
    // sondern nur im "mail_logs" der Datenbank als "Testmodus" protokolliert. Schützt vor versehentlichem Spam.
    'test_mode' => false,

    // Entwicklermodus: Wenn auf "true", hebelt dies die komplette Rechteprüfung (PermissionRegistry) aus.
    // Jeder eingeloggte Nutzer im Adminbereich hat dann sofort "Gott-Modus" (vollen Zugriff auf alles).
    // Nur lokal nutzen!
    'admin_dev_mode' => false,

    // --- ADMINBEREICH ---
    // Wie oft rückgängig möglich?
    'comic_revision_limit' => 20,

    // --- BILD-VERARBEITUNG (MEDIA SERVICE) ---
    'webp_quality' => 85,    // Standard WebP-Qualität (0-100)
    'webp_quality_thumb' => 80,    // Etwas aggressiver für Thumbnails
    'webp_lossless' => false, // true = Erzwingt Qualität 100 (Lossless)

    // Hintergrundfarbe bei PNG/WebP Transparenzen.
    // 'transparent' belässt es durchsichtig. Hex-Code (z.B. '#ffffff') füllt es auf.
    'image_background_color' => 'transparent',

    // --- MASSEN-UPLOAD SCHWELLENWERTE ---
    // Legt die Höhe und Breite fest, ab wann ein Bild als Hires gild
    'hires_min_width' => 1000,
    'hires_min_height' => 1800,

    // --- GRUNDEINSTELLUNGEN RSS und SITEMAP ---
    'base_url' => '', // Wird zur Laufzeit dynamisch im Core ermittelt
    'cli_fallback_url' => 'https://twokinds.4lima.local', // Fallback für Cronjobs (CLI)
    'site_title' => 'Twokinds auf Deutsch',
    'site_description' => 'Die deutsche Übersetzung des Webcomics Twokinds von Tom Fischbach, übersetzt von Felix Maywald.', // phpcs:ignore Generic.Files.LineLength.TooLong
    'rss_max_items' => 25,

    // --- TRACKING & ANALYTICS ---
    'google_analytics_id' => 'G-7VE3ZEWZQ7', // Leer lassen (''), um Analytics und das Banner komplett zu deaktivieren

    // --- IMPRESSUM SPAM-SCHUTZ ---
    'email_user' => '',
    'email_domain' => '',

    // Social Media Links
    'social_patreon' => 'https://www.patreon.com/RaptorXilef',
    'social_inkbunny' => 'https://inkbunny.net/RaptorXilefSFW',
    'social_paypal' => 'https://paypal.me/RaptorXilef',
    'social_github' => 'https://github.com/RaptorXilef/twokinds.4lima.de',
    'social_twokinds' => 'https://twokinds.keenspot.com/',
];
