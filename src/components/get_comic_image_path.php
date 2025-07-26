<?php
/**
 * Helper-Funktion zum Auffinden des korrekten Bildpfads und der Dateierweiterung für Comic-Bilder.
 * Sucht nach .jpg, .png und .gif in der angegebenen Reihenfolge im Basisverzeichnis.
 *
 * @param string $comicId Die ID des Comics (typischerweise das Datum, z.B. '20250312').
 * @param string $baseDir Das Basisverzeichnis (z.B. './assets/comic_lowres/' oder './assets/comic_hires/').
 * @param string $suffix Ein optionaler Suffix für den Dateinamen (z.B. '_preview' für Thumbnails).
 * @return string Der vollständige relative Pfad zum Comic-Bild, falls gefunden, andernfalls ein leerer String.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
// Diese Variable wird in dieser Datei aktuell nicht verwendet, da keine error_log Aufrufe vorhanden sind.
/* $debugMode = false; */

function getComicImagePath(string $comicId, string $baseDir, string $suffix = ''): string
{
    // Bevorzugte Reihenfolge der Dateierweiterungen, in der gesucht wird.
    // Falls ein Suffix vorhanden ist (z.B. '_preview'), ist es wahrscheinlicher, dass es sich um ein PNG handelt.
    // Daher wird die Reihenfolge angepasst, um .png zuerst zu prüfen, falls ein Suffix verwendet wird.
    $extensions = ['png', 'jpg', 'gif'];
    if (empty($suffix)) { // Wenn kein Suffix, ist .jpg oft das Hauptformat
        $extensions = ['jpg', 'png', 'gif'];
    }

    foreach ($extensions as $ext) {
        $filePath = $baseDir . htmlspecialchars($comicId) . $suffix . '.' . $ext;
        // file_exists benötigt einen absoluten Pfad. __DIR__ ist das Verzeichnis dieser Datei.
        // '/../../' geht von 'src/components/' zwei Ebenen hoch ins Hauptverzeichnis, um dann $filePath anzuhängen.
        if (file_exists(__DIR__ . '/../../' . $filePath)) {
            return $filePath;
        }
    }
    // Wenn kein passendes Bild gefunden wurde, gib einen leeren String zurück.
    return '';
}
