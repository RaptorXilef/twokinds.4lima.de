<?php
define('ROOT_PATH', __DIR__ . '/../');
define('CONFIG_PATH', __DIR__ . '/');

// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false; // true = AN, false = AUS

// Stellt ein, ob interne Links die Dateiendung .php bekommen oder nicht.
// Wenn die .htaccess .php setzt, sollte der wert auf "false" stehen, 
// wenn .htaccess deaktiviert ist, sollte der Wert auf "true stehen".
$phpBoolen = false; // true = .php anhängen, false = kein .php



// ###################################################################
// Admin-Bereich: 

// Anzahl der Comic-Seiten pro Seite in der Übersicht des Comicseiten Editors.
$comicPagesPerPage = 50; // 50 ist ein guter Wert, der weder zu viele Seiten lädt, noch zu oft umblättern muss.


?>