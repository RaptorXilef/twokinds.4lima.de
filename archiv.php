<?php
/**
 * Dies ist die Archivseite der TwoKinds-Webseite.
 * Sie zeigt die Comics nach Kapiteln gruppiert an und lädt Informationen
 * aus archive_chapters.json und comic_var.json.
 * Die Seite verwendet ein aufklappbares Sektionsdesign mit Lazy Loading für Thumbnails.
 */

// Setze Parameter für den Header. Der Seitentitel wird im Header automatisch mit Präfix versehen.
$pageTitle = 'Archiv';
$pageHeader = 'TwoKinds Archiv'; // Dieser Wert wird im Hauptinhaltsbereich angezeigt.
$siteDescription = 'Das Archiv der TwoKinds Comics, fanübersetzt auf Deutsch.';
$robotsContent = 'index, follow'; // Diese Seite soll von Suchmaschinen indexiert werden

// Pfade zu den JSON-Dateien
$archiveChaptersJsonPath = __DIR__ . '/src/components/archive_chapters.json';
$comicVarJsonPath = __DIR__ . '/src/components/comic_var.json';
$placeholderImagePath = 'assets/comic_thumbnails/placeholder.jpg'; // Pfad zum Platzhalterbild

// Lade die Archivkapitel-Daten
function loadArchiveChapters(string $path): array {
    if (!file_exists($path) || filesize($path) === 0) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von archive_chapters.json: " . json_last_error_msg());
        return [];
    }
    // Sortiere nach chapterId, um Konsistenz zu gewährleisten
    usort($data, function($a, $b) {
        return ($a['chapterId'] ?? 0) <=> ($b['chapterId'] ?? 0);
    });
    return $data;
}

// Lade die Comic-Variablen-Daten
function loadComicVar(string $path): array {
    if (!file_exists($path) || filesize($path) === 0) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        return [];
    }
    return $data;
}

$archiveChapters = loadArchiveChapters($archiveChaptersJsonPath);
$comicData = loadComicVar($comicVarJsonPath);

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

// Füge fehlende Kapitel aus comic_var.json hinzu, falls sie nicht in archive_chapters.json sind
$existingChapterIds = array_column($archiveChapters, 'chapterId');
foreach ($comicsByChapter as $chId => $comics) {
    if (!in_array($chId, $existingChapterIds)) {
        $archiveChapters[] = [
            'chapterId' => (int)$chId,
            'title' => 'Dieses Kapitel wird im Moment bearbeitet.',
            'description' => 'Die Informationen zu diesem Kapitel werden noch erstellt. Bitte besuche diesen Teil später noch einmal.'
        ];
    }
}

// Sortiere die Kapitel erneut nach chapterId, falls neue hinzugefügt wurden
usort($archiveChapters, function($a, $b) {
    return ($a['chapterId'] ?? 0) <=> ($b['chapterId'] ?? 0);
});


// JavaScript für aufklappbare Sektionen und Lazy Loading
$additionalScripts = <<<EOT
<script>
document.addEventListener("DOMContentLoaded", function() {
    const sections = document.querySelectorAll(".chapter.collapsible-section");
    const placeholderImage = "{$baseUrl}assets/comic_thumbnails/placeholder.jpg"; // Verwende baseUrl für den Platzhalter

    // Funktion zum Laden der Bilder in einer Sektion
    function loadImages(section) {
        section.querySelectorAll("img.lazyload").forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.classList.remove("lazyload");
                img.classList.add("loaded");
                img.removeAttribute("data-src");
            }
        });
    }

    // Funktion zum Einklappen aller Sektionen außer der aktuellen
    function collapseOtherSections(currentSection = null) {
        sections.forEach(section => {
            if (section !== currentSection && section.classList.contains("expanded")) {
                section.classList.remove("expanded");
                const icon = section.querySelector(".collapsible-header i");
                if (icon) {
                    icon.classList.remove("fa-chevron-down");
                    icon.classList.add("fa-chevron-right");
                }
                // Optional: Bilder entladen, um Speicher zu sparen, wenn eingeklappt
                // section.querySelectorAll("img.loaded").forEach(img => {
                //     img.dataset.src = img.src;
                //     img.src = "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
                //     img.classList.remove("loaded");
                //     img.classList.add("lazyload");
                // });
            }
        });
    }

    sections.forEach(section => {
        const header = section.querySelector(".collapsible-header");
        const icon = header.querySelector("i"); // Das Icon innerhalb des Headers
        const content = section.querySelector(".collapsible-content");

        // Setze den initialen Zustand: alle eingeklappt
        section.classList.remove("expanded");
        if (icon) {
            icon.classList.remove("fa-chevron-down");
            icon.classList.add("fa-chevron-right");
        }
        content.style.maxHeight = null; // Setze max-height zurück, damit die Transition funktioniert

        header.addEventListener("click", function() {
            const isExpanded = section.classList.contains("expanded");

            // Klappe alle anderen Sektionen ein
            collapseOtherSections(section);

            // Toggle die aktuelle Sektion
            if (isExpanded) {
                section.classList.remove("expanded");
                if (icon) {
                    icon.classList.remove("fa-chevron-down");
                    icon.classList.add("fa-chevron-right");
                }
                content.style.maxHeight = null; // Setze max-height zurück
            } else {
                section.classList.add("expanded");
                if (icon) {
                    icon.classList.remove("fa-chevron-right");
                    icon.classList.add("fa-chevron-down");
                }
                // Setze max-height auf die Scrollhöhe des Inhalts für die Transition
                content.style.maxHeight = content.scrollHeight + "px";
                loadImages(section); // Lade Bilder, wenn Sektion ausgeklappt wird
            }
        });

        // Event Listener für Transition End, um max-height zu entfernen
        content.addEventListener("transitionend", function() {
            if (section.classList.contains("expanded")) {
                content.style.maxHeight = "none"; // Entferne max-height, um vollständiges Scrollen zu ermöglichen
            }
        });
    });

    // Lazy Loading für sichtbare Bilder beim Laden der Seite (falls eine Sektion initial offen wäre)
    // Da alle standardmäßig eingeklappt sind, wird dies erst beim Ausklappen relevant.
    // Aber der Observer ist gut für zukünftige Anpassungen oder falls doch mal eine Sektion offen ist.
    const lazyLoadThrottleTimeout = 300;
    let lazyloadThrottle;

    function lazyload() {
        if (lazyloadThrottle) {
            clearTimeout(lazyloadThrottle);
        }
        lazyloadThrottle = setTimeout(() => {
            document.querySelectorAll("img.lazyload").forEach(img => {
                if (img.getBoundingClientRect().top < window.innerHeight + 200 && img.getBoundingClientRect().bottom > -200 && getComputedStyle(img).display !== "none") {
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.remove("lazyload");
                        img.classList.add("loaded");
                        img.removeAttribute("data-src");
                    }
                }
            });
        }, lazyLoadThrottleTimeout);
    }

    document.addEventListener("scroll", lazyload);
    window.addEventListener("resize", lazyload);
    window.addEventListener("orientationchange", lazyload);
    lazyload(); // Initialer Aufruf für Bilder im sichtbaren Bereich
});
</script>
EOT;

// Zusätzliche Styles für das Archiv
// Angelehnt an das Original, aber mit Anpassungen für dein aktuelles Design
$additionalHeadContent = <<<EOT
<style>
/* Allgemeine Layout-Anpassungen (aus main.css oder ähnlichem übernommen) */
#mainContainer {
    max-width: 1200px; /* Oder die maximale Breite deines Hauptcontainers */
    margin: 0 auto;
    padding: 20px;
}

body.theme-night {
    background-color: #1a1a1a;
    color: #f0f0f0;
}

/* Archiv-spezifische Stile */
.chapter {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden; /* Wichtig für die Transition */
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

body.theme-night .chapter {
    background-color: #2b2b2b;
    border-color: #444;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.chapter h2 {
    cursor: pointer;
    padding: 15px 20px;
    background-color: #f2f2f2;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.5em;
    font-weight: bold;
    color: #333;
    margin: 0; /* Entfernt Standard-Margin von h2 */
}

body.theme-night .chapter h2 {
    background-color: #3a3a3a;
    border-bottom-color: #555;
    color: #f0f0f0;
}

.chapter h2 .arrow-left {
    transition: transform 0.3s ease;
    margin-left: 10px;
}

.chapter.expanded h2 .arrow-left {
    transform: rotate(90deg); /* Pfeil nach unten, wenn ausgeklappt */
}

.chapter p {
    padding: 10px 20px;
    margin: 0;
    line-height: 1.6;
    color: #555;
}

body.theme-night .chapter p {
    color: #ccc;
}

.chapter-links {
    display: flex;
    flex-wrap: wrap;
    padding: 10px 15px 20px; /* Mehr Padding unten */
    gap: 10px; /* Abstand zwischen den Thumbnails */
    justify-content: center; /* Zentriert die Thumbnails */
}

.chapter-links a {
    display: block;
    border: 1px solid #ccc;
    border-radius: 5px;
    overflow: hidden;
    text-decoration: none;
    color: #333;
    position: relative; /* Für das Hover-Datum */
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    width: 100px; /* Feste Breite für Thumbnails */
    height: 100px; /* Feste Höhe für Thumbnails */
    flex-shrink: 0; /* Verhindert Schrumpfen */
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f9f9f9; /* Hintergrund für Platzhalter/leere Bereiche */
}

body.theme-night .chapter-links a {
    border-color: #555;
    background-color: #333;
}

.chapter-links a:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

body.theme-night .chapter-links a:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.5);
}

.chapter-links img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain; /* Bild anpassen, ohne es zu beschneiden */
    transition: opacity 0.3s ease;
}

/* Lazyload Placeholder */
img.lazyload {
    opacity: 0; /* Versteckt das Bild, bis es geladen ist */
}

img.loaded {
    opacity: 1; /* Zeigt das Bild an, sobald es geladen ist */
}

/* Datum beim Hover */
.chapter-links a span {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 0.8em;
    padding: 3px 5px;
    text-align: center;
    opacity: 0;
    transition: opacity 0.2s ease-in-out;
    pointer-events: none; /* Verhindert, dass das Span Klicks abfängt */
    z-index: 10; /* Stellt sicher, dass das Datum über dem Bild liegt */
}

.chapter-links a:hover span {
    opacity: 1;
}

/* Content für aufklappbare Sektionen */
.collapsible-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out, padding 0.3s ease-out;
    padding: 0 20px; /* Initial padding */
}

.chapter.expanded .collapsible-content {
    max-height: 5000px; /* Genug Platz für alle Inhalte */
    padding-top: 10px;
    padding-bottom: 20px;
}

/* Mobile Anpassungen */
@media (max-width: 768px) {
    .chapter h2 {
        font-size: 1.2em;
        padding: 10px 15px;
    }
    .chapter p {
        padding: 5px 15px;
        font-size: 0.9em;
    }
    .chapter-links {
        padding: 5px 10px 15px;
        justify-content: space-around; /* Bessere Verteilung auf kleinen Bildschirmen */
    }
    .chapter-links a {
        width: 80px; /* Kleinere Thumbnails auf Mobilgeräten */
        height: 80px;
    }
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
EOT;

// Binde den gemeinsamen Header ein.
// Der Pfad ist relativ zum aktuellen Verzeichnis (Root der Website)
include __DIR__ . '/src/layout/header.php';
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
            $chapterTitle = !empty(trim(strip_tags($chapter['title'] ?? '', '<b><i><u><p><br>'))) ? $chapter['title'] : 'Dieses Kapitel wird im Moment bearbeitet.';
            $chapterDescription = !empty(trim(strip_tags($chapter['description'] ?? '', '<b><i><u><p><br>'))) ? $chapter['description'] : 'Die Informationen zu diesem Kapitel werden noch erstellt. Bitte besuche diesen Teil später noch einmal.';
        ?>
            <section class="chapter collapsible-section" data-ch-id="<?php echo htmlspecialchars($chapterId); ?>">
                <h2 class="collapsible-header"><?php echo $chapterTitle; ?> <i class="fas fa-chevron-right arrow-left jsdep"></i></h2>
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
                        <?php else: ?>
                            <?php foreach ($comicsForThisChapter as $comicId => $comicDetails):
                                $comicImagePath = "assets/comic_thumbnails/{$comicId}.jpg";
                                $displayImagePath = file_exists(__DIR__ . '/' . $comicImagePath) ? $baseUrl . $comicImagePath : $baseUrl . $placeholderImagePath;
                                $comicPagePath = $baseUrl . 'comic/' . htmlspecialchars($comicId) . '.php'; // Link zur Comic-Seite
                                $comicDate = DateTime::createFromFormat('Ymd', $comicId);
                                $displayDate = $comicDate ? $comicDate->format('d.m.Y') : 'Unbekanntes Datum';
                            ?>
                                <a href="<?php echo $comicPagePath; ?>" title="Comic vom <?php echo htmlspecialchars($displayDate); ?>">
                                    <span><?php echo htmlspecialchars($displayDate); ?></span>
                                    <img class="lazyload" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" data-src="<?php echo htmlspecialchars($displayImagePath); ?>" alt="Comic vom <?php echo htmlspecialchars($displayDate); ?>">
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
?>
