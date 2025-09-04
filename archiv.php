<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * Dies ist die Archivseite der TwoKinds-Webseite. (OPTIMIERTE VERSION)
 * Sie zeigt die Comics nach Kapiteln gruppiert an und lädt Informationen
 * aus archive_chapters.json und comic_var.json.
 * Die Thumbnail-Pfade werden aus einer vor-generierten Cache-Datei gelesen,
 * um die Serverlast drastisch zu reduzieren.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: archiv.php wird geladen.");

// === Dynamische Basis-URL Bestimmung für die gesamte Anwendung ===
// Diese Logik wird hier dupliziert, um sicherzustellen, dass $baseUrl
// verfügbar ist, bevor $additionalScripts und $additionalHeadContent definiert werden,
// die ihrerseits im header.php verwendet werden.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$baseUrl = $protocol . $host . ($scriptDir === '' ? '/' : $scriptDir . '/');

if ($debugMode)
    error_log("DEBUG: Basis-URL in archiv.php: " . $baseUrl);


// === LADE CACHE UND DATEN ===
$archiveChaptersJsonPath = __DIR__ . '/src/config/archive_chapters.json';
$comicVarJsonPath = __DIR__ . '/src/config/comic_var.json';
$archiveCacheJsonPath = __DIR__ . '/src/config/archive_cache.json'; // NEU
$placeholderImagePath = 'assets/comic_thumbnails/placeholder.jpg';

// Funktion zum Laden von JSON-Dateien
function loadJsonFile(string $path, bool $debugMode, string $fileName): array
{
    if (!file_exists($path) || filesize($path) === 0) {
        if ($debugMode)
            error_log("DEBUG: {$fileName} nicht gefunden oder leer: " . $path);
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($debugMode)
            error_log("Fehler beim Dekodieren von {$fileName}: " . json_last_error_msg());
        return [];
    }
    if ($debugMode)
        error_log("DEBUG: {$fileName} erfolgreich geladen.");
    return $data;
}

$archiveChapters = loadJsonFile($archiveChaptersJsonPath, $debugMode, 'archive_chapters.json');
$comicData = loadJsonFile($comicVarJsonPath, $debugMode, 'comic_var.json');
$thumbnailPaths = loadJsonFile($archiveCacheJsonPath, $debugMode, 'archive_cache.json'); // NEU

if ($debugMode && empty($thumbnailPaths)) {
    error_log("WARNUNG: Der Thumbnail-Cache (archive_cache.json) ist leer oder konnte nicht geladen werden. Führe build_archive_cache.php aus.");
}


// === DATENVERARBEITUNG ===
// Erstelle eine Map von chapterId zu Comic-IDs aus comic_var.json
$comicsByChapter = [];
foreach ($comicData as $comicId => $details) {
    $chapterId = $details['chapter'] ?? null;
    if ($chapterId !== null) {
        if (!isset($comicsByChapter[$chapterId])) {
            $comicsByChapter[$chapterId] = [];
        }
        $comicsByChapter[$chapterId][$comicId] = $details; // Speichere den gesamten Comic-Datensatz
    }
}
if ($debugMode)
    error_log("DEBUG: Comics nach Kapiteln gruppiert.");

// Füge fehlende Kapitel aus comic_var.json hinzu, falls sie nicht in archive_chapters.json sind
$existingChapterIds = array_column($archiveChapters, 'chapterId');
foreach ($comicsByChapter as $chId => $comics) {
    if (!in_array($chId, $existingChapterIds)) {
        $archiveChapters[] = [
            'chapterId' => (string) $chId,
            'title' => '',
            'description' => 'Die Informationen zu diesem Kapitel werden noch erstellt.'
        ];
        if ($debugMode)
            error_log("DEBUG: Fehlendes Kapitel {$chId} hinzugefügt.");
    }
}

// FILTERLOGIK: Entferne Kapitel mit leerer chapterId, wenn keine zugehörigen Comics vorhanden sind.
$archiveChapters = array_filter($archiveChapters, function ($chapter) use ($comicsByChapter, $debugMode) {
    $chapterId = $chapter['chapterId'] ?? '';
    if ($chapterId === '' && empty($comicsByChapter[$chapterId] ?? [])) {
        if ($debugMode)
            error_log("DEBUG: Leeres Kapitel ohne Comics wird entfernt: " . json_encode($chapter));
        return false;
    }
    return true;
});
if ($debugMode)
    error_log("DEBUG: Kapitel nach Filterung aktualisiert.");


// === KORRIGIERTE SORTIERLOGIK (Mehrstufig für numerische, gemischte und leere IDs) ===
// Funktion, um den effektiven Sortierwert für ein Kapitel zu erhalten
// Gibt ein Array zurück: [Priorität, Sortier-Schlüssel]
// Prioritäten:
// 0: Numerische chapterId (z.B. "1", "10", "5.5", "6,1" wird zu "6.1") - sortiert numerisch
// 1: Andere String-chapterId (z.B. "Chapter X") - sortiert natürlich als String
// 2: Leere chapterId ("") - sortiert ganz ans Ende
function getChapterSortValue(array $chapter): array
{
    $rawChapterId = $chapter['chapterId'] ?? '';

    // Priorität 2: Leere chapterId ("") - sortiert ganz ans Ende
    if ($rawChapterId === '')
        return [2, PHP_INT_MAX]; // Höchste Priorität, um ans Ende zu gehen

    // Ersetze Komma durch Punkt für die numerische Prüfung, falls vorhanden
    $numericCheckId = str_replace(',', '.', $rawChapterId);
    // Priorität 0: Numerische chapterId (z.B. "1", "10", "5.5", "6,1" wird zu "6.1")
    if (is_numeric($numericCheckId))
        return [0, (float) $numericCheckId]; // Niedrigste Priorität, sortiert numerisch
    // Priorität 1: Andere String-chapterId (z.B. "Chapter X")
    return [1, $rawChapterId]; // Mittlere Priorität, sortiert natürlich als String
}

// Sortiere die Kapitel nach ihrem effektiven Sortierwert (mehrstufig)
usort($archiveChapters, function ($a, $b) {
    $valA = getChapterSortValue($a);
    $valB = getChapterSortValue($b);
    // Vergleiche zuerst nach Priorität
    if ($valA[0] !== $valB[0])
        return $valA[0] <=> $valB[0];
    // Wenn Prioritäten gleich sind, vergleiche nach dem Sortier-Schlüssel
    if ($valA[0] === 1) // Beide sind andere Strings (Priorität 1)
        return strnatcmp($valA[1], $valB[1]); // Natürliche String-Sortierung
    return $valA[1] <=> $valB[1];
});
if ($debugMode)
    error_log("DEBUG: Kapitel nach ID und Titelstatus sortiert.");


// === HEADER-PARAMETER ===
$pageTitle = 'Archiv';
$pageHeader = 'TwoKinds auf Deutsch - Archiv';
$siteDescription = 'Das Archiv der TwoKinds Comics, fanübersetzt auf Deutsch.';
$robotsContent = 'index, follow';
$additionalScripts = '<script type="text/javascript" src="' . htmlspecialchars($baseUrl) . 'src/layout/js/archive.js?c=' . filemtime(__DIR__ . '/src/layout/js/archive.js') . '"></script>';
// Zusätzliche Styles für das Archiv
// Hier wird kein Font Awesome mehr geladen, da das Original-Design eigene Pfeile hat.
$additionalHeadContent = '';

// Binde den gemeinsamen Header ein. Dies muss vor jeglichem HTML-Output geschehen.
include __DIR__ . '/src/layout/header.php';

if ($debugMode)
    error_log("DEBUG: Header in archiv.php eingebunden.");
?>

<article>
    <header>
        <h1 class="page-header">TwoKinds Archiv</h1>
    </header>
    <div class="instructions jsdep">Klicken Sie auf eine Kapitelüberschrift, um das Kapitel zu erweitern.</div>

    <?php if (empty($archiveChapters)): ?>
        <p>Es sind noch keine Archivkapitel vorhanden.</p>
        <?php if ($debugMode)
            error_log("DEBUG: Keine Archivkapitel zum Anzeigen."); ?>
    <?php else: ?>
        <?php foreach ($archiveChapters as $chapter):
            $chapterId = $chapter['chapterId'] ?? 'N/A';
            // Der Titel wird hier anhand der Logik aus dem Originalcode ermittelt
            $chapterTitle = !empty(trim(strip_tags($chapter['title'] ?? ''))) ? $chapter['title'] : 'Dieses Kapitel wird im Moment bearbeitet.';
            $chapterDescription = $chapter['description'] ?? 'Die Informationen zu diesem Kapitel werden noch erstellt.';
            if ($debugMode)
                error_log("DEBUG: Verarbeite Kapitel ID: {$chapterId} mit Titel: {$chapterTitle}");
            ?>
            <section class="chapter collapsible-section" data-ch-id="<?php echo htmlspecialchars($chapterId); ?>">
                <h2 class="collapsible-header"><?php echo $chapterTitle; ?><span class="arrow-left jsdep"></span></h2>
                <!-- Der P-Tag für die Beschreibung ist jetzt direkt unter h2 und außerhalb von .collapsible-content -->
                <p><?php echo $chapterDescription; ?></p>
                <div class="collapsible-content">
                    <aside class="chapter-links">
                        <?php
                        // Hole alle Comics, die diesem Kapitel zugeordnet sind
                        $comicsForThisChapter = $comicsByChapter[$chapterId] ?? [];
                        // Sortiere die Comics nach ihrer ID (Datum) aufsteigend
                        ksort($comicsForThisChapter);

                        if (empty($comicsForThisChapter)): ?>
                            <p>Für dieses Kapitel sind noch keine Comics verfügbar.</p>
                            <?php if ($debugMode)
                                error_log("DEBUG: Keine Comics für Kapitel {$chapterId} gefunden."); ?>
                        <?php else: ?>
                            <?php foreach ($comicsForThisChapter as $comicId => $comicDetails):
                                // === MODIFIZIERT: Bildpfad aus Cache lesen ===
                                $foundImagePath = $thumbnailPaths[$comicId] ?? null;
                                $displayImagePath = $foundImagePath ? $baseUrl . $foundImagePath : $baseUrl . $placeholderImagePath;

                                $comicPagePath = $baseUrl . 'comic/' . htmlspecialchars($comicId) . '.php';
                                $comicDate = DateTime::createFromFormat('Ymd', $comicId);
                                $displayDate = $comicDate ? $comicDate->format('d.m.Y') : 'Unbekanntes Datum';

                                if ($debugMode)
                                    error_log("DEBUG: Zeige Comic {$comicId} für Kapitel {$chapterId}. Bildpfad: {$displayImagePath}");
                                ?>
                                <a href="<?php echo $comicPagePath; ?>" title="Comic vom <?php echo htmlspecialchars($displayDate); ?>">
                                    <span><?php echo htmlspecialchars($displayDate); ?></span>
                                    <img class="jsdep"
                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
                                        data-src="<?php echo htmlspecialchars($displayImagePath); ?>"
                                        alt="Comic vom <?php echo htmlspecialchars($displayDate); ?>">
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </aside>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</article>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . '/src/layout/footer.php';
if ($debugMode)
    error_log("DEBUG: Footer in archiv.php eingebunden.");
?>