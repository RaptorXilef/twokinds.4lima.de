<?php
// src/config/app_config.php

// Basis-Dokumentenpfad des Webservers
define('APP_ROOT', $_SERVER['DOCUMENT_ROOT']);

// Pfade zu zentralen Verzeichnissen
define('SRC_DIR', APP_ROOT . '/src');
define('ADMIN_DIR', APP_ROOT . '/admin');
define('TEMPLATES_DIR', SRC_DIR . '/templates');
define('ADMIN_TEMPLATES_DIR', TEMPLATES_DIR . '/admin');
define('LAYOUT_TEMPLATES_DIR', TEMPLATES_DIR . '/layout');
define('PUBLIC_TEMPLATES_DIR', TEMPLATES_DIR . '/public');
define('ADMIN_CORE_DIR', SRC_DIR . '/admin/core');
define('ADMIN_ARCHIVE_GENERATOR_DIR', SRC_DIR . '/admin/archive_generator');
define('DATA_DIR', SRC_DIR . '/data');
define('INCLUDES_DIR', SRC_DIR . '/includes');

// Spezifische Pfade für Daten
define('COMIC_NAMES_FILE', DATA_DIR . '/comic_names.php'); // Angenommen, comicnamen.php wird zu comic_names.php umbenannt und die Datenstruktur angepasst
define('ARCHIVE_VARS_DIR', DATA_DIR . '/archive_vars');

// Pfade für den Archiv-Generator (Input/Output)
define('ARCHIVE_OUTPUT_FILE_ADMIN', APP_ROOT . '/archiv.php'); // Der Public-Archiv-Link
define('ARCHIVE_INPUT_DIR_ADMIN', APP_ROOT . '/includes/archive/'); // Alter Pfad
define('THUMBNAILS_DIR_ADMIN', APP_ROOT . '/thumbnails/'); // Alter Pfad
define('THUMBNAILS_DIR_ARCHIV_RELATIVE', './thumbnails/'); // Relativer Pfad für archive.php
define('COMIC_PHP_FILE_DIR_ARCHIV_RELATIVE', ''); // Relativer Pfad für archive.php (leer, falls im selben Ordner)

// Allgemeine Website-Informationen
define('SITE_TITLE_BASE', 'Twokinds - In Deutsch');
define('SITE_DESCRIPTION', 'TwoKinds in deutsch übersetzt! Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf.');
define('SITE_KEYWORDS', 'TwoKinds, in, auf, deutsch, übersetzt, uebersetzt, Web, Comic, Tom, Fischbach, RaptorXilef, Felix, Maywald, Reni, Nora, Trace, Flora, Keith, Natani, Zen, Sythe, Nibbly, Raine, Laura, Saria, Eric, Kathrin, Mike, Evals, Madelyn, Maren, Karen, Red, Templer, Keidran, Basitin, Mensch');

// Datenbankkonfiguration (falls benötigt, hier nur als Beispiel)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'twokinds_db');
// define('DB_USER', 'user');
// define('DB_PASS', 'password');

// Andere Konfigurationen
define('TIME_LIMIT', 300); // Standard-Zeitlimit für Skripte
?>