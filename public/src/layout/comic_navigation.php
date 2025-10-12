<?php
/**
 * Dieses Skript ist für das Rendern der Comic-Navigationsbuttons zuständig.
 * Es erwartet, dass $currentComicId, $comicData und $baseUrl
 * aus dem inkludierenden Skript verfügbar sind.
 * $isCurrentPageLatest ist optional und kann vom inkludierenden Skript gesetzt werden (z.B. von index.php).
 * 
 * @file      ROOT/public/src/layout/comic_navigation.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.2.0
 * @since     1.2.0 Umstellung auf globale Pfad-Konstanten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Lade die Hilfsfunktion zum Rendern der Navigationsbuttons (Jetzt mit Konstante).
// Diese Datei sollte nur einmal pro Request eingebunden werden, um den "Cannot redeclare function"-Fehler zu vermeiden.
require_once NAV_LINK_HELPER_PATH;

// Prüfe, ob die essentiellen Daten verfügbar sind.
if (!isset($comicData)) {
    if ($debugMode)
        error_log("FEHLER: \$comicData ist in comic_navigation.php nicht verfügbar. Navigation kann nicht gerendert werden.");
    return; // Skript beenden, wenn Daten fehlen
}
if (!isset($baseUrl)) {
    if ($debugMode)
        error_log("FEHLER: \$baseUrl ist in comic_navigation.php nicht verfügbar. Absolute Links können nicht generiert werden.");
    return; // Skript beenden, wenn Basis-URL fehlt
}
if (!isset($currentComicId)) {
    // Dieser Fall sollte durch die aufrufenden Seiten verhindert werden, ist aber ein Fallback.
    if ($debugMode)
        error_log("WARNUNG: \$currentComicId ist in comic_navigation.php nicht verfügbar.");
}

// Initialisiere Navigations-Variablen
$prevPageUrl = 'javascript:void(0)';
$nextPageUrl = 'javascript:void(0)';
$firstPageUrl = 'javascript:void(0)';
$lastPageUrl = $baseUrl; // Die "Letzte Seite" zeigt immer auf die Haupt-Indexseite des Projekts (neuester Comic)

// Standardwerte für die Flags, falls nicht von der aufrufenden Seite gesetzt.
// $isCurrentPageLatest wird von index.php im Root gesetzt, um den "Letzte Seite" Button zu deaktivieren.
$isCurrentPageLatest = isset($isCurrentPageLatest) ? $isCurrentPageLatest : false;
$isCurrentPageFirst = false;

// Wenn Comic-Daten verfügbar sind, berechne die Navigationslinks.
if (!empty($comicData)) {
    $comicKeys = array_keys($comicData);
    $firstComicId = reset($comicKeys); // ID des ältesten Comics
    $latestComicId = end($comicKeys); // ID des neuesten Comics

    $currentIndex = array_search($currentComicId, $comicKeys);

    if ($currentIndex !== false) {
        // Prüfe, ob die aktuelle Seite die allererste Seite ist.
        if ($currentIndex === 0) {
            $isCurrentPageFirst = true;
        }

        // Link für "Erste Seite": Wenn es die erste Seite ist, deaktiviere den Link.
        // Ansonsten generiere den Link zur ersten Comic-Seite.
        if (!$isCurrentPageFirst) {
            $firstPageUrl = $baseUrl . 'comic/' . htmlspecialchars($firstComicId) . $dateiendungPHP;
        }

        // Link für "Vorherige Seite": Wenn es nicht die erste Seite ist, nimm die vorherige Seite
        // und generiere den Link.
        if ($currentIndex > 0) {
            $prevPageUrl = $baseUrl . 'comic/' . htmlspecialchars($comicKeys[$currentIndex - 1]) . $dateiendungPHP;
        }

        // Link für "Nächste Seite": Wenn es die neueste Seite ist (index.php-Szenario)
        // oder wenn es die letzte Seite in der Comic-Liste ist, deaktiviere den Link.
        // Ansonsten generiere den Link.
        if (!$isCurrentPageLatest && ($currentIndex < count($comicKeys) - 1)) {
            $nextPageUrl = $baseUrl . 'comic/' . htmlspecialchars($comicKeys[$currentIndex + 1]) . $dateiendungPHP;
        } else {
            // Wenn die aktuelle Seite die logisch letzte (neueste) ist, gibt es keine "Nächste Seite".
            // Der Link wird durch leeren string oder javascript:void(0) deaktiviert, je nachdem wie renderNavLink es handhabt.
            $nextPageUrl = 'javascript:void(0)';
        }
    } else {
        // Fallback, falls die aktuelle Comic-ID nicht gefunden wird.
        if ($debugMode)
            error_log("WARNUNG: Aktuelle Comic ID '{$currentComicId}' nicht in " . COMIC_VAR_JSON_FILE . " gefunden. Navigation möglicherweise fehlerhaft.");
        $isCurrentPageFirst = true;
        $isCurrentPageLatest = true;
    }
} else {
    // Wenn comicData leer ist, sollten alle Navigationslinks deaktiviert sein.
    if ($debugMode)
        error_log("WARNUNG: \$comicData ist leer. Alle Navigationslinks werden deaktiviert.");
    $isCurrentPageFirst = true;
    $isCurrentPageLatest = true;
}

// Bestimme die Deaktivierungs-Flags für die renderNavLink Funktion
$disableFirst = $isCurrentPageFirst;
$disablePrev = $isCurrentPageFirst; // Vorherige ist deaktiviert, wenn es die erste Seite ist
$disableNext = $isCurrentPageLatest; // Nächste ist deaktiviert, wenn es die neueste ist
$disableLast = $isCurrentPageLatest; // Letzte ist deaktiviert, wenn es die neueste ist (was index.php ist)

?>
<?php echo renderNavLink($firstPageUrl, 'navbegin', 'Erste Seite', $disableFirst); ?>
<?php echo renderNavLink($prevPageUrl, 'navprev', 'Vorherige Seite', $disablePrev); ?>
<a href="<?php echo $baseUrl; ?>archiv.php" class="navarchive">
    <span class="nav-wrapper">Archiv</span>
</a>
<?php echo renderNavLink($nextPageUrl, 'navnext', 'Nächste Seite', $disableNext); ?>
<?php echo renderNavLink($lastPageUrl, 'navend', 'Letzte Seite', $disableLast); ?>