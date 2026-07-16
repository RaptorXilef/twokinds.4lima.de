<?php

/**
 * @file      ROOT/src/components/load_config.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung
 */

$configExist = true;

// Lädt die grundlegenden Verzeichnis-Konstanten.
include_once __DIR__ . '/../../config/config_folder_path.php';

// Lädt die Haupt-Konfigurationsdatei.
include_once __DIR__ . '/../../config/config_main.php';

// Lädt die neue Path-Helfer-Klasse für dynamische Pfad-Generierung.
include_once 'Path.php';
include_once 'Url.php';

if ($phpBoolen) :
    $dateiendungPHP = '.php';
else :
    $dateiendungPHP = '';
endif;
