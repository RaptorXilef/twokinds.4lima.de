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
 *
 * @param array $comicData Assoziatives Array aller Comic-Metadaten, sortiert nach ID.
 * @param string $currentComicId Die ID (Datum) des aktuell angezeigten Comics.
 * @param bool $isCurrentPageLatest Optional, ob die aktuelle Seite der allerneueste Comic ist (standardmäßig false).
 */

// Lade die Hilfsfunktion zum Rendern der Navigationsbuttons.
// 'require_once' stellt sicher, dass die Funktion nur einmal deklariert wird,
// auch wenn diese Datei mehrfach inkludiert wird.
require_once __DIR__ . '/../components/nav_link_helper.php';

// Initialisiere Navigations-Variablen
$prevPage = '';
$nextPage = '';
$firstPageLink = ''; // Wird der Link oder '#' sein
$lastPageLink = './'; // Die letzte Seite ist immer die index.php (neuester Comic)

// Standardwerte für die Flags, falls nicht von der aufrufenden Seite gesetzt.
$isCurrentPageLatest = isset($isCurrentPageLatest) ? $isCurrentPageLatest : false;
$isCurrentPageFirst = false; // Flag, ob die aktuelle Seite der allererste Comic ist.

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
        $firstPageLink = $isCurrentPageFirst ? '#' : $comicKeys[0] . '.php';

        // Link für "Vorherige Seite": Wenn es nicht die erste Seite ist, nimm die vorherige Seite.
        if ($currentIndex > 0) {
            $prevPage = $comicKeys[$currentIndex - 1] . '.php';
        } else {
            // Wenn es die erste Seite ist, gibt es keine vorherige Seite, Link deaktivieren.
            $prevPage = '';
        }

        // Link für "Nächste Seite": Wenn es die neueste Seite ist (index.php-Szenario)
        // oder wenn es die letzte Seite in der Comic-Liste ist, deaktiviere den Link.
        if ($isCurrentPageLatest || ($currentIndex === count($comicKeys) - 1)) {
            $nextPage = '';
        } else {
            $nextPage = $comicKeys[$currentIndex + 1] . '.php';
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
