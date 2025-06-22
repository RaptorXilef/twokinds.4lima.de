<?php
// src/admin/archive_generator/generator_logic.php

// Lade die globale Konfiguration (falls nicht bereits geladen)
if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../../config/app_config.php';
}
require_once INCLUDES_DIR . '/functions.php'; // Für generateImageLinks

/**
 * Generiert den Inhalt der archive.php-Datei und speichert sie.
 *
 * @param int $archiveNumber Die Nummer des Archivs.
 * @param array $startupPictures Array mit Startbildern.
 * @param array $headings Array mit Überschriften, Texten und Anzahl der Bilder.
 * @param string $archiveInputDirForAdmin Der Pfad zum Archiv-Input-Verzeichnis (wird im neuen System nicht mehr direkt benötigt, aber zur Kompatibilität behalten).
 * @param string $archiveFileNameOutput Der vollständige Pfad zur Ausgabedatei (z.B. /path/to/archiv.php).
 * @param string $thumbnailsDirForAdmin Verzeichnis für Thumbnails im Admin (absoluter Pfad).
 * @param string $thumbnailsDirForArchiv Relativer Pfad für Thumbnails im generierten Archiv.
 * @param string $comicPHPfileDirForArchiv Relativer Pfad für Comic-PHP-Dateien im generierten Archiv.
 */
function generateArchiveFile(
    int $archiveNumber,
    array $startupPictures,
    array $headings,
    string $archiveInputDirForAdmin, // Könnte obsolet werden, je nach genauer Verwendung
    string $archiveFileNameOutput,
    string $thumbnailsDirForAdmin, // Könnte obsolet werden, da generateImageLinks den Public-Pfad braucht
    string $thumbnailsDirForArchiv,
    string $comicPHPfileDirForArchiv
) {
    echo '<h1 style="color: green; font-weight: bold;">Archiv-Generator gestartet</h1>';

    ob_start(); // Starte Output Buffering
?>

<?php foreach ($headings as $chId => $data): // $chId ist hier die Archivnummer ?>
<section class="chapter" data-ch-id="<?php echo htmlspecialchars($chId); ?>">
    <h2><?php echo htmlspecialchars($data['ueberschrift']); ?><span class="arrow-left jsdep"></span></h2>
    <p><?php echo $data['text']; ?></p>
    <aside class="chapter-links">
        <?php
        // Verwende die generateImageLinks Funktion aus functions.php
        echo generateImageLinks(
            $startupPictures[$chId],
            $data['anzahlBilder'],
            $thumbnailsDirForAdmin, // Dieser Wert wird in generateImageLinks nicht mehr direkt verwendet, da es den Public-Pfad braucht
            $comicPHPfileDirForArchiv,
            $thumbnailsDirForArchiv
        );
        ?>
    </aside>
</section>
<?php endforeach; ?>

<?php
    $archiveContent = ob_get_clean(); // Hole den gepufferten Inhalt und leere den Puffer

    // Speichere den generierten Inhalt in die Datei
    $fileWritten = file_put_contents($archiveFileNameOutput, $archiveContent);

    if ($fileWritten !== false) {
        echo '<h2 style="color: green; font-weight: bold;">Vorgang erfolgreich beendet</h2>';
    } else {
        echo '<h2 style="color: red; font-weight: bold;">Fehler beim Schreiben der Datei: ' . htmlspecialchars($archiveFileNameOutput) . '</h2>';
        error_log("Fehler beim Schreiben der Archivdatei: " . $archiveFileNameOutput);
    }
}