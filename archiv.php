<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * Dies ist die Archivseite der TwoKinds-Webseite. (OPTIMIERTE VERSION)
 * Sie zeigt die Comics nach Kapiteln gruppiert an und lädt Informationen
 * aus archive_chapters.json und comic_var.json.
 * Die Thumbnail-Pfade werden aus einer vor-generierten Cache-Datei (comic_image_cache.json) gelesen,
 * um die Serverlast drastisch zu reduzieren.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: archiv.php wird geladen.");

// === Dynamische Basis-URL Bestimmung ===
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$baseUrl = $protocol . $host . ($scriptDir === '' ? '/' : $scriptDir . '/');

if ($debugMode)
    error_log("DEBUG: Basis-URL in archiv.php: " . $baseUrl);


// === LADE CACHE UND DATEN ===
$archiveChaptersJsonPath = __DIR__ . '/src/config/archive_chapters.json';
$comicVarJsonPath = __DIR__ . '/src/config/comic_var.json';
// *** NEU: Pfad zur zentralen Bild-Cache-Datei ***
$imageCacheJsonPath = __DIR__ . '/src/config/comic_image_cache.json';
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
$imageCache = loadJsonFile($imageCacheJsonPath, $debugMode, 'comic_image_cache.json');

if ($debugMode && empty($imageCache)) {
    // *** Warnmeldung verweist auf die korrekte Datei ***
    error_log("WARNUNG: Der Bild-Cache (comic_image_cache.json) ist leer oder konnte nicht geladen werden. Führe build_image_cache.php im Admin-Bereich aus.");
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
        $comicsByChapter[$chapterId][$comicId] = $details;
    }
}
if ($debugMode)
    error_log("DEBUG: Comics nach Kapiteln gruppiert.");

// Füge fehlende Kapitel aus comic_var.json hinzu
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

// FILTERLOGIK: Entferne Kapitel mit leerer chapterId ohne Comics
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


// === SORTIERLOGIK (Mehrstufig) ===
function getChapterSortValue(array $chapter): array
{
    $rawChapterId = $chapter['chapterId'] ?? '';
    if ($rawChapterId === '')
        return [2, PHP_INT_MAX];
    $numericCheckId = str_replace(',', '.', $rawChapterId);
    if (is_numeric($numericCheckId))
        return [0, (float) $numericCheckId];
    return [1, $rawChapterId];
}

usort($archiveChapters, function ($a, $b) {
    $valA = getChapterSortValue($a);
    $valB = getChapterSortValue($b);
    if ($valA[0] !== $valB[0])
        return $valA[0] <=> $valB[0];
    if ($valA[0] === 1)
        return strnatcmp($valA[1], $valB[1]);
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
$additionalHeadContent = '';

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
    <?php else: ?>
        <?php foreach ($archiveChapters as $chapter):
            $chapterId = $chapter['chapterId'] ?? 'N/A';
            $chapterTitle = !empty(trim(strip_tags($chapter['title'] ?? ''))) ? $chapter['title'] : 'Dieses Kapitel wird im Moment bearbeitet.';
            $chapterDescription = $chapter['description'] ?? 'Die Informationen zu diesem Kapitel werden noch erstellt.';
            ?>
            <section class="chapter collapsible-section" data-ch-id="<?php echo htmlspecialchars($chapterId); ?>">
                <h2 class="collapsible-header"><?php echo $chapterTitle; ?><span class="arrow-left jsdep"></span></h2>
                <p><?php echo $chapterDescription; ?></p>
                <div class="collapsible-content">
                    <aside class="chapter-links">
                        <?php
                        $comicsForThisChapter = $comicsByChapter[$chapterId] ?? [];
                        ksort($comicsForThisChapter);

                        if (empty($comicsForThisChapter)): ?>
                            <p>Für dieses Kapitel sind noch keine Comics verfügbar.</p>
                        <?php else: ?>
                            <?php foreach ($comicsForThisChapter as $comicId => $comicDetails):
                                // *** Logik angepasst, um den Thumbnail-Pfad aus der neuen Cache-Struktur zu lesen ***
                                $foundImagePath = $imageCache[$comicId]['thumbnails'] ?? null;

                                $displayImagePath = $foundImagePath ? $baseUrl . $foundImagePath : $baseUrl . $placeholderImagePath;

                                $comicPagePath = $baseUrl . 'comic/' . htmlspecialchars($comicId) . '.php';
                                $comicDate = DateTime::createFromFormat('Ymd', $comicId);
                                $displayDate = $comicDate ? $comicDate->format('d.m.Y') : 'Unbekanntes Datum';
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
include __DIR__ . '/src/layout/footer.php';
if ($debugMode)
    error_log("DEBUG: Footer in archiv.php eingebunden.");
?>