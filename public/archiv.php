<?php
/**
 * Dies ist die Archivseite der TwoKinds-Webseite. (OPTIMIERTE VERSION)
 * Sie zeigt die Comics nach Kapiteln gruppiert an und lädt Informationen
 * aus archive_chapters.json und comic_var.json.
 * Die Thumbnail-Pfade werden aus einer vor-generierten Cache-Datei (comic_image_cache.json) gelesen,
 * um die Serverlast drastisch zu reduzieren.
 * 
 * @file      ROOT/public/archiv.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.0
 * @since     1.0.0 Verarbeitung der Daten aus archive_chapters.json und comic_var.json.
 * @since     1.1.0 Anpassung an versionalisierte comic_var.json (Schema v2).
 * @since     1.2.0 Umstellung auf globale Pfad-Konstanten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konstanten erst lädt.
require_once __DIR__ . '/../src/components/public_init.php';

// === 2. LADE-SKRIPTE & DATEN ===
$placeholderImagePath = 'assets/comic_thumbnails/placeholder.jpg';

// Funktion zum Laden von JSON-Dateien
function loadJsonFile(string $path, bool $debugMode, string $fileName): ?array
{
    if (!file_exists($path) || filesize($path) === 0) {
        if ($debugMode)
            error_log("DEBUG: {$fileName} nicht gefunden oder leer: " . $path);
        return null;
    }
    $content = file_get_contents($path);
    if ($content === false) {
        if ($debugMode)
            error_log("Fehler beim Lesen von {$fileName}: " . $path);
        return null;
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($debugMode)
            error_log("Fehler beim Dekodieren von {$fileName}: " . json_last_error_msg());
        return null;
    }
    if ($debugMode)
        error_log("DEBUG: {$fileName} erfolgreich geladen.");
    return $data;
}

$archiveChapters = loadJsonFile(ARCHIVE_CHAPTERS_JSON, $debugMode, ARCHIVE_CHAPTERS_JSON_FILE) ?? [];

// *** ANPASSUNG FÜR V2-SCHEMA ***
$rawComicData = loadJsonFile(COMIC_VAR_JSON, $debugMode, COMIC_VAR_JSON_FILE);
$comicData = [];
if (is_array($rawComicData)) {
    if (isset($rawComicData['schema_version']) && $rawComicData['schema_version'] >= 2 && isset($rawComicData['comics'])) {
        $comicData = $rawComicData['comics']; // v2 format
    } else {
        $comicData = $rawComicData; // v1 format (fallback)
    }
}
// *** ENDE ANPASSUNG ***

$imageCache = loadJsonFile(COMIC_IMAGE_CACHE_JSON, $debugMode, COMIC_IMAGE_CACHE_JSON_FILE) ?? [];


if ($debugMode && empty($imageCache)) {
    error_log("WARNUNG: Der Bild-Cache (" . COMIC_IMAGE_CACHE_JSON_FILE . ") ist leer oder konnte nicht geladen werden. Führe build_image_cache.php im Admin-Bereich aus.");
}

// === 3. DATENVERARBEITUNG & SORTIERUNG ===
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
foreach (array_keys($comicsByChapter) as $chId) {
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
        return strnatcmp((string) $valA[1], (string) $valB[1]);
    return $valA[1] <=> $valB[1];
});

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Archiv';
$pageHeader = 'Archiv';
$siteDescription = 'Das vollständige Archiv aller TwoKinds-Comics, übersichtlich nach Kapiteln geordnet. Finde schnell und einfach deine Lieblingsseite.';
$robotsContent = 'index, follow';
$archiveJsPathOnServer = PUBLIC_JS_ASSETS_PATH . DIRECTORY_SEPARATOR . 'archive.js';
$archiveJsWebUrl = $baseUrl . 'src/layout/js/archive.js';
$cacheBuster = file_exists($archiveJsPathOnServer) ? '?c=' . filemtime($archiveJsPathOnServer) : '';
$additionalScripts = '<script nonce="' . htmlspecialchars($nonce) . '" type="text/javascript" src="' . htmlspecialchars($archiveJsWebUrl . $cacheBuster) . '"></script>';


// === 5. HEADER EINBINDEN (Jetzt mit Konstante) ===
require_once TEMPLATE_HEADER;
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
                                $foundImagePath = $imageCache[$comicId]['thumbnails'] ?? null;
                                $displayImagePath = $foundImagePath ? $baseUrl . ltrim($foundImagePath, './') : $baseUrl . $placeholderImagePath;
                                $comicPagePath = $baseUrl . 'comic/' . htmlspecialchars($comicId)/* . '.php'*/ ; // Kommentar entfernen, wenn htaccess nicht auf PHP-Dateien zeigt
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
// Binde den gemeinsamen Footer ein (Jetzt mit Konstante).
require_once TEMPLATE_FOOTER;
?>