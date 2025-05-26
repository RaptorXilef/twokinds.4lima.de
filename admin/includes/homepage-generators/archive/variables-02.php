<?php
/**
 * Dieses Skript gibt die Array-Variablen an den Maincode weiter um eine Archivdatei für eine Sammlung von Comics oder Bildern zu generrieren.
 *
 * Arrays:
 * - $startupPictures: Ein Array mit den Startbildern für jede Überschrift.
 * - $headings: Ein Array mit den Überschriften, Texten und Anzahl der Bilder für jedes Kapitel.
 */

$startupPictures = array(
    $archiveNumberIdInput => '20031104',
);

$pictureStartNumber = 6;

$headings = array(
    $archiveNumberIdInput => array(
        'ueberschrift' => 'Kapitel 1 (Nov 2003 - Dez 2003)',
        'text' => 'Der Mensch Trace und die Keidran Flora verbringen die <a href="20031117.php">erste gemeinsame Nacht</a>. Floras Ex, Sythe, hat seinen <a href="20031128.php">ersten Auftritt!</a>',
        'anzahlBilder' => 15 + $pictureStartNumber - 1
    ),
);
?>