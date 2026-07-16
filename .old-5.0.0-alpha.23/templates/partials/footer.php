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
 *
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 * @since     5.0.0 refactor: 'version.json' entfernt, nutzt nun 'package.json' via Path::getRootPath().
 * @since     5.0.1 feat: Dynamische Jahreszahl für Copyright-Bereich hinzugefügt.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

$versionInfo = [
    'version' => 'Unbekannt',
    'type'    => 'release',
];

/**
 * Lade Versions-Informationen aus der package.json im System-Root.
 * Nutzt die zentrale Path-Klasse für konsistente Pfadauflösung.
 */
$packageJsonPath = Path::getRootPath('package.json');

if (file_exists($packageJsonPath)) {
    $packageContent = file_get_contents($packageJsonPath);
    $decodedPackage = json_decode($packageContent, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($decodedPackage['version'])) {
        $fullVersion = $decodedPackage['version']; // z.B. "5.0.0-alpha.15"

        // Trenne Version und Typ am ersten Bindestrich (SemVer-Format)
        if (str_contains($fullVersion, '-')) {
            $parts = explode('-', $fullVersion, 2);
            $versionInfo['version'] = htmlspecialchars($parts[0]);
            $versionInfo['type']    = htmlspecialchars($parts[1]);
        } else {
            $versionInfo['version'] = htmlspecialchars($fullVersion);
            $versionInfo['type']    = 'release';
        }
    } else {
        if ($debugMode) {
            error_log("Fehler beim Dekodieren von 'package.json' oder 'version' fehlt.");
        }
        $versionInfo['version'] = 'Fehler (JSON)';
    }
} else {
    if ($debugMode) {
        error_log("'package.json' nicht gefunden unter: " . $packageJsonPath);
    }
    $versionInfo['version'] = 'Fehler (Datei)';
}

?>
                </article>
            </main>
        </div> <!-- Schließt id="content-area" class="content-area" --->
        <footer>
            <p>TWOKINDS, sein Logo und alle zugehörigen Zeichen sind urheberrechtlich geschützt; &copy; 2023-<?php echo date('Y'); ?> Thomas J. Fischbach.</p>
            <p> Der Webspace wird für dieses Projekt kostenlos von <a href="https://www.lima-city.de/">www.lima-city.de</a> bereitgestellt!</p>
            <p>Website-Design von F. Maywald (Angelehnt an das Design von <a href="https://twokinds.keenspot.com/">TwoKinds</a>).</p>
            <?php /*Ab Kapitel 21 ins deutsche übersetzt von Felix Maywald. Kapitel 01 bis 20 ins deutsche übersetzt von <a
        href="https://www.twokinds.de/">Cornelius Lehners</a>.<br>*/ ?>

            <p> Homepage und Übersetzungen &copy; 2023-<?php echo date('Y'); ?> Felix Maywald</p>
            <p><span class="website-version">Homepage Version: <?php echo $versionInfo['version']; ?>
                (<?php echo $versionInfo['type']; ?>) - siehe: <a
                    href="https://github.com/RaptorXilef/twokinds.4lima.de/releases">GitHub</a></p>
            </span>
        </footer>
        <div class="footer-img"></div>
    </div> <!-- Schließt id="mainContainer" class="main-container" --->
</body>

</html>
