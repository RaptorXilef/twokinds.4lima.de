<?php
/**
 * Gemeinsamer Footer für alle Seiten.
 * Enthält Copyright-Informationen und schließt die HTML-Struktur ab.
 * 
 * @file      ROOT/templates/partials/footer.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.2.0
 * @since     2.2.0 Redundanter include für configLoader.php entfernt, da die Konstanten global verfügbar sind.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Die Konstante VERSION_JSON wird jetzt zentral über public_init.php geladen.
/** @uses VERSION_JSON */

$versionInfo = [
    'version' => 'Unbekannt',
    'type' => 'Unbekannt'
];

if (defined('VERSION_JSON') && file_exists(VERSION_JSON)) {
    $versionContent = file_get_contents(VERSION_JSON);
    $decodedVersion = json_decode($versionContent, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedVersion)) {
        $versionInfo['version'] = htmlspecialchars($decodedVersion['version'] ?? 'Unbekannt');
        $versionInfo['type'] = htmlspecialchars($decodedVersion['type'] ?? 'Unbekannt');
    } else {
        if ($debugMode)
            error_log("Fehler beim Dekodieren von version.json: " . json_last_error_msg());
        $versionInfo['version'] = 'Fehler (JSON)';
    }
} else {
    $versionJsonPath = defined('VERSION_JSON') ? VERSION_JSON : 'nicht definiert';
    if ($debugMode)
        error_log("version.json nicht gefunden unter: " . $versionJsonPath);
    $versionInfo['version'] = 'Fehler (Datei)';
}

?>
</article>
</main>
</div>
<footer>
    TWOKINDS, sein Logo und alle zugehörigen Zeichen sind urheberrechtlich geschützt; 2023 Thomas J. Fischbach.
    Website-Design von Thomas J. Fischbach & Brandon J. Dusseau.</br>
    Website-Design von F. Maywald überarbeitet und angepasst.<br>
    <?php /*Ab Kapitel 21 ins deutsche übersetzt von Felix Maywald. Kapitel 01 bis 20 ins deutsche übersetzt von <a
href="https://www.twokinds.de/">Cornelius Lehners</a>.</br>*/ ?>
    </br> Der Webspace wird für dieses Projekt wird kostenlos von <a
        href="https://www.lima-city.de/">www.lima-city.de</a> bereitgestellt!
    <br><br> Homepage und Übersetzungen &copy; 2023-2025 Felix Maywald
    </br>
    <span class="website-version">Homepage Version: <?php echo $versionInfo['version']; ?>
        (<?php echo $versionInfo['type']; ?>) - siehe: <a
            href="https://github.com/RaptorXilef/twokinds.4lima.de/releases">Github</a> </span>
</footer>
<div class="footer-img"></div>
</div>
</body>

</html>