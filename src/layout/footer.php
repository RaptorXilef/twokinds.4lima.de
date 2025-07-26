<?php
/**
 * Gemeinsamer Footer für alle Seiten.
 * Enthält Copyright-Informationen und schließt die HTML-Struktur ab.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
// Diese Variable wird in dieser Datei aktuell nicht verwendet, da keine error_log Aufrufe vorhanden sind.
/* $debugMode = false; */

// Pfad zur version.json Datei
// Die $baseUrl sollte von der header.php verfügbar sein, da diese Datei nach dem Header eingebunden wird.
$versionJsonPath = __DIR__ . '/../config/version.json'; // Relativer Pfad von src/layout/footer.php

$versionInfo = [
    'version' => 'Unbekannt',
    'type' => 'Unbekannt'
];

if (file_exists($versionJsonPath)) {
    $versionContent = file_get_contents($versionJsonPath);
    $decodedVersion = json_decode($versionContent, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedVersion)) {
        $versionInfo['version'] = htmlspecialchars($decodedVersion['version'] ?? 'Unbekannt');
        $versionInfo['type'] = htmlspecialchars($decodedVersion['type'] ?? 'Unbekannt');
    } else {
        error_log("Fehler beim Dekodieren von version.json: " . json_last_error_msg());
        $versionInfo['version'] = 'Fehler beim Laden (JSON)';
    }
} else {
    error_log("version.json nicht gefunden unter: " . $versionJsonPath);
    $versionInfo['version'] = 'Fehler beim Laden (Datei nicht gefunden)';
}

?>
</article>
</main>
</div>
<footer>
    TWOKINDS, sein Logo und alle zugehörigen Zeichen sind urheberrechtlich geschützt; 2023 Thomas J. Fischbach.
    Website-Design von Thomas J. Fischbach & Brandon J. Dusseau.</br>
    Ab Kapitel 21 ins deutsche übersetzt von Felix Maywald. Kapitel 01 bis 20 ins deutsche übersetzt von <a
        href="https://www.twokinds.de/">Cornelius Lehners</a>.</br>
    </br> Der Webspace wird mir kostenlos von <a href="https://www.lima-city.de/">www.lima-city.de</a> bereitgestellt!
    </br>
    <span class="website-version">Webseite Version: <?php echo $versionInfo['version']; ?>
        (<?php echo $versionInfo['type']; ?>) - siehe: <a
            href="https://github.com/RaptorXilef/twokinds.4lima.de/releases">Github</a> </span>
</footer>
<div class="footer-img"></div>
</div>
</body>

</html>