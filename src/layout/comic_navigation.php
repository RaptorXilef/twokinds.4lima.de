<?php
/**
 * Verwaltet und zeigt die Comic-Navigationslinks an.
 * Ermittelt die Links zur vorherigen, nächsten, ersten und letzten Seite.
 *
 * Diese Datei generiert NUR die Navigations-Links (<a>-Tags) OHNE den umgebenden Div-Container.
 * Der Container wird in der aufrufenden Comic-Seite (z.B. 20250312.php oder index.php) hinzugefügt.
 *
 * Benötigt das $comicData Array und $currentComicId.
 * Optional kann $isCurrentPageLatest von der aufrufenden Seite gesetzt werden (z.B. von index.php).
 */

// Lade die Hilfsfunktion zum Rendern der Navigationsbuttons.
// Diese Datei sollte nur einmal pro Request eingebunden werden, um den "Cannot redeclare function"-Fehler zu vermeiden.
require_once __DIR__ . '/../components/nav_link_helper.php';

// Initialisiere Navigations-Variablen
$prevPage = '';
$nextPage = '';
$firstPageLink = ''; // Wird der Link oder '#' sein
$lastPageLink = './'; // Die letzte Seite ist immer die index.php (neuester Comic)

// Bestimme den Basispfad für Comic-Dateien.
// Wenn die aktuelle Seite nicht im '/comic/' Verzeichnis liegt (z.B. index.php im Hauptverzeichnis),
// dann müssen alle Comic-Links mit 'comic/' präfixiert werden.
// Dies wird anhand von $_SERVER['PHP_SELF'] überprüft.
$comicFilePrefix = '';
if (strpos($_SERVER['PHP_SELF'], '/comic/') === false) {
    $comicFilePrefix = 'comic/';
}

// Standardwerte für die Flags, falls nicht von der aufrufenden Seite gesetzt.
$isCurrentPageLatest = isset($isCurrentPageLatest) ? $isCurrentPageLatest : false;
$isCurrentPageFirst = false; // Neu: Flag, ob die aktuelle Seite der allererste Comic ist.

// Wenn Comic-Daten verfügbar sind, berechne die Navigationslinks.
if (!empty($comicData)) {
    $comicKeys = array_keys($comicData);
    $currentIndex = array_search($currentComicId, $comicKeys);

    if ($currentIndex !== false) {
        // Prüfe, ob die aktuelle Seite die allererste Seite ist.
        if ($currentIndex === 0) {
            $isCurrentPageFirst = true;
        }

        // Link für "Erste Seite": Wenn es die erste Seite ist, deaktiviere den Link.
        // Ansonsten füge den Präfix hinzu.
        if ($isCurrentPageFirst) {
            $firstPageLink = '#'; // Deaktiviert
        } else {
            $firstPageLink = $comicFilePrefix . $comicKeys[0] . '.php'; // Pfad mit/ohne Prefix
        }

        // Link für "Vorherige Seite": Wenn es nicht die erste Seite ist, nimm die vorherige Seite
        // und füge den Präfix hinzu.
        if ($currentIndex > 0) {
            $prevPage = $comicFilePrefix . $comicKeys[$currentIndex - 1] . '.php';
        } else {
            // Wenn es die erste Seite ist, gibt es keine vorherige Seite, Link deaktivieren.
            $prevPage = '';
        }

        // Link für "Nächste Seite": Wenn es die neueste Seite ist (index.php-Szenario)
        // oder wenn es die letzte Seite in der Comic-Liste ist, deaktiviere den Link.
        // Ansonsten füge den Präfix hinzu.
        if ($isCurrentPageLatest || ($currentIndex === count($comicKeys) - 1)) {
            $nextPage = '';
        } else {
            $nextPage = $comicFilePrefix . $comicKeys[$currentIndex + 1] . '.php';
        }
    } else {
        // Fallback, falls die aktuelle Comic-ID nicht gefunden wird (sollte bei korrekter Logik nicht passieren).
        $isCurrentPageFirst = true;
        $isCurrentPageLatest = true;
        $firstPageLink = '#';
        $prevPage = '';
        $nextPage = '';
        $lastPageLink = '#';
    }
} else {
    // Wenn comicData leer ist, sollten alle Navigationslinks deaktiviert sein.
    $isCurrentPageFirst = true;
    $isCurrentPageLatest = true;
    $firstPageLink = '#';
    $prevPage = '';
    $nextPage = '';
    $lastPageLink = '#';
}
?>
    <?php echo renderNavLink($firstPageLink, 'navbegin', 'Erste Seite', $isCurrentPageFirst); ?>
    <?php echo renderNavLink($prevPage, 'navprev', 'Vorherige Seite', empty($prevPage) || $isCurrentPageFirst); ?>
    <a href="/archiv.php" class="navarchive">
        <span class="nav-wrapper">Archiv</span>
    </a>
    <?php echo renderNavLink($nextPage, 'navnext', 'Nächste Seite', empty($nextPage)); ?>
    <?php echo renderNavLink($lastPageLink, 'navend', 'Letzte Seite', $isCurrentPageLatest); ?>
