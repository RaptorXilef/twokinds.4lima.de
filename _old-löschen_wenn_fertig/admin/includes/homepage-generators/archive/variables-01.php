<?php
/**
 * Dieses Skript gibt die Array-Variablen an den Maincode weiter um eine Archivdatei für eine Sammlung von Comics oder Bildern zu generrieren.
 *
 * Arrays:
 * - $startupPictures: Ein Array mit den Startbildern für jede Überschrift.
 * - $headings: Ein Array mit den Überschriften, Texten und Anzahl der Bilder für jedes Kapitel.
 */

$startupPictures = array(
    $archiveNumberIdInput => '20031022',
);

$pictureStartNumber = 1;

$headings = array(
    $archiveNumberIdInput => array(
        'ueberschrift' => 'Prolog (Oktober 2003 - November 2003)',
        'text' => 'Unser Held erwacht!',
        'anzahlBilder' => 5
    ),
);

?>