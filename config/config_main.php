<?php

/**
 * @file      ROOT/config/config_main.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     1.0.0 Initiale Erstellung
 * @since     1.0.1 Fügt zwei include_once Anweisungen hinzu, um wichtige Pfad-Konstanten zu laden.
 * @since     1.0.2 Entfernt includes wieder.
 * @since     5.0.0 Füge KONSTANTEN TRUNCATE_DESCRIPTION und ENTRIES_PER_PAGE hinzu
 * - Füge seitenspezifische Konstanten hinzu.
 * - Hinzufügen von SESSION_TIMEOUT_SECONDS und SESSION_WARNING_SECONDS.
 */

// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = true;
// true = AN, false = AUS

// Stellt ein, ob interne Links die Dateiendung .php bekommen oder nicht.
// Wenn die .htaccess .php setzt, sollte der wert auf "false" stehen,
// wenn .htaccess deaktiviert ist, sollte der Wert auf "true stehen".
$phpBoolen = false;
// true = .php anhängen, false = kein .php


// Stellt ein, ob Cache-Busting aktiviert ist.
// Standard: true
define('ENABLE_CACHE_BUSTING', true);


// ###################################################################
// Admin-Bereich:

// Anzahl der Comic-Seiten pro Seite in der Übersicht des Comicseiten Editors.
define('ENTRIES_PER_PAGE', 50); // 50 ist ein guter Wert, der weder zu viele Seiten lädt, noch zu oft umblättern muss.

define('ENTRIES_PER_PAGE_ARCHIVE', ENTRIES_PER_PAGE);
define('ENTRIES_PER_PAGE_COMIC', ENTRIES_PER_PAGE);
define('ENTRIES_PER_PAGE_REPORT', ENTRIES_PER_PAGE);
define('ENTRIES_PER_PAGE_SITEMAP', ENTRIES_PER_PAGE);

// Legt fest, ob die Beschreibungen beim Archiv-Editor gekürzt oder voll angezeigt werden. Standart ist: false (nicht kürzen)
define('TRUNCATE_DESCRIPTION', true);
// Legt fest, ob die Beschreibungen beim Archiv-Editor gekürzt oder voll angezeigt werden. Standart ist: false (nicht kürzen)
define('TRUNCATE_ARCHIVE_DESCRIPTION', TRUNCATE_DESCRIPTION);
// Legt fest, ob die Beschreibungen beim Comic-Editor gekürzt oder voll angezeigt werden. Standart ist: false (nicht kürzen)
define('TRUNCATE_COMIC_DESCRIPTION', TRUNCATE_DESCRIPTION);
// Legt fest, ob die Beschreibungen beim Report-Editor gekürzt oder voll angezeigt werden. Standart ist: false (nicht kürzen)
define('TRUNCATE_REPORT_DESCRIPTION', TRUNCATE_DESCRIPTION);


// ###################################################################
// Session & Sicherheit:

// Zeit in Sekunden bis zum automatischen Logout bei Inaktivität.
// Standard: 600 (10 Minuten)
define('SESSION_TIMEOUT_SECONDS', 600);

// Zeit in Sekunden, BEVOR der Timeout eintritt, wann die Warnung angezeigt werden soll.
// Standard: 60 (1 Minute vor Ablauf)
define('SESSION_WARNING_SECONDS', 60);

// Zeit in Sekunden, BEVOR der Session-Key werden soll.
// Ich empfehle diesen Wert nicht niedriger als 300 und höher als 3600 zu setzen (5 Min bis 1 Stunde)
// Standard: 900 (alle 15 Minuten)
define('SESSION_REGENERATION_SECOUNDS', 900);
