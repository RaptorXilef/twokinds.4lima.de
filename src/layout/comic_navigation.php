<?php
/**
 * Verwaltet und zeigt die Comic-Navigationslinks an.
 * Ermittelt die Links zur vorherigen, nächsten, ersten und letzten Seite.
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
        // Erste Seite
        $firstPage = $comicKeys[0] . '.php';

        // Letzte Seite
        $lastPage = end($comicKeys) . '.php'; // Nutze den letzten Schlüssel

        // Vorherige Seite
        if ($currentIndex > 0) {
            $prevPage = $comicKeys[$currentIndex - 1] . '.php';
        }

        // Nächste Seite
        if ($currentIndex < count($comicKeys) - 1) {
            $nextPage = $comicKeys[$currentIndex + 1] . '.php';
        } else {
            // Wenn es die letzte Seite ist, soll der "Nächste Seite"-Link auf die aktuelle Seite zeigen,
            // oder leer bleiben, je nach gewünschtem Verhalten.
            // Hier leer gelassen, damit der Link deaktiviert wird.
            $nextPage = '';
        }
    }
}
?>

<div class='comicnav'>
    <a href="<?php echo !empty($firstPage) ? $firstPage : '#'; ?>" class="navarrow navbegin <?php echo empty($firstPage) ? 'disabled' : ''; ?>">
        <span class="nav-wrapper">Erste Seite</span>
    </a>
    <a href="<?php echo !empty($prevPage) ? $prevPage : '#'; ?>" class="navarrow navprev <?php echo empty($prevPage) ? 'disabled' : ''; ?>">
        <span class="nav-wrapper">Vorherige Seite</span>
    </a>
    <a href="/archiv.php" class="navarchive">
        <span class="nav-wrapper">Archiv</span>
    </a>
    <a href="<?php echo !empty($nextPage) ? $nextPage : '#'; ?>" class="navarrow navnext <?php echo empty($nextPage) ? 'disabled' : ''; ?>">
        <span class="nav-wrapper">Nächste Seite</span>
    </a>
    <a href="<?php echo !empty($lastPage) ? $lastPage : '#'; ?>" class="navarrow navend <?php echo empty($lastPage) ? 'disabled' : ''; ?>">
        <span class="nav-wrapper">Letzte Seite</span>
    </a>
</div>

<div class="below-nav">
    <i class="jsdep">Sie können auch mit den Pfeiltasten oder den Tasten J und K navigieren.</i>
    <?php /*
    <p class="permalink">
        <a href="/feed.xml" class="rss">RSS</a>
        &middot; <a href="/comic/1082_16thanniversary/">Permalink</a>
    </p>
    */ ?>
</div>