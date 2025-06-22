<?php
/**
 * Diese Datei zeigt das Archiv der TwoKinds-Comics an.
 * Sie bindet verschiedene Archiv-Teile ein.
 */

// Setze Parameter für den Header. Der Seitentitel wird im Header automatisch mit Präfix versehen.
$pageTitle = 'Archiv';
$pageHeader = 'TwoKinds Archiv'; // Dieser Wert wird im Hauptinhaltsbereich angezeigt.
$additionalScripts = '<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script><script type="text/javascript" src="https://cdn.twokinds.keenspot.com/js/archive.js?c=20201116"></script>';
include __DIR__ . "/src/layout/header.php";
?>
<div class="instructions jsdep">Klicken Sie auf eine Kapitelüberschrift, um das Kapitel zu erweitern.</div>
<?php
// Die Archiv-Dateien bleiben vorerst im ursprünglichen Pfad, da sie spezifischen Inhalt haben
// und das Verschieben in eine neue Struktur mit vielen kleinen Dateien möglicherweise
// die Wartung nicht vereinfacht, sondern verlagert.
// Wenn du diese auch konsolidieren möchtest, sag Bescheid.
include "src/components/archive/archive-01.php";
include "src/components/archive/archive-02.php";
include "src/components/archive/archive-03.php";
include "src/components/archive/archive-04.php";
include "src/components/archive/archive-05.php";
include "src/components/archive/archive-06.php";
include "src/components/archive/archive-07.php";
include "src/components/archive/archive-08.php";
include "src/components/archive/archive-09.php";
include "src/components/archive/archive-10.php";
include "src/components/archive/archive-11.php";
include "src/components/archive/archive-12.php";
include "src/components/archive/archive-13.php";
include "src/components/archive/archive-14.php";
include "src/components/archive/archive-15.php";
include "src/components/archive/archive-16.php";
include "src/components/archive/archive-17.php";
include "src/components/archive/archive-18.php";
include "src/components/archive/archive-19.php";
include "src/components/archive/archive-20.php";
include "src/components/archive/archive-21.php";
include "src/components/archive/archive-22.php";
include "src/components/archive/archive-23.php";
include "src/components/archive/archive-24.php";
include "src/components/archive/archive-25.php";
//include "src/components/archive/archive-26.php";
//include "src/components/archive/archive-27.php";
//include "src/components/archive/archive-28.php";
//include "src/components/archive/archive-29.php";
//include "src/components/archive/archive-30.php";
?>
<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . "/src/layout/footer.php";
?>