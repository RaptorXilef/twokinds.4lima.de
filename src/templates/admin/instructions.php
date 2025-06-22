<?php
// src/templates/admin/instructions.php
// Anleitung für den Adminbereich

// Lade die globale Konfiguration (falls nicht bereits geladen)
if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../../config/app_config.php';
}
?>
<div class="linksbuendig">
    <h2>Aktualisierungsablauf</h2>

    <h3>1. Bilder hochladen:</h3>
    <ul>
        <li>Kleines PNG: <code>tk/comic</code></li>
        <li>Großes JPG: <code>tk/comic_hires</code></li>
        <li>Dateiname: <code>YYYYMMDD.png</code></li>
    </ul>

    <h3>2. Bearbeite Archiv-Generator Variablen:</h3>
    <ul>
        <li>Datei: <code><?php echo htmlspecialchars(str_replace(APP_ROOT, 'tk', ARCHIVE_VARS_DIR)); ?>/XX.php</code></li>
        <li>Bearbeite die höchste Nummer.</li>
        <li>Erhöhe "anzahlBilder" um die Anzahl der hochgeladenen Bilder in <code>tk/comic</code>.</li>
        <li><strong>WICHTIG:</strong> Bearbeite offline und synchronisiere oder kopiere die Daten als Backup.</li>
    </ul>

    <h3>3. Bearbeite Comicnamen-Daten:</h3>
    <ul>
        <li>Datei: <code><?php echo htmlspecialchars(str_replace(APP_ROOT, 'tk', COMIC_NAMES_FILE)); ?></code></li>
        <li>Füge die Namen der neuen Seiten hinzu.</li>
        <li>Vorlage: <code>'<?php echo date('Ymd'); ?>' => ['type' => 'Comicseite vom ', 'name' => '[NAME]'],</code></li>
        <li>**Hinweis:** Die `comicnamen.php` sollte zu einem PHP-Array in `src/data/comic_names.php` umgewandelt werden, um die Lesbarkeit und Handhabung zu verbessern.</li>
    </ul>

    <h3>4. Klicke im Adminbereich auf:</h3>
    <ol>
        <li>SEITENGENERATOR STARTEN</li>
        <li>THUMBNAILS - BILD (THUMBNAILS - TEXT nur bei mehr als 20 Comicseiten)</li>
        <li>ARCHIV -> gefolgt von der Nummer der "variables-[Nr].php" aus Schritt 2.</li>
        <li>SITEMAP</li>
    </ol>
</div>