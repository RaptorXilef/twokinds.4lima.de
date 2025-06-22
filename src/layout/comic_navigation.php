<?php
/**
 * Verwaltet und zeigt die Comic-Navigationslinks an.
 * Ermittelt die Links zur vorherigen, nächsten, ersten und letzten Seite.
 *
 * Diese Datei generiert NUR die Navigations-Links (<a>-Tags) OHNE den umgebenden Div-Container.
 * Der Container wird in der aufrufenden Comic-Seite (z.B. 20250312.php) hinzugefügt.
 *
 * Benötigt das $comicData Array und $currentComicId.
 *
 * @param array $comicData Asssoziatives Array aller Comic-Metadaten, sortiert nach ID.
 * @param string $currentComicId Die ID (Datum) des aktuell angezeigten Comics.
 */

// Initialisiere Navigations-Variablen
$prevPage = '';
$nextPage = '';
$firstPage = '';
$lastPage = '';

// Wenn Comic-Daten verfügbar sind
if (!empty($comicData)) {
    $comicKeys = array_keys($comicData);
    $currentIndex = array_search($currentComicId, $comicKeys);

    if ($currentIndex !== false) {
        // Erste Seite: Nimm den ersten Schlüssel im sortierten Array
        $firstPage = $comicKeys[0] . '.php';

        // Letzte Seite: Nimm den letzten Schlüssel im sortierten Array
        $lastPage = end($comicKeys) . '.php';

        // Vorherige Seite
        if ($currentIndex > 0) {
            $prevPage = $comicKeys[$currentIndex - 1] . '.php';
        }

        // Nächste Seite
        if ($currentIndex < count($comicKeys) - 1) {
            $nextPage = $comicKeys[$currentIndex + 1] . '.php';
        } else {
            // Wenn es die letzte Seite ist, gibt es keine nächste Seite.
            $nextPage = '';
        }
    }
}
?>
    <a href="<?php echo !empty($firstPage) ? htmlspecialchars($firstPage) : '#'; ?>" class="navarrow navbegin <?php echo empty($firstPage) ? 'disabled' : ''; ?>">
        <span class="nav-wrapper"><span class="nav-text">Erste Seite</span></span>
    </a>
    <a href="<?php echo !empty($prevPage) ? htmlspecialchars($prevPage) : '#'; ?>" class="navarrow navprev <?php echo empty($prevPage) ? 'disabled' : ''; ?>">
        <span class="nav-wrapper"><span class="nav-text">Vorherige Seite</span></span>
    </a>
    <a href="/archiv.php" class="navarchive">
        <span class="nav-wrapper">Archiv</span>
    </a>
    <a href="<?php echo !empty($nextPage) ? htmlspecialchars($nextPage) : '#'; ?>" class="navarrow navnext <?php echo empty($nextPage) ? 'disabled' : ''; ?>">
        <span class="nav-wrapper"><span class="nav-text">Nächste Seite</span></span>
    </a>
    <a href="<?php echo !empty($lastPage) ? htmlspecialchars($lastPage) : '#'; ?>" class="navarrow navend <?php echo empty($lastPage) ? 'disabled' : ''; ?>">
        <span class="nav-wrapper"><span class="nav-text">Letzte Seite</span></span>
    </a>
