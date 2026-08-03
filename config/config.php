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
    'maintenance_mode'       => false, // true = Sperrt das Frontend
    'maintenance_mode_admin' => false, // true = Sperrt zusätzlich das gesamte Admin-Dashboard

    // --- UMGEBUNGSSTEUERUNG ---
    'test_mode'      => false, // true = Sandbox-Modus (PayPal & Mails blockiert) | false = Produktion
    'admin_dev_mode' => false, // true = Hebelt Admin-Login aus (Nur für lokale Entwicklung!)

    // --- ADMINBEREICH ---
    // Wie oft rückgängig möglich?
    'comic_revision_limit' => 20,

    // --- BILD-VERARBEITUNG (MEDIA SERVICE) ---
    'webp_quality'       => 85,    // Standard WebP-Qualität (0-100)
    'webp_quality_thumb' => 80,    // Etwas aggressiver für Thumbnails
    'webp_lossless'      => false, // true = Erzwingt Qualität 100 (Lossless)

    // Hintergrundfarbe bei PNG/WebP Transparenzen.
    // 'transparent' belässt es durchsichtig. Hex-Code (z.B. '#ffffff') füllt es auf.
    'image_background_color' => 'transparent',

    // --- MASSEN-UPLOAD SCHWELLENWERTE ---
    // Legt die Höhe und Breite fest, ab wann ein Bild als Hires gild
    'hires_min_width'  => 1000,
    'hires_min_height' => 1800,

    // --- GRUNDEINSTELLUNGEN RSS und SITEMAP ---
    'base_url'         => 'https://twokinds.4lima.de', // OHNE Slash am Ende!
    'site_title'       => 'Twokinds auf Deutsch',
    'site_description' => 'Die deutsche Übersetzung des Webcomics Twokinds von Tom Fischbach, übersetzt von Felix Maywald.',
    'rss_max_items'    => 25,

    // --- TRACKING & ANALYTICS ---
    'google_analytics_id' => 'G-7VE3ZEWZQ7', // Leer lassen (''), um Analytics und das Banner komplett zu deaktivieren

    // --- IMPRESSUM SPAM-SCHUTZ ---
    'email_user'   => '',
    'email_domain' => '',

    // Social Media Links
    'social_patreon'  => 'https://www.patreon.com/RaptorXilef',
    'social_inkbunny' => 'https://inkbunny.net/RaptorXilefSFW',
    'social_paypal'   => 'https://paypal.me/RaptorXilef',
    'social_github'   => 'https://github.com/RaptorXilef/twokinds.4lima.de',
    'social_twokinds' => 'https://twokinds.keenspot.com/',
];
