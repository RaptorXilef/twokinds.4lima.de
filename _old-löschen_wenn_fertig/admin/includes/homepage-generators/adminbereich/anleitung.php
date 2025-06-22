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
        <li>Datei: <code>variables-[Nr].php</code> in <code>tk/admin/includes/homepage-generators/archive</code></li>
        <li>Bearbeite die höchste Nummer.</li>
        <li>Erhöhe "anzahlBilder" um die Anzahl der hochgeladenen Bilder in <code>tk/comic</code>.</li>
        <li><strong>WICHTIG:</strong> Bearbeite offline und synchronisiere oder kopiere die Daten als Backup.</li>
    </ul>

    <h3>3. Bearbeite comicnamen.php:</h3>
    <ul>
        <li>Datei: <code>comicnamen.php</code> in <code>tk/includes</code></li>
        <li>Füge die Namen der neuen Seiten hinzu.</li>
        <li>Vorlage: <code>&lt;?php $comicTypInput[DATUM] = 'Comicseite vom '; $comicNameInput[DATUM] = '[NAME]';?&gt;</code></li>
    </ul>

    <h3>4. Klicke im Adminbereich auf:</h3>
    <ol>
        <li>SEITENGENERATOR STARTEN</li>
        <li>THUMBNAILS - BILD (THUMBNAILS - TEXT nur bei mehr als 20 Comicseiten)</li>
        <li>ARCHIV -> gefolgt von der Nummer der "variables-[Nr].php" aus Schritt 2.</li>
        <li>SITEMAP</li>
    </ol>

    <h3>5. Ausloggen:</h3>
    <p>Sobald die Schritte abgeschlossen sind.</p>

    <h3>6. InkBunny nicht vergessen:</h3>
    <p>Lade auch Comicseiten auf InkBunny hoch.</p>
</div>