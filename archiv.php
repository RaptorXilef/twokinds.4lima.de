<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * Dies ist die Archivseite der TwoKinds-Webseite.
 * Sie zeigt die Comics nach Kapiteln gruppiert an und lädt Informationen
 * aus archive_chapters.json und comic_var.json.
 * Die Seite verwendet ein aufklappbares Sektionsdesign mit Lazy Loading für Thumbnails.
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
$scriptName = $_SERVER['SCRIPT_NAME'];
// Ermittle das Basisverzeichnis des Skripts relativ zum Document Root
$scriptDir = rtrim(dirname($scriptName), '/');

// Wenn das Skript im Root-Verzeichnis liegt, ist $scriptDir leer.
// In diesem Fall ist $baseUrl einfach das Protokoll und der Host.
// Andernfalls ist es Protokoll + Host + Skriptverzeichnis.
$baseUrl = $protocol . $host . ($scriptDir === '' ? '/' : $scriptDir . '/');

if ($debugMode)
    error_log("DEBUG: Basis-URL in archiv.php: " . $baseUrl);

// Setze Parameter für den Header. Der Seitentitel wird im Header automatisch mit Präfix versehen.
$pageTitle = 'Archiv';
$pageHeader = 'TwoKinds Archiv'; // Dieser Wert wird im Hauptinhaltsbereich angezeigt.
$siteDescription = 'Das Archiv der TwoKinds Comics, fanübersetzt auf Deutsch.';
$robotsContent = 'index, follow'; // Diese Seite soll von Suchmaschinen indexiert werden

// Setze den Seitentyp, damit der Header die richtigen Skripte lädt
$current_page_type = 'archive';

// Pfade zu den JSON-Dateien
$archiveChaptersJsonPath = __DIR__ . '/src/config/archive_chapters.json';
$comicVarJsonPath = __DIR__ . '/src/config/comic_var.json';
$placeholderImagePath = 'assets/comic_thumbnails/placeholder.jpg'; // Pfad zum Platzhalterbild

// Lade die Archivkapitel-Daten
function loadArchiveChapters(string $path, bool $debugMode): array
{
    if (!file_exists($path) || filesize($path) === 0) {
        if ($debugMode)
            error_log("DEBUG: Archivkapitel-Datei nicht gefunden oder leer: " . $path);
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($debugMode)
            error_log("Fehler beim Dekodieren von archive_chapters.json: " . json_last_error_msg());
        return [];
    }
    // Sortiere nach chapterId, um Konsistenz zu gewährleisten
    usort($data, function ($a, $b) {
        return ($a['chapterId'] ?? 0) <=> ($b['chapterId'] ?? 0);
    });
    if ($debugMode)
        error_log("DEBUG: Archivkapitel erfolgreich geladen.");
    return $data;
}

// Lade die Comic-Variablen-Daten
function loadComicVar(string $path, bool $debugMode): array
{
    if (!file_exists($path) || filesize($path) === 0) {
        if ($debugMode)
            error_log("DEBUG: Comic-Variablen-Datei nicht gefunden oder leer: " . $path);
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($debugMode)
            error_log("Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        return [];
    }
    if ($debugMode)
        error_log("DEBUG: Comic-Variablen erfolgreich geladen.");
    return $data;
}

$archiveChapters = loadArchiveChapters($archiveChaptersJsonPath, $debugMode);
$comicData = loadComicVar($comicVarJsonPath, $debugMode);

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
            'chapterId' => (int) $chId,
            'title' => 'Dieses Kapitel wird im Moment bearbeitet.',
            'description' => 'Die Informationen zu diesem Kapitel werden noch erstellt. Bitte besuche diesen Teil später noch einmal.'
        ];
        if ($debugMode)
            error_log("DEBUG: Fehlendes Kapitel {$chId} hinzugefügt.");
    }
}

// Sortiere die Kapitel erneut nach chapterId, falls neue hinzugefügt wurden
usort($archiveChapters, function ($a, $b) {
    return ($a['chapterId'] ?? 0) <=> ($b['chapterId'] ?? 0);
});
if ($debugMode)
    error_log("DEBUG: Kapitel nach ID sortiert.");


// === WICHTIG: Entferne die explizite JS-Einbindung hier! ===
// Die JavaScript-Dateien werden nun über den header.php geladen,
// basierend auf dem oben gesetzten $current_page_type.
$additionalScripts = ''; // Leere diesen String, damit keine doppelten Skripte geladen werden.

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
            $chapterTitle = !empty(trim(strip_tags($chapter['title'] ?? '', '<b><i><u><p><br>'))) ? $chapter['title'] : 'Dieses Kapitel wird im Moment bearbeitet.';
            $chapterDescription = !empty(trim(strip_tags($chapter['description'] ?? '', '<b><i><u><p><br>'))) ? $chapter['description'] : 'Die Informationen zu diesem Kapitel werden noch erstellt. Bitte besuche diesen Teil später noch einmal.';
            if ($debugMode)
                error_log("DEBUG: Verarbeite Kapitel ID: {$chapterId} mit Titel: {$chapterTitle}");
            ?>
            <section class="chapter collapsible-section" data-ch-id="<?php echo htmlspecialchars($chapterId); ?>">
                <h2 class="collapsible-header"><?php echo $chapterTitle; ?><span class="arrow-left jsdep"></span></h2>
                <div class="collapsible-content">
                    <p><?php echo $chapterDescription; ?></p>
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
                                $comicImagePath = "assets/comic_thumbnails/{$comicId}.jpg";
                                // Überprüfe, ob die Datei auf dem Server existiert
                                $displayImagePath = file_exists(__DIR__ . '/' . $comicImagePath) ? $baseUrl . $comicImagePath : $baseUrl . $placeholderImagePath;
                                $comicPagePath = $baseUrl . 'comic/' . htmlspecialchars($comicId) . '.php'; // Link zur Comic-Seite
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