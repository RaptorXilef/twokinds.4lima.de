<?php
/**
 * Dateiname: generate_archive-[NUMBER].php
 * 
 * Beschreibung:
 * Dieser Code extrahiert eine Zahl aus dem eigenen Dateinamen und gibt sie aus. Der Dateiname
 * hat das Format "generate_archive-[NUMBER].php". Es werden zunächst ".php" am Ende des Dateinamens
 * entfernt. Anschließend wird die Zahl, die sich nach dem Bindestrich "-" befindet, extrahiert.
 * Führende Nullen werden entfernt und die extrahierte Zahl mit führenden Nullen wird ausgegeben.
 * Zusätzlich wird die extrahierte Zahl ohne führende Nullen und ohne ".php" ausgegeben.
 */

$archiveNumberInput = str_pad($archiveNumberIdInput, 2, '0', STR_PAD_LEFT);  // Führende Nullen hinzufügen



/**
 * Dieses Skript gibt die Variablen an den Maincode weiter um eine Archivdatei für eine Sammlung von Comics oder Bildern zu generrieren.
 *
 * Konfigurationsvariablen:
 * - $archiveDirInputForAdmin: Der Pfad zum Archivverzeichnis der Inputdateien > '../includes/archive/'.
 * - $archiveNameInput: Der Basename für die Archivdateien. > 'archive-'
 * - $archiveNumberInput: Die Nummer des Archivs basierend auf die aus dem Dateinamen ausgelesenen Nummer.
 * - $archiveFileNameOutput: Die archive[name] variablen werden verkettet und bekommen die Dateiendung '.php'
 *
 * - $thumbnailsDirForAdmin: Das Verzeichnis für Thumbnails im Administrationsbereich. > '../thumbnails/'
 * - $thumbnailsDirForArchiv: Das Verzeichnis für Thumbnails im Archivbereich. > 'thumbnails/' oder './thumbnails/'
 * - $ComicPHPfileDirForArchiv: Das Verzeichnis für die Comic-PHP-Dateien im Archivbereich. > ''
 */

$archiveFileDirOutputForAdmin = '../archiv.php';

$archiveDirInputForAdmin = '../includes/archive/';
$archiveNameInput = 'archive-';
$archiveFileNameOutput = $archiveDirInputForAdmin . $archiveNameInput . $archiveNumberInput . '.php';

$thumbnailsDirForAdmin = '../thumbnails/';
$thumbnailsDirForArchiv = './thumbnails/';

$ComicPHPfileDirForArchiv = '';
?>