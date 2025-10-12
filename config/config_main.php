<?php
/**
 * @file      ROOT/config/config_main.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung
 * @version   1.0.1 Fügt zwei include_once Anweisungen hinzu, um wichtige Pfad-Konstanten zu laden.
 */


// config_folder_path.php fügt folgende Konstanten hinzu:
// PRIVATE_PATH, PRIVATE_SRC_PATH, CONFIG_PATH, DB_ASSETS_PATH, PUBLIC_PATH
include_once 'config_folder_path.php';
include_once 'config_file_path.php';

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