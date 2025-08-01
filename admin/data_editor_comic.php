<?php
/**
 * Dies ist die Administrationsseite zum Bearbeiten der comic_var.json Konfigurationsdatei.
 * Sie ermöglicht das Hinzufügen, Bearbeiten und Löschen von Comic-Einträgen
 * über eine benutzerfreundliche Oberfläche.
 *
 * Zusätzlich werden fehlende Comic-IDs aus den Bildordnern automatisch hinzugefügt
 * und unvollständige Einträge visuell hervorgehoben.
 * Ein Bericht über fehlende Informationen wird am Ende der Seite angezeigt.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: data_editor_comic.php wird geladen.");

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer in data_editor_comic.php gestartet.");

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
session_start();
if ($debugMode)
    error_log("DEBUG: Session gestartet in data_editor_comic.php.");

// Logout-Logik: Muss vor dem Sicherheitscheck erfolgen.
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion erkannt.");
    // Zerstöre alle Session-Variablen.
    $_SESSION = array();

    // Lösche das Session-Cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httpholy"]
        );
    }

    // Zerstöre die Session.
    session_destroy();

    // Weiterleitung zur Login-Seite (index.php im Admin-Bereich).
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($debugMode)
        error_log("DEBUG: Nicht angemeldet, Weiterleitung zur Login-Seite von data_editor_comic.php.");
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}
if ($debugMode)
    error_log("DEBUG: Admin in data_editor_comic.php angemeldet.");

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';
$comicLowresDirPath = __DIR__ . '/../assets/comic_lowres/';
$comicHiresDirPath = __DIR__ . '/../assets/comic_hires/';
$comicThumbnailsDirPath = __DIR__ . '/../assets/comic_thumbnails/'; // Neuer Pfad
$comicSocialMediaDirPath = __DIR__ . '/../assets/comic_socialmedia/'; // Neuer Pfad
if ($debugMode) {
    error_log("DEBUG: Pfade definiert: comicVarJsonPath=" . $comicVarJsonPath . ", comicLowresDirPath=" . $comicLowresDirPath . ", comicHiresDirPath=" . $comicHiresDirPath . ", comicThumbnailsDirPath=" . $comicThumbnailsDirPath . ", comicSocialMediaDirPath=" . $comicSocialMediaDirPath);
}

// Konstante für die Anzahl der Elemente pro Seite definieren, falls noch nicht geschehen
if (!defined('ITEMS_PER_PAGE')) {
    define('ITEMS_PER_PAGE', 50);
    if ($debugMode)
        error_log("DEBUG: ITEMS_PER_PAGE definiert: " . ITEMS_PER_PAGE);
}

// Setze Parameter für den Header.
$pageTitle = 'Comic Daten Editor';
$pageHeader = 'Comic Daten Editor';
$robotsContent = 'noindex, nofollow'; // Admin-Seiten nicht crawlen
if ($debugMode) {
    error_log("DEBUG: Seiten-Titel: " . $pageTitle);
    error_log("DEBUG: Robots-Content: " . $robotsContent);
}

$message = '';
$messageType = ''; // 'success' or 'error'

// Optionen für 'type' und 'chapter'
$comicTypeOptions = ['Comicseite', 'Lückenfüller'];
// Kapitel-Optionen: 0 bis 100 (wird jetzt nur noch als Referenz verwendet, da Input-Feld Text ist)
$chapterOptions = range(0, 100);
if ($debugMode) {
    error_log("DEBUG: Comic-Typ-Optionen: " . implode(', ', $comicTypeOptions));
    error_log("DEBUG: Kapitel-Optionen: " . implode(', ', $chapterOptions));
}

/**
 * Lädt Comic-Metadaten aus einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Die dekodierten Daten als assoziatives Array oder ein leeres Array im Fehlerfall.
 */
function loadComicData(string $path, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: loadComicData() aufgerufen für: " . basename($path));
    if (!file_exists($path)) {
        if ($debugMode)
            error_log("DEBUG: Comic-JSON-Datei nicht gefunden: " . $path);
        return [];
    }
    $content = file_get_contents($path);
    if ($content === false) {
        error_log("Fehler beim Lesen der JSON-Datei: " . $path);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Lesen des Inhalts von: " . $path);
        return [];
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        return [];
    }
    if ($debugMode)
        error_log("DEBUG: Comic-Daten erfolgreich geladen und dekodiert.");
    return is_array($data) ? $data : [];
}

/**
 * Speichert Comic-Daten in die JSON-Datei, alphabetisch sortiert.
 * Diese Funktion führt nun ein Merge durch, um nur die übergebenen Daten zu aktualisieren.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $newDataSubset Die neuen oder aktualisierten Daten (Subset der gesamten Daten).
 * @param array $deletedIds Eine Liste von IDs, die gelöscht werden sollen.
 * @param bool $debugMode Debug-Modus Flag.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveComicData(string $path, array $newDataSubset, array $deletedIds = [], bool $debugMode): bool
{
    if ($debugMode)
        error_log("DEBUG: saveComicData() aufgerufen für: " . basename($path));
    $existingData = loadComicData($path, $debugMode); // Lade die bestehenden Daten

    // Aktualisiere bestehende Daten mit dem Subset und füge neue hinzu
    foreach ($newDataSubset as $id => $data) {
        $existingData[$id] = $data;
        if ($debugMode)
            error_log("DEBUG: Aktualisiere/Füge Comic-ID hinzu: " . $id);
    }

    // Entferne gelöschte IDs
    foreach ($deletedIds as $id) {
        if (isset($existingData[$id])) {
            unset($existingData[$id]);
            if ($debugMode)
                error_log("DEBUG: Lösche Comic-ID: " . $id);
        }
    }

    // Sortiere das Array alphabetisch nach Schlüsseln (Comic-IDs)
    ksort($existingData);
    if ($debugMode)
        error_log("DEBUG: Comic-Daten nach ID sortiert.");
    $jsonContent = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false) {
        error_log("Fehler beim Kodieren von Comic-Daten: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Kodieren von Comic-Daten: " . json_last_error_msg());
        return false;
    }
    if (file_put_contents($path, $jsonContent) === false) {
        error_log("Fehler beim Schreiben der Comic-Daten nach " . $path);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Schreiben der Comic-Daten nach " . $path);
        return false;
    }
    if ($debugMode)
        error_log("DEBUG: Comic-Daten erfolgreich gespeichert.");
    return true;
}

/**
 * Scannt die Comic-Bildverzeichnisse nach vorhandenen Comic-IDs.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne Erweiterung), sortiert.
 */
function getComicIdsFromImages(string $lowresDir, string $hiresDir, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: getComicIdsFromImages() aufgerufen für lowres: " . $lowresDir . " und hires: " . $hiresDir);
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    $dirs = [$lowresDir, $hiresDir];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            if ($files === false) {
                if ($debugMode)
                    error_log("DEBUG: scandir() fehlgeschlagen für " . $dir);
                continue;
            }
            foreach ($files as $file) {
                // Ignoriere . und .. sowie versteckte Dateien und "in_translation" Bilder
                if ($file === '.' || $file === '..' || substr($file, 0, 1) === '.' || strpos($file, 'in_translation') !== false) {
                    if ($debugMode)
                        error_log("DEBUG: Datei übersprungen ('.' oder '..' oder versteckt oder 'in_translation'): " . $file);
                    continue;
                }
                $info = pathinfo($file);
                if (isset($info['filename']) && preg_match('/^\d{8}$/', $info['filename']) && isset($info['extension']) && in_array(strtolower($info['extension']), $imageExtensions)) {
                    $imageIds[$info['filename']] = true; // Verwende assoziatives Array für Eindeutigkeit
                    if ($debugMode)
                        error_log("DEBUG: Comic-ID aus Bild gefunden: " . $info['filename'] . " in " . basename($dir));
                } else {
                    if ($debugMode)
                        error_log("DEBUG: Datei übersprungen (ungültiges Format/Extension): " . $file . " in " . basename($dir));
                }
            }
        } else {
            if ($debugMode)
                error_log("DEBUG: Verzeichnis nicht gefunden oder ist kein Verzeichnis: " . $dir);
        }
    }
    $sortedIds = array_keys($imageIds);
    sort($sortedIds);
    if ($debugMode)
        error_log("DEBUG: " . count($sortedIds) . " Comic-IDs aus Bildern gefunden und sortiert.");
    return $sortedIds;
}

/**
 * Checks for the existence of various image types for a given comic ID.
 * @param string $comicId The ID of the comic (e.g., 'JJJJMMTT').
 * @param array $directories An associative array of directory paths for each image type.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array An associative array indicating presence (true/false) for each image type.
 */
function checkImageExistenceForComic(string $comicId, array $directories, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: checkImageExistenceForComic() aufgerufen für Comic-ID: " . $comicId);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $results = [];

    foreach ($directories as $type => $dirPath) {
        $found = false;
        foreach ($imageExtensions as $ext) {
            $filePath = $dirPath . $comicId . '.' . $ext;
            if (file_exists($filePath)) {
                $found = true;
                break;
            }
        }
        $results[$type] = $found;
        if ($debugMode)
            error_log("DEBUG: Bildtyp '" . $type . "' für " . $comicId . ": " . ($found ? "gefunden" : "fehlt"));
    }
    return $results;
}


// Verarbeite POST-Anfragen zum Speichern (AJAX-Handling)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    if ($debugMode)
        error_log("DEBUG: POST-Anfrage mit application/json Content-Type erkannt.");
    // JSON-Daten aus dem Request Body lesen
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Dekodieren der JSON-Daten: ' . json_last_error_msg()]);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Dekodieren der empfangenen JSON-Daten: " . json_last_error_msg());
        exit;
    }
    if ($debugMode)
        error_log("DEBUG: JSON-Daten erfolgreich empfangen und dekodiert.");

    $updatedComicDataSubset = [];
    $deletedIds = [];

    if (isset($requestData['pages']) && is_array($requestData['pages'])) {
        if ($debugMode)
            error_log("DEBUG: Verarbeite " . count($requestData['pages']) . " Seiten aus der Anfrage.");
        foreach ($requestData['pages'] as $page) {
            $comicId = trim($page['comic_id']);
            if (empty($comicId)) {
                // Wenn die ID leer ist, wurde die Zeile im Frontend als gelöscht markiert oder ist ungültig
                // Wir fügen sie zur Liste der zu löschenden IDs hinzu, falls sie existiert
                if (isset($page['original_comic_id']) && !empty($page['original_comic_id'])) {
                    $deletedIds[] = $page['original_comic_id'];
                    if ($debugMode)
                        error_log("DEBUG: Original-ID für Löschung markiert (leere Comic-ID): " . $page['original_comic_id']);
                }
                continue; // Überspringe leere IDs
            }

            $type = isset($page['comic_type']) ? trim($page['comic_type']) : '';
            $name = isset($page['comic_name']) ? trim($page['comic_name']) : '';
            $transcript = isset($page['comic_transcript']) ? $page['comic_transcript'] : '';

            // NEU: Kapitel als String empfangen und verarbeiten
            $chapter = $page['comic_chapter'] ?? '';

            if ($chapter === '') {
                $chapter = null; // Leere Zeichenkette als null speichern
            } else {
                // Komma durch Punkt ersetzen für konsistente numerische Umwandlung
                $chapter = str_replace(',', '.', $chapter);
                // Prüfen, ob es eine gültige Zahl ist (Integer oder Float)
                if (!is_numeric($chapter)) {
                    // Wenn nicht numerisch, als ungültige Eingabe behandeln und null setzen
                    $chapter = null;
                    if ($debugMode)
                        error_log("DEBUG: Kapitel für Comic-ID " . $comicId . " als ungültig erkannt (nicht numerisch), auf null gesetzt.");
                } else {
                    // In Float umwandeln für Konsistenz, auch wenn es eine Ganzzahl ist (z.B. "0" oder "5")
                    $chapter = (float) $chapter;
                    // Wenn negativ, als fehlend behandeln (oder auf null setzen)
                    if ($chapter < 0) {
                        $chapter = null;
                        if ($debugMode)
                            error_log("DEBUG: Kapitel für Comic-ID " . $comicId . " als ungültig erkannt (negativ), auf null gesetzt.");
                    }
                }
            }

            $updatedComicDataSubset[$comicId] = [
                'type' => $type,
                'name' => $name,
                'transcript' => $transcript,
                'chapter' => $chapter
            ];
            if ($debugMode)
                error_log("DEBUG: Comic-Daten für Update/Insert vorbereitet: " . $comicId);
        }
    }
    if ($debugMode) {
        error_log("DEBUG: " . count($updatedComicDataSubset) . " Comics für Update/Insert gesammelt.");
        error_log("DEBUG: " . count($deletedIds) . " Comics für Löschung gesammelt.");
    }

    // Speichere das Subset der Daten und verarbeite gelöschte IDs.
    if (saveComicData($comicVarJsonPath, $updatedComicDataSubset, $deletedIds, $debugMode)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Comic-Daten erfolgreich gespeichert!']);
        if ($debugMode)
            error_log("DEBUG: Comic-Daten erfolgreich gespeichert (AJAX-Antwort).");
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Speichern der Comic-Daten.']);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Speichern der Comic-Daten (AJAX-Antwort).");
        exit;
    }
}

// Lade die gesamte Comic-Datenbank für Paginierung und fehlende IDs
$fullComicData = loadComicData($comicVarJsonPath, $debugMode);
if ($debugMode)
    error_log("DEBUG: Count of fullComicData after loading from JSON: " . count($fullComicData)); // NEUE DEBUG-ZEILE
$imageComicIds = getComicIdsFromImages($comicLowresDirPath, $comicHiresDirPath, $debugMode);
if ($debugMode)
    error_log("DEBUG: Count of imageComicIds: " . count($imageComicIds));

// Füge fehlende Comic-IDs aus den Bildern hinzu
foreach ($imageComicIds as $id) {
    if (!isset($fullComicData[$id])) {
        $fullComicData[$id] = [
            'type' => '', // Leer lassen für neue Einträge
            'name' => '', // Leer lassen
            'transcript' => '', // Leer lassen
            'chapter' => null // Leer lassen
        ];
        if ($debugMode)
            error_log("DEBUG: Neue Comic-ID aus Bildern zu fullComicData hinzugefügt: " . $id);
    }
}
if ($debugMode)
    error_log("DEBUG: Final count of fullComicData after merging with image IDs: " . count($fullComicData));

// Sortiere die gesamten Daten nach Comic-ID, bevor paginiert wird
ksort($fullComicData);
if ($debugMode)
    error_log("DEBUG: fullComicData nach ID sortiert.");

// Sammle Bildverfügbarkeitsdaten für alle Comics
$imageDirectories = [
    'lowres' => $comicLowresDirPath,
    'hires' => $comicHiresDirPath,
    'thumbnails' => $comicThumbnailsDirPath,
    'socialmedia' => $comicSocialMediaDirPath,
];

$imageExistenceReport = [];
foreach ($fullComicData as $id => $data) {
    $imageExistenceReport[$id] = checkImageExistenceForComic($id, $imageDirectories, $debugMode);
}
if ($debugMode)
    error_log("DEBUG: Bildexistenzbericht für alle Comics erstellt.");


// --- Paginierungslogik anwenden ---
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($currentPage < 1)
    $currentPage = 1;


$totalItems = count($fullComicData);
$totalPages = ceil($totalItems / ITEMS_PER_PAGE);

// Debugging output in den Error-Log
if ($debugMode) {
    error_log("DEBUG: Total Items: " . $totalItems);
    error_log("DEBUG: Items Per Page: " . ITEMS_PER_PAGE);
    error_log("DEBUG: Calculated Total Pages: " . $totalPages);
    error_log("DEBUG: Current Page (before adjustment): " . $currentPage);
}

// Sicherstellen, dass die aktuelle Seite nicht außerhalb des Bereichs liegt
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
    if ($debugMode)
        error_log("DEBUG: Current Page adjusted to Total Pages: " . $currentPage);
} elseif ($totalPages == 0 && $totalItems > 0) {
    $currentPage = 1; // Sollte nicht passieren, wenn totalItems > 0 und ITEMS_PER_PAGE > 0
    if ($debugMode)
        error_log("DEBUG: Current Page adjusted to 1 (totalPages 0, totalItems > 0): " . $currentPage);
} elseif ($totalItems == 0) {
    $currentPage = 1;
    $totalPages = 1; // Sicherstellen, dass mindestens 1 Seite angezeigt wird, wenn keine Items vorhanden sind
    if ($debugMode)
        error_log("DEBUG: Current Page and Total Pages adjusted to 1 (no items): " . $currentPage);
}

if ($debugMode)
    error_log("DEBUG: Final Current Page: " . $currentPage);
if ($debugMode)
    error_log("DEBUG: Final Total Pages: " . $totalPages);


$offset = ($currentPage - 1) * ITEMS_PER_PAGE;
if ($debugMode)
    error_log("DEBUG: Offset: " . $offset);

$paginatedComicData = array_slice($fullComicData, $offset, ITEMS_PER_PAGE, true); // true, um Keys zu erhalten
if ($debugMode)
    error_log("DEBUG: Paginated Comic Data Count: " . count($paginatedComicData));

// Bericht über unvollständige Informationen (basierend auf allen Daten)
$incompleteInfoReportFull = [];
foreach ($fullComicData as $id => $data) {
    $missingFields = [];
    // Korrektur für leere Transkripte: <p><br></p> als leer behandeln
    $transcriptContent = trim(strip_tags($data['transcript'], '<br>')); // Entferne HTML-Tags, außer <br>
    $isTranscriptEffectivelyEmpty = (empty($transcriptContent) || $transcriptContent === '<br>' || $transcriptContent === '&nbsp;');

    if (empty($data['type'])) {
        $missingFields[] = 'type';
    }
    if (empty($data['name'])) {
        $missingFields[] = 'name';
    }
    if ($isTranscriptEffectivelyEmpty) { // Verwende die korrigierte Prüfung
        $missingFields[] = 'transcript';
    }
    // Kapitel 0 soll nicht mehr als fehlend gelten
    if ($data['chapter'] === null || $data['chapter'] < 0) {
        $missingFields[] = 'chapter';
    }

    if (!empty($missingFields)) {
        $incompleteInfoReportFull[$id] = $missingFields;
        if ($debugMode)
            error_log("DEBUG: Unvollständiger Eintrag gefunden: " . $id . " (Fehlende Felder: " . implode(', ', $missingFields) . ")");
    }
}
if ($debugMode)
    error_log("DEBUG: " . count($incompleteInfoReportFull) . " unvollständige Einträge im Gesamtbericht.");

// Bericht für die aktuelle Seite
$incompleteInfoReportCurrentPage = [];
foreach ($paginatedComicData as $id => $data) {
    if (isset($incompleteInfoReportFull[$id])) {
        $incompleteInfoReportCurrentPage[$id] = $incompleteInfoReportFull[$id];
    }
}
if ($debugMode)
    error_log("DEBUG: " . count($incompleteInfoReportCurrentPage) . " unvollständige Einträge auf der aktuellen Seite.");

// Prüfen, ob auf anderen Seiten unvollständige Informationen vorhanden sind
$hasIncompleteOtherPages = false;
foreach ($incompleteInfoReportFull as $id => $fields) {
    if (!isset($paginatedComicData[$id])) { // Wenn die ID nicht auf der aktuellen Seite ist
        $hasIncompleteOtherPages = true;
        if ($debugMode)
            error_log("DEBUG: Unvollständiger Eintrag auf anderer Seite gefunden: " . $id);
        break;
    }
}
if ($debugMode)
    error_log("DEBUG: Has incomplete data on other pages: " . ($hasIncompleteOtherPages ? "true" : "false"));

// Ermittle, welche Seiten in der Paginierung unvollständige Daten haben
$pagesWithIncompleteData = [];
if (!empty($incompleteInfoReportFull)) {
    $allComicIds = array_keys($fullComicData);
    foreach ($incompleteInfoReportFull as $id => $fields) {
        $index = array_search($id, $allComicIds);
        if ($index !== false) {
            $pageNumber = floor($index / ITEMS_PER_PAGE) + 1;
            $pagesWithIncompleteData[$pageNumber] = true;
            if ($debugMode)
                error_log("DEBUG: Seite " . $pageNumber . " enthält unvollständige Daten.");
        }
    }
}
if ($debugMode)
    error_log("DEBUG: Seiten mit unvollständigen Daten: " . implode(', ', array_keys($pagesWithIncompleteData)));


// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
    if ($debugMode)
        error_log("DEBUG: Header in data_editor_comic.php eingebunden.");
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- Summernote CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

<style>
    /* Allgemeine Layout-Anpassungen */
    .admin-container {
        padding: 20px;
        max-width: 1200px;
        margin: 20px auto;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    body.theme-night .admin-container {
        background-color: #00334c;
        /* Dunklerer Hintergrund für den Container im Dark Mode */
        color: #fff;
    }

    .message-box {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
        display: none;
        /* Standardmäßig ausgeblendet */
    }

    .message-box.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message-box.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .message-box.info {
        /* Added info message style */
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .message-box.warning {
        /* Added warning message style */
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }


    body.theme-night .message-box.success {
        background-color: #28a745;
        /* Dunkelgrün */
        color: #fff;
        border-color: #218838;
    }

    body.theme-night .message-box.error {
        background-color: #dc3545;
        /* Dunkelrot */
        color: #fff;
        border-color: #c82333;
    }

    body.theme-night .message-box.info {
        /* Dark mode for info message */
        background-color: #17a2b8;
        color: #fff;
        border-color: #138496;
    }

    body.theme-night .message-box.warning {
        /* Dark mode for warning message */
        background-color: #6c5b00;
        color: #fff;
        border-color: #927c00;
    }

    /* Collapsible Sections */
    .collapsible-section {
        margin-bottom: 30px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        /* Ensures content doesn\'t spill during transition */
    }

    body.theme-night .collapsible-section {
        background-color: #00425c;
    }

    .collapsible-header {
        cursor: pointer;
        padding: 15px 20px;
        /* More padding for a better clickable area */
        background-color: #f2f2f2;
        /* Light background for header */
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.5em;
        /* Match h2 font size */
        font-weight: bold;
        color: #333;
    }

    body.theme-night .collapsible-header {
        background-color: #005a7e;
        color: #fff;
        border-bottom-color: #007bb5;
    }

    .collapsible-header i {
        transition: transform 0.3s ease;
        margin-left: 10px;
        /* Space between text and icon */
    }

    .collapsible-section.expanded .collapsible-header i {
        transform: rotate(0deg);
        /* Down arrow */
    }

    .collapsible-section:not(.expanded) .collapsible-header i {
        transform: rotate(-90deg);
        /* Right arrow */
    }

    .collapsible-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        padding: 0 20px;
        /* Initial padding, will be adjusted when expanded */
        display: block;
        /* Sicherstellen, dass es nicht 'display: none' ist, wenn es von max-height gesteuert wird */
    }

    .collapsible-section.expanded .collapsible-content {
        /* Angepasste max-height Werte */
        max-height: 90000px;
        /* Für die Comic-Liste */
        padding-top: 20px;
        /* Restore top padding */
        padding-bottom: 20px;
        /* Restore bottom padding */
        display: block !important;
        /* Explizit sicherstellen, dass es sichtbar ist */
    }

    /* Spezifische Anpassung für den Bericht über fehlende Comic-Informationen */
    .report-section.collapsible-section.expanded .collapsible-content {
        max-height: 459800px;
        /* Sehr großer Wert für den Gesamtbericht */
        display: block !important;
        /* Explizit sicherstellen, dass es sichtbar ist */
    }

    /* Remove padding from the section classes themselves as it's now on collapsible-content */
    .form-section,
    .comic-list-section,
    .report-section {
        padding: 0;
    }

    /* Restore border-radius for collapsed sections */
    .collapsible-section:not(.expanded) {
        border-radius: 8px;
    }

    /* Ensure header has rounded corners when section is collapsed */
    .collapsible-section:not(.expanded) .collapsible-header {
        border-radius: 8px;
        border-bottom: none;
        /* No bottom border when collapsed */
    }

    /* Ensure header has rounded top corners when expanded */
    .collapsible-section.expanded .collapsible-header {
        border-radius: 8px 8px 0 0;
    }


    /* Formular- und Button-Stile */
    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }

    body.theme-night .form-group label {
        color: #ccc;
    }

    .form-group input[type="text"],
    .form-group select {
        width: calc(100% - 22px);
        /* Padding berücksichtigen */
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1em;
        box-sizing: border-box;
        /* Padding und Border in der Breite enthalten */
        background-color: #fff;
        color: #333;
    }

    /* Summernote editor frame */
    .note-editor.note-frame {
        width: calc(100% - 22px) !important;
        /* Ensure same width as other inputs */
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }


    body.theme-night .form-group input[type="text"],
    body.theme-night .form-group select,
    body.theme-night .note-editor.note-frame {
        /* Summernote editor frame dark mode */
        background-color: #005a7e;
        border-color: #007bb5;
        color: #fff;
    }

    /* Summernote specific dark mode adjustments */
    body.theme-night .note-editor .note-toolbar,
    body.theme-night .note-editor .note-editing-area .note-editable {
        background-color: #005a7e;
        color: #fff;
    }

    body.theme-night .note-editor .note-toolbar .btn-group .btn {
        background-color: #006690;
        color: #fff;
        border-color: #007bb5;
    }

    body.theme-night .note-editor .note-toolbar .btn-group .btn:hover {
        background-color: #007bb5;
    }

    body.theme-night .note-editor .note-toolbar .dropdown-menu {
        background-color: #005a7e;
        border-color: #007bb5;
    }

    body.theme-night .note-editor .note-toolbar .dropdown-menu a {
        color: #fff;
    }

    body.theme-night .note-editor .note-toolbar .dropdown-menu a:hover {
        background-color: #006690;
    }

    .form-group textarea {
        /* This is the original textarea, hidden by Summernote */
        min-height: 100px;
        resize: vertical;
    }

    /* NEU: Styling für die Checkbox-Gruppe */
    .form-group-checkbox {
        margin-top: 8px;
        display: flex;
        align-items: center;
    }

    .form-group-checkbox label {
        margin-bottom: 0;
        margin-left: 8px;
        font-weight: normal;
        cursor: pointer;
    }

    .button-group {
        text-align: right;
        margin-top: 20px;
    }

    button,
    .button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        background-color: #007bff;
        color: white;
        font-size: 1em;
        cursor: pointer;
        transition: background-color 0.3s ease;
        text-decoration: none;
        /* Für .button Klasse */
        display: inline-block;
        /* Für .button Klasse */
        margin-left: 10px;
        /* Abstand zwischen Buttons */
    }

    button:hover,
    .button:hover {
        background-color: #0056b3;
    }

    button.delete,
    .button.delete {
        background-color: #dc3545;
    }

    button.delete:hover,
    .button.delete:hover {
        background-color: #c82333;
    }

    button.edit,
    .button.edit {
        background-color: #ffc107;
        color: #333;
    }

    button.edit:hover,
    .button.edit:hover {
        background-color: #e0a800;
    }

    /* Icon buttons in table */
    .comic-table td .actions button {
        background-color: transparent;
        /* Make background transparent */
        border: 1px solid transparent;
        /* Remove border */
        color: #007bff;
        /* Use primary color for icons */
        padding: 5px;
        /* Adjust padding for icon-only buttons */
        margin: 0 2px;
        /* Adjust margin */
        font-size: 1.1em;
        /* Make icons slightly larger */
    }

    .comic-table td .actions button:hover {
        background-color: rgba(0, 123, 255, 0.1);
        /* Light hover background */
        border-color: #007bff;
        /* Add border on hover */
    }

    body.theme-night .comic-table td .actions button {
        color: #7bbdff;
        /* Lighter color for icons in dark mode */
    }

    body.theme-night .comic-table td .actions button:hover {
        background-color: rgba(123, 189, 255, 0.1);
        border-color: #7bbdff;
    }


    body.theme-night button,
    body.theme-night .button {
        background-color: #2a6177;
    }

    body.theme-night button:hover,
    body.theme-night .button:hover {
        background-color: #48778a;
    }

    body.theme-night button.delete,
    body.theme-night .button.delete {
        background-color: #dc3545;
    }

    body.theme-night button.delete:hover,
    body.theme-night .button.delete:hover {
        background-color: #c82333;
    }


    body.theme-night button.edit,
    body.theme-night .button.edit {
        background-color: #ffc107;
        color: #333;
        /* Textfarbe bleibt dunkel für Kontrast */
    }

    body.theme-night button.edit:hover,
    body.theme-night .button.edit:hover {
        background-color: #e0a800;
    }

    /* Comic-Liste und Tabelle */
    /* .comic-list-section padding is now handled by .collapsible-content */
    .comic-table-container {
        overflow-x: auto;
        /* Ermöglicht horizontales Scrollen bei kleinen Bildschirmen */
    }

    .comic-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .comic-table th,
    .comic-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        vertical-align: top;
    }

    body.theme-night .comic-table th,
    body.theme-night .comic-table td {
        border-color: #005a7e;
    }

    .comic-table th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
    }

    body.theme-night .comic-table th {
        background-color: #005a7e;
        color: #fff;
    }

    .comic-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    body.theme-night .comic-table tr:nth-child(even) {
        background-color: #004c6b;
    }

    .comic-table tr:hover {
        background-color: #f1f1f1;
    }

    body.theme-night .comic-table tr:hover {
        background-color: #006690;
    }

    .comic-table td .editable-field {
        width: 100%;
        padding: 5px;
        border: 1px solid #eee;
        /* Leichter Rand im Nicht-Bearbeitungsmodus */
        border-radius: 3px;
        box-sizing: border-box;
        background-color: transparent;
        /* Standardmäßig transparent */
        cursor: pointer;
        /* Zeigt an, dass es klickbar ist */
        min-height: 30px;
        /* Mindesthöhe für leere Felder */
        display: block;
        /* Stellt sicher, dass es die volle Breite einnimmt */
        color: #333;
        /* Standardtextfarbe */
    }

    body.theme-night .comic-table td .editable-field {
        border-color: #005a7e;
        color: #fff;
    }

    .comic-table td .editable-field:hover {
        border-color: #ccc;
        /* Rand beim Hover */
    }

    body.theme-night .comic-table td .editable-field:hover {
        border-color: #007bb5;
    }

    .comic-table td .editable-field.editing {
        border-color: #007bff;
        /* Blauer Rand im Bearbeitungsmodus */
        background-color: #fff;
        /* Weißer Hintergrund im Bearbeitungsmodus */
        cursor: text;
    }

    body.theme-night .comic-table td .editable-field.editing {
        background-color: #005a7e;
        border-color: #007bff;
    }

    .comic-table td .editable-field.missing-info {
        border: 2px solid #dc3545;
        /* Roter Rand für fehlende Infos */
        background-color: #f8d7da;
        /* Hellroter Hintergrund */
    }

    body.theme-night .comic-table td .editable-field.missing-info {
        border-color: #ff4d4d;
        /* Helleres Rot */
        background-color: #721c24;
        /* Dunkleres Rot */
    }

    .comic-table td .actions {
        white-space: nowrap;
        /* Buttons bleiben in einer Zeile */
    }


    /* Paginierung */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
        flex-wrap: wrap;
        /* Ermöglicht Umbruch auf kleineren Bildschirmen */
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        margin: 0 4px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #007bff;
        background-color: #fff;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    body.theme-night .pagination a,
    body.theme-night .pagination span {
        border-color: #005a7e;
        color: #7bbdff;
        background-color: #004c6b;
    }

    .pagination a:hover {
        background-color: #e9ecef;
        color: #0056b3;
    }

    body.theme-night .pagination a:hover {
        background-color: #005a7e;
        color: #a0d0ff;
    }

    .pagination .current-page {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        font-weight: bold;
    }

    body.theme-night .pagination .current-page {
        background-color: #007bff;
        /* Bleibt blau, da es die aktive Seite ist */
        border-color: #007bff;
        color: white;
    }

    .pagination .incomplete-page {
        background-color: #f8d7da;
        /* Hellrot */
        border-color: #f5c6cb;
        color: #721c24;
    }

    body.theme-night .pagination .incomplete-page {
        background-color: #dc3545;
        /* Dunkelrot */
        border-color: #c82333;
        color: #fff;
    }

    .pagination .incomplete-page.current-page {
        background-color: #dc3545;
        /* Roter Hintergrund für aktuelle unvollständige Seite */
        color: white;
    }

    .pagination .incomplete-page:hover {
        background-color: #f5c6cb;
    }

    body.theme-night .pagination .incomplete-page:hover {
        background-color: #c82333;
    }

    .pagination .disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Bericht über fehlende Informationen */
    /* .report-section padding is now handled by .collapsible-content */

    /* Styles for the new report table with icons */
    .report-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .report-table th,
    .report-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
        /* Center icons */
        vertical-align: middle;
    }

    body.theme-night .report-table th,
    body.theme-night .report-table td {
        border-color: #005a7e;
    }

    .report-table th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
    }

    body.theme-night .report-table th {
        background-color: #005a7e;
        color: #fff;
    }

    .report-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    body.theme-night .report-table tr:nth-child(even) {
        background-color: #004c6b;
    }

    .report-table tr:hover {
        background-color: #f1f1f1;
    }

    body.theme-night .report-table tr:hover {
        background-color: #006690;
    }

    .icon-success {
        color: #28a745;
        /* Green checkmark */
        font-size: 1.2em;
    }

    .icon-missing {
        color: #dc3545;
        /* Red cross */
        font-size: 1.2em;
    }

    .report-section .missing-other-pages-warning {
        margin-top: 15px;
        padding: 10px;
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        border-radius: 5px;
        color: #856404;
    }

    body.theme-night .report-section .missing-other-pages-warning {
        background-color: #6c5b00;
        /* Dunkleres Gelb */
        border-color: #927c00;
        color: #fff;
    }

    /* Floating Save Button */
    .floating-save-button {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #28a745;
        /* Grün */
        color: white;
        padding: 15px 25px;
        border-radius: 50px;
        font-size: 1.2em;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .floating-save-button:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }

    .floating-save-button.disabled {
        background-color: #6c757d;
        /* Grau */
        cursor: not-allowed;
        box-shadow: none;
    }

    body.theme-night .floating-save-button {
        background-color: #1e7e34;
        /* Dunkelgrün */
    }

    body.theme-night .floating-save-button:hover {
        background-color: #1c7430;
    }

    body.theme-night .floating-save-button.disabled {
        background-color: #495057;
    }

    /* Floating Add Button */
    .floating-add-button {
        position: fixed;
        bottom: 90px;
        /* Über dem Save Button */
        right: 20px;
        background-color: #17a2b8;
        /* Cyan */
        color: white;
        padding: 15px 25px;
        border-radius: 50px;
        font-size: 1.2em;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .floating-add-button:hover {
        background-color: #138496;
        transform: translateY(-2px);
    }

    body.theme-night .floating-add-button {
        background-color: #117a8b;
    }

    body.theme-night .floating-add-button:hover {
        background-color: #0f6674;
    }

    /* Responsive Anpassungen */
    @media (max-width: 768px) {
        .admin-container {
            padding: 10px;
            margin: 10px auto;
        }

        .form-group input[type="text"],
        .form-group select {
            width: calc(100% - 20px);
            /* Anpassung für kleinere Bildschirme */
        }

        .note-editor.note-frame {
            /* Summernote editor frame */
            width: calc(100% - 20px) !important;
            /* Ensure same width as other inputs */
        }

        .collapsible-header {
            padding: 10px 15px;
            /* Adjust padding for smaller screens */
            font-size: 1.2em;
        }

        .collapsible-content {
            padding: 0 15px;
            /* Adjust padding for smaller screens */
        }

        .collapsible-section.expanded .collapsible-content {
            padding-top: 15px;
            padding-bottom: 15px;
        }


        .comic-table th,
        .comic-table td {
            padding: 6px;
            font-size: 0.9em;
        }

        .comic-table td .actions button {
            padding: 3px 6px;
            font-size: 0.8em;
            margin-left: 2px;
        }

        .pagination a,
        .pagination span {
            padding: 6px 10px;
            margin: 0 2px;
            font-size: 0.9em;
        }

        .floating-save-button,
        .floating-add-button {
            padding: 12px 20px;
            font-size: 1em;
            bottom: 10px;
            right: 10px;
        }

        .floating-add-button {
            bottom: 70px;
            /* Anpassung für kleinere Bildschirme */
        }

    }

    .note-modal-backdrop {
        /*Fix Summernote backdrop Problem*/
        z-index: 99;
    }
</style>

<div class="admin-container">
    <div id="message-box" class="message-box"></div>

    <section class="form-section collapsible-section"> <!-- Standardmäßig eingeklappt -->
        <h2 class="collapsible-header">Comic-Eintrag bearbeiten / hinzufügen <i class="fas fa-chevron-right"></i></h2>
        <div class="collapsible-content">
            <form id="comic-edit-form">
                <input type="hidden" id="original-comic-id" name="original_comic_id" value="">
                <div class="form-group">
                    <label for="comic-id">Comic ID (Datum JJJJMMTT):</label>
                    <input type="text" id="comic-id" name="comic_id" pattern="\d{8}"
                        title="Bitte geben Sie eine 8-stellige Zahl (JJJJMMTT) ein." required>
                </div>
                <div class="form-group">
                    <label for="comic-type">Typ:</label>
                    <select id="comic-type" name="comic_type" required>
                        <?php foreach ($comicTypeOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>">
                                <?php echo htmlspecialchars($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="comic-name">Name:</label>
                    <input type="text" id="comic-name" name="comic_name" required>
                    <!-- NEU: Checkbox hinzugefügt -->
                    <div class="form-group-checkbox">
                        <input type="checkbox" id="comic-name-empty-checkbox">
                        <label for="comic-name-empty-checkbox">Name leer lassen</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="comic-transcript">Transkript (HTML erlaubt):</label>
                    <textarea id="comic-transcript" name="comic_transcript"></textarea>
                </div>
                <div class="form-group">
                    <label for="comic-chapter">Kapitel:</label>
                    <input type="text" id="comic-chapter" name="comic_chapter"
                        placeholder="Geben Sie eine Kapitelnummer ein (z.B. 0, 6, 6.1)">
                </div>
                <div class="button-group">
                    <button type="submit" id="save-single-button">Speichern</button>
                    <button type="button" id="cancel-edit-button">Abbrechen</button>
                </div>
            </form>
        </div>
    </section>

    <section class="comic-list-section collapsible-section expanded"> <!-- Standardmäßig ausgeklappt, wie gewünscht -->
        <h2 class="collapsible-header">Bearbeitungsübersicht Comic-Daten <i class="fas fa-chevron-down"></i></h2>
        <!-- Pfeil nach unten für ausgeklappten Zustand -->
        <div class="collapsible-content">
            <div class="comic-table-container">
                <table class="comic-table" id="comic-data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Typ</th>
                            <th>Name</th>
                            <th>Transkript</th>
                            <th>Kapitel</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paginatedComicData)): ?>
                            <tr>
                                <td colspan="6">Keine Comic-Daten gefunden oder auf dieser Seite verfügbar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paginatedComicData as $id => $data):
                                $isTypeMissing = empty($data['type']);
                                $isNameMissing = empty($data['name']);
                                $transcriptContent = trim(strip_tags($data['transcript'], '<br>'));
                                $isTranscriptEffectivelyEmpty = (empty($transcriptContent) || $transcriptContent === '<br>' || $transcriptContent === '&nbsp;');
                                // Kapitel 0 soll nicht mehr als fehlend gelten
                                $isChapterMissing = ($data['chapter'] === null || $data['chapter'] < 0);

                                $isMissingInfoRow = $isTypeMissing || $isNameMissing || $isTranscriptEffectivelyEmpty || $isChapterMissing;
                                ?>
                                <tr data-comic-id="<?php echo htmlspecialchars($id); ?>"
                                    class="<?php echo $isMissingInfoRow ? 'missing-info-row' : ''; ?>">
                                    <td class="comic-id-display"><?php echo htmlspecialchars($id); ?></td>
                                    <td><span
                                            class="editable-field comic-type-display <?php echo $isTypeMissing ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['type']); ?></span>
                                    </td>
                                    <td><span
                                            class="editable-field comic-name-display <?php echo $isNameMissing ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['name']); ?></span>
                                    </td>
                                    <td><span
                                            class="editable-field comic-transcript-display <?php echo $isTranscriptEffectivelyEmpty ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['transcript']); ?></span>
                                    </td>
                                    <td><span
                                            class="editable-field comic-chapter-display <?php echo $isChapterMissing ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['chapter'] ?? ''); ?></span>
                                    </td>
                                    <td class="actions">
                                        <button type="button" class="edit-button button edit" title="Bearbeiten"><i
                                                class="fas fa-edit"></i></button>
                                        <button type="button" class="delete-button button delete" title="Löschen"><i
                                                class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" id="add-new-comic-button" class="button"><i class="fas fa-plus"></i> Neuen
                Comic-Eintrag hinzufügen (+)</button>

            <!-- Paginierung -->
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=1" title="Erste Seite"><i class="fas fa-angle-double-left"></i></a>
                    <a href="?page=<?php echo $currentPage - 1; ?>" title="Vorherige Seite"><i
                            class="fas fa-angle-left"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                    <span class="disabled"><i class="fas fa-angle-left"></i></span>
                <?php endif; ?>

                <?php
                // Zeige nur einen Bereich von Seitenlinks an
                $range = 2; // Zeigt 2 Seiten vor und nach der aktuellen Seite
                $startPage = max(1, $currentPage - $range);
                $endPage = min($totalPages, $currentPage + $range);

                if ($startPage > 1) {
                    echo '<span>...</span>';
                }

                for ($i = $startPage; $i <= $endPage; $i++):
                    $pageClass = ($i == $currentPage) ? 'current-page' : '';
                    if (isset($pagesWithIncompleteData[$i])) {
                        $pageClass .= ' incomplete-page';
                    }
                    ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $pageClass; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php
                if ($endPage < $totalPages) {
                    echo '<span>...</span>';
                }
                ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" title="Nächste Seite"><i
                            class="fas fa-angle-right"></i></a>
                    <a href="?page=<?php echo $totalPages; ?>" title="Letzte Seite"><i
                            class="fas fa-angle-double-right"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-right"></i></span>
                    <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="report-section collapsible-section"> <!-- Standardmäßig eingeklappt -->
        <h2 class="collapsible-header">Bericht über fehlende Comic-Informationen (Gesamt) <i
                class="fas fa-chevron-right"></i></h2>
        <div class="collapsible-content">
            <?php if (!empty($fullComicData)): // Check if there's any comic data at all ?>
                <div class="comic-table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Typ</th>
                                <th>Name</th>
                                <th>Transkript</th>
                                <th>Kapitel</th>
                                <th>Lowres</th>
                                <th>Hires</th>
                                <th>Thumbnails</th>
                                <th>Social Media</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fullComicData as $id => $data):
                                // Determine if each field is missing
                                $isTypeMissing = empty($data['type']);
                                $isNameMissing = empty($data['name']);
                                $transcriptContent = trim(strip_tags($data['transcript'], '<br>'));
                                $isTranscriptEffectivelyEmpty = (empty($transcriptContent) || $transcriptContent === '<br>' || $transcriptContent === '&nbsp;');
                                // Kapitel 0 soll nicht mehr als fehlend gelten
                                $isChapterMissing = ($data['chapter'] === null || $data['chapter'] < 0);

                                // Get image existence for current comic ID
                                $currentImageExistence = $imageExistenceReport[$id] ?? [];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($id); ?></td>
                                    <td><?php echo $isTypeMissing ? '<i class="fas fa-times-circle icon-missing"></i>' : '<i class="fas fa-check-circle icon-success"></i>'; ?>
                                    </td>
                                    <td><?php echo $isNameMissing ? '<i class="fas fa-times-circle icon-missing"></i>' : '<i class="fas fa-check-circle icon-success"></i>'; ?>
                                    </td>
                                    <td><?php echo $isTranscriptEffectivelyEmpty ? '<i class="fas fa-times-circle icon-missing"></i>' : '<i class="fas fa-check-circle icon-success"></i>'; ?>
                                    </td>
                                    <td><?php echo $isChapterMissing ? '<i class="fas fa-times-circle icon-missing"></i>' : '<i class="fas fa-check-circle icon-success"></i>'; ?>
                                    </td>
                                    <td><?php echo ($currentImageExistence['lowres'] ?? false) ? '<i class="fas fa-check-circle icon-success"></i>' : '<i class="fas fa-times-circle icon-missing"></i>'; ?>
                                    </td>
                                    <td><?php echo ($currentImageExistence['hires'] ?? false) ? '<i class="fas fa-check-circle icon-success"></i>' : '<i class="fas fa-times-circle icon-missing"></i>'; ?>
                                    </td>
                                    <td><?php echo ($currentImageExistence['thumbnails'] ?? false) ? '<i class="fas fa-check-circle icon-success"></i>' : '<i class="fas fa-times-circle icon-missing"></i>'; ?>
                                    </td>
                                    <td><?php echo ($currentImageExistence['socialmedia'] ?? false) ? '<i class="fas fa-check-circle icon-success"></i>' : '<i class="fas fa-times-circle icon-missing"></i>'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($hasIncompleteOtherPages): ?>
                    <p class="missing-other-pages-warning">
                        <i class="fas fa-exclamation-triangle"></i> Hinweis: Es gibt auch unvollständige Comic-Einträge auf
                        anderen Seiten. Bitte überprüfen Sie alle Seiten.
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p>Keine Comic-Daten zum Berichten vorhanden.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<button id="save-all-button" class="floating-save-button">
    <i class="fas fa-save"></i> Alle Änderungen speichern
</button>

<!-- jQuery (Summernote benötigt jQuery) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Summernote JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        console.log("DOMContentLoaded fired."); // Debug-Meldung

        const comicEditForm = document.getElementById('comic-edit-form');
        const comicIdInput = document.getElementById('comic-id');
        const originalComicIdInput = document.getElementById('original-comic-id');
        const comicTypeSelect = document.getElementById('comic-type');
        const comicNameInput = document.getElementById('comic-name');
        const comicNameEmptyCheckbox = document.getElementById('comic-name-empty-checkbox'); // NEU: Checkbox Referenz
        const comicTranscriptTextarea = document.getElementById('comic-transcript');
        const comicChapterInput = document.getElementById('comic-chapter');
        const saveSingleButton = document.getElementById('save-single-button');
        const cancelEditButton = document.getElementById('cancel-edit-button');
        const addComicButton = document.getElementById('add-new-comic-button');
        const comicDataTable = document.getElementById('comic-data-table');
        const messageBoxElement = document.getElementById('message-box');
        const saveAllButton = document.getElementById('save-all-button');
        const formSection = document.querySelector('.form-section');

        let hasUnsavedChanges = false;
        let editedRows = new Map();
        let deletedRows = new Set();
        let summernoteInitialized = false;

        function initializeSummernote() {
            if (!summernoteInitialized) {
                console.log("Initializing Summernote...");
                $('#comic-transcript').summernote({
                    height: 150,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        ['insert', ['link']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ]
                });
                summernoteInitialized = true;
                console.log("Summernote initialized.");
            }
        }

        function resetForm() {
            comicEditForm.reset();
            comicIdInput.value = '';
            originalComicIdInput.value = '';
            comicIdInput.readOnly = false;

            // NEU: Checkbox-Logik beim Zurücksetzen
            comicNameEmptyCheckbox.checked = false;
            comicNameInput.required = true;
            comicNameInput.disabled = false;

            saveSingleButton.textContent = 'Speichern';
            saveSingleButton.classList.remove('edit');
            saveSingleButton.classList.add('button');
            if (summernoteInitialized) {
                $('#comic-transcript').summernote('code', '');
            } else {
                comicTranscriptTextarea.value = '';
            }
            comicChapterInput.value = '';
            formSection.scrollIntoView({ behavior: 'smooth' });
            console.log("Form reset.");
        }

        function showMessage(msg, type) {
            if (messageBoxElement) {
                messageBoxElement.textContent = msg;
                messageBoxElement.className = 'message-box ' + type;
                messageBoxElement.style.display = 'block';
                setTimeout(() => {
                    messageBoxElement.style.display = 'none';
                }, 5000);
                console.log("Message displayed:", msg, "Type:", type);
            } else {
                console.warn("Message box element not found, falling back to console: " + msg);
            }
        }

        function setUnsavedChanges(status) {
            hasUnsavedChanges = status;
            if (saveAllButton) {
                if (status) {
                    saveAllButton.classList.remove('disabled');
                } else {
                    saveAllButton.classList.add('disabled');
                }
            }
            console.log("Unsaved changes status:", hasUnsavedChanges);
        }

        window.addEventListener('beforeunload', function (event) {
            if (hasUnsavedChanges) {
                event.preventDefault();
                event.returnValue = '';
                console.log("Beforeunload: Unsaved changes detected, preventing navigation.");
                return '';
            }
        });

        function htmlspecialchars(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        // NEU: Event Listener für die "Name leer lassen" Checkbox
        comicNameEmptyCheckbox.addEventListener('change', function () {
            if (this.checked) {
                comicNameInput.value = ''; // Feld leeren
                comicNameInput.required = false; // "required" entfernen
                comicNameInput.disabled = true; // Feld deaktivieren
            } else {
                comicNameInput.required = true; // "required" wiederherstellen
                comicNameInput.disabled = false; // Feld aktivieren
            }
        });

        comicDataTable.addEventListener('click', function (event) {
            const target = event.target;
            const editButton = target.closest('.edit-button');
            const deleteButton = target.closest('.delete-button');

            if (editButton) {
                console.log("Edit button clicked.");
                const row = editButton.closest('tr');
                const comicId = row.dataset.comicId;
                const comicType = row.querySelector('.comic-type-display').textContent;
                const comicName = row.querySelector('.comic-name-display').textContent;
                const comicTranscript = row.querySelector('.comic-transcript-display').textContent;
                const comicChapter = row.querySelector('.comic-chapter-display').textContent;

                comicIdInput.value = comicId;
                originalComicIdInput.value = comicId;
                comicIdInput.readOnly = true;
                comicTypeSelect.value = comicType;
                comicNameInput.value = comicName;

                // NEU: Checkbox-Status basierend auf dem Namen setzen
                if (comicName === '') {
                    comicNameEmptyCheckbox.checked = true;
                } else {
                    comicNameEmptyCheckbox.checked = false;
                }
                // Trigger change event to apply disabled/required state
                comicNameEmptyCheckbox.dispatchEvent(new Event('change'));

                initializeSummernote();
                $('#comic-transcript').summernote('code', comicTranscript);

                comicChapterInput.value = comicChapter;

                saveSingleButton.textContent = 'Änderungen speichern';
                saveSingleButton.classList.add('edit');
                saveSingleButton.classList.remove('button');

                if (!formSection.classList.contains('expanded')) {
                    formSection.classList.add('expanded');
                    console.log("Form section expanded.");
                }
                formSection.scrollIntoView({ behavior: 'smooth' });
            } else if (deleteButton) {
                console.log("Delete button clicked.");
                const row = deleteButton.closest('tr');
                const comicId = row.dataset.comicId;

                const confirmDelete = confirm(`Sind Sie sicher, dass Sie den Comic-Eintrag mit der ID ${comicId} löschen möchten? Diese Änderung wird erst nach dem Klick auf "Alle Änderungen speichern" permanent.`);
                if (confirmDelete) {
                    row.style.textDecoration = 'line-through';
                    row.style.opacity = '0.5';
                    row.querySelectorAll('button').forEach(btn => btn.disabled = true);

                    deletedRows.add(comicId);
                    editedRows.delete(comicId);
                    console.log("Comic-ID " + comicId + " marked for deletion. deletedRows:", deletedRows);

                    setUnsavedChanges(true);
                    showMessage(`Comic-Eintrag ${comicId} zum Löschen markiert. Klicken Sie auf "Alle Änderungen speichern", um dies zu bestätigen.`, 'success');
                } else {
                    showMessage(`Löschen für Comic-Eintrag ${comicId} abgebrochen.`, 'info');
                }
            }
        });

        addComicButton.addEventListener('click', function () {
            console.log("Add new comic button clicked.");
            resetForm();
            saveSingleButton.textContent = 'Hinzufügen';
            saveSingleButton.classList.add('button');
            saveSingleButton.classList.remove('edit');
            comicIdInput.readOnly = false;

            initializeSummernote();

            if (!formSection.classList.contains('expanded')) {
                formSection.classList.add('expanded');
                console.log("Form section expanded for new entry.");
            }
            formSection.scrollIntoView({ behavior: 'smooth' });
        });

        cancelEditButton.addEventListener('click', function () {
            console.log("Cancel button clicked.");
            resetForm();
            showMessage('Bearbeitung abgebrochen.', 'info');
        });

        comicEditForm.addEventListener('submit', function (event) {
            event.preventDefault();
            console.log("Form submit button clicked.");

            const comicId = comicIdInput.value.trim();
            const originalComicId = originalComicIdInput.value.trim();
            const comicType = comicTypeSelect.value.trim();
            const comicName = comicNameInput.value.trim();
            const comicTranscript = summernoteInitialized ? $('#comic-transcript').summernote('code').trim() : comicTranscriptTextarea.value.trim();
            const comicChapter = comicChapterInput.value.trim();

            const parsedChapter = parseFloat(comicChapter.replace(',', '.'));

            // NEU: Validierung angepasst
            if (!comicId || !comicType || (!comicName && !comicNameEmptyCheckbox.checked) || (comicChapter !== '' && (isNaN(parsedChapter) || parsedChapter < 0))) {
                showMessage('Bitte füllen Sie alle Felder (ID, Typ) aus. Name muss entweder ausgefüllt oder als "leer" markiert sein. Kapitel muss eine Zahl (>= 0) sein oder leer bleiben.', 'error');
                console.error("Validation failed: Missing required fields or invalid chapter.");
                return;
            }
            if (!/^\d{8}$/.test(comicId)) {
                showMessage('Comic ID muss eine 8-stellige Zahl (JJJJMMTT) sein.', 'error');
                console.error("Validation failed: Invalid Comic ID format.");
                return;
            }

            const comicData = {
                comic_id: comicId,
                comic_type: comicType,
                comic_name: comicName,
                comic_transcript: comicTranscript,
                comic_chapter: comicChapter
            };
            console.log("Comic data prepared:", comicData);

            if (originalComicId && originalComicId !== comicId) {
                deletedRows.add(originalComicId);
                editedRows.delete(originalComicId);
                console.log("Original ID marked for deletion due to ID change:", originalComicId);
            }

            editedRows.set(comicId, comicData);
            deletedRows.delete(comicId);
            console.log("Edited rows map updated:", editedRows);

            let row = comicDataTable.querySelector(`tr[data-comic-id="${htmlspecialchars(originalComicId || comicId)}"]`);
            if (!row) {
                row = comicDataTable.tBodies[0].insertRow();
                row.dataset.comicId = comicId;
                row.innerHTML = `
                <td class="comic-id-display"></td>
                <td><span class="editable-field comic-type-display"></span></td>
                <td><span class="editable-field comic-name-display"></span></td>
                <td><span class="editable-field comic-transcript-display"></span></td>
                <td><span class="editable-field comic-chapter-display"></span></td>
                <td class="actions">
                    <button type="button" class="edit-button button edit" title="Bearbeiten"><i class="fas fa-edit"></i></button>
                    <button type="button" class="delete-button button delete" title="Löschen"><i class="fas fa-trash-alt"></i></button>
                </td>
            `;
                console.log("New row added visually for ID:", comicId);
            }

            row.dataset.comicId = comicId;
            row.querySelector('.comic-id-display').textContent = comicId;
            row.querySelector('.comic-type-display').textContent = comicType;
            row.querySelector('.comic-name-display').textContent = comicName;
            row.querySelector('.comic-transcript-display').textContent = comicTranscript;
            row.querySelector('.comic-chapter-display').textContent = comicChapter;

            row.style.textDecoration = 'none';
            row.style.opacity = '1';
            row.querySelectorAll('button').forEach(btn => btn.disabled = false);

            const isTranscriptEffectivelyEmpty = (comicTranscript === '' || comicTranscript === '<p><br></p>' || comicTranscript === '&nbsp;');
            const isChapterEffectivelyEmpty = (comicChapter === '' || isNaN(parseFloat(comicChapter.replace(',', '.'))) || parseFloat(comicChapter.replace(',', '.')) < 0);

            const missingFields = [];
            if (comicType === '') missingFields.push('type');
            if (comicName === '') missingFields.push('name'); // Markierung bleibt bestehen, wie gewünscht
            if (isTranscriptEffectivelyEmpty) missingFields.push('transcript');
            if (isChapterEffectivelyEmpty) missingFields.push('chapter');

            if (missingFields.length > 0) {
                row.classList.add('missing-info-row');
                if (missingFields.includes('type')) row.querySelector('.comic-type-display').classList.add('missing-info'); else row.querySelector('.comic-type-display').classList.remove('missing-info');
                if (missingFields.includes('name')) row.querySelector('.comic-name-display').classList.add('missing-info'); else row.querySelector('.comic-name-display').classList.remove('missing-info');
                if (missingFields.includes('transcript')) row.querySelector('.comic-transcript-display').classList.add('missing-info'); else row.querySelector('.comic-transcript-display').classList.remove('missing-info');
                if (missingFields.includes('chapter')) row.querySelector('.comic-chapter-display').classList.add('missing-info'); else row.querySelector('.comic-chapter-display').classList.remove('missing-info');
                console.log("Row " + comicId + " marked as missing info. Fields:", missingFields);
            } else {
                row.classList.remove('missing-info-row');
                row.querySelectorAll('.missing-info').forEach(el => el.classList.remove('missing-info'));
                console.log("Row " + comicId + " has all info, removed missing-info class.");
            }

            setUnsavedChanges(true);
            resetForm();
            showMessage('Eintrag lokal gespeichert.', 'success');
            showMessage('Ihre Änderungen sind lokal gespeichert. Bitte klicken Sie auf "Alle Änderungen speichern", um sie permanent zu sichern.', 'warning');
        });

        saveAllButton.addEventListener('click', function () {
            console.log("Save All button clicked.");
            if (!hasUnsavedChanges) {
                showMessage('Keine ungespeicherten Änderungen vorhanden.', 'info');
                return;
            }

            const dataToSend = {
                pages: Array.from(editedRows.values()),
                deleted_ids: Array.from(deletedRows)
            };
            console.log("Data to send to server:", dataToSend);

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dataToSend)
            })
                .then(response => {
                    if (!response.ok) {
                        console.error(`HTTP-Fehler! Status: ${response.status}`);
                        throw new Error(`HTTP-Fehler! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        showMessage(data.message + ' Die Seite wird neu geladen, um die Änderungen anzuzeigen.', 'success');
                        setUnsavedChanges(false);
                        editedRows.clear();
                        deletedRows.clear();
                        console.log("Fetch successful, reloading page.");
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showMessage('Fehler beim Speichern der Daten: ' + data.message, 'error');
                        console.error('Server responded with error:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showMessage('Ein Netzwerkfehler ist aufgetreten oder die Serverantwort war unerwartet: ' + error.message, 'error');
                });
        });

        setUnsavedChanges(false);

        comicDataTable.addEventListener('dblclick', function (event) {
            const target = event.target;
            if (target.classList.contains('editable-field')) {
                const row = target.closest('tr');
                const comicId = row.dataset.comicId;
                const fieldName = target.classList[1].replace('-display', '');
                console.log("Double-clicked editable field:", fieldName, "for ID:", comicId);

                if (target.classList.contains('editing')) {
                    console.log("Field already in editing mode, returning.");
                    return;
                }

                document.querySelectorAll('.editable-field.editing').forEach(field => {
                    field.classList.remove('editing');
                    const originalContent = field.dataset.originalContent;
                    if (originalContent !== undefined) {
                        field.innerHTML = originalContent;
                        console.log("Closed other editing field, restored content.");
                    }
                });

                target.dataset.originalContent = target.innerHTML;
                target.classList.add('editing');

                let inputElement;
                if (fieldName === 'comic-type') {
                    inputElement = document.createElement('select');
                    <?php foreach ($comicTypeOptions as $option): ?>
                        var optionType = document.createElement('option');
                        optionType.value = "<?php echo htmlspecialchars($option); ?>";
                        optionType.textContent = "<?php echo htmlspecialchars($option); ?>";
                        inputElement.appendChild(optionType);
                    <?php endforeach; ?>
                    inputElement.value = target.textContent;
                    console.log("Created select input for comic-type.");
                } else if (fieldName === 'comic-chapter') {
                    inputElement = document.createElement('input');
                    inputElement.type = 'text';
                    inputElement.value = target.textContent;
                    inputElement.style.width = '100%';
                    inputElement.style.boxSizing = 'border-box';
                    inputElement.placeholder = "Kapitel (z.B. 0, 6, 6.1)";
                    console.log("Created text input for comic-chapter.");
                } else if (fieldName === 'comic-transcript') {
                    const editButton = row.querySelector('.edit-button');
                    if (editButton) {
                        editButton.click();
                        target.classList.remove('editing');
                        console.log("Transcript double-clicked, redirecting to full form edit.");
                        return;
                    }
                } else {
                    inputElement = document.createElement('input');
                    inputElement.type = 'text';
                    inputElement.value = target.textContent;
                    inputElement.style.width = '100%';
                    inputElement.style.boxSizing = 'border-box';
                    console.log("Created text input for field:", fieldName);
                }

                if (inputElement) {
                    target.innerHTML = '';
                    target.appendChild(inputElement);
                    inputElement.focus();

                    inputElement.addEventListener('blur', function () {
                        const newValue = inputElement.value.trim();
                        const originalValue = target.dataset.originalContent.trim();
                        console.log("Field blurred. New value:", newValue, "Original value:", originalValue);

                        target.classList.remove('editing');
                        target.innerHTML = htmlspecialchars(newValue);

                        if (newValue !== originalValue) {
                            let currentData = editedRows.get(comicId) || {
                                comic_id: comicId,
                                comic_type: row.querySelector('.comic-type-display').textContent,
                                comic_name: row.querySelector('.comic-name-display').textContent,
                                comic_transcript: row.querySelector('.comic-transcript-display').textContent,
                                comic_chapter: row.querySelector('.comic-chapter-display').textContent
                            };

                            if (fieldName === 'comic-type') currentData.comic_type = newValue;
                            else if (fieldName === 'comic-name') currentData.comic_name = newValue;
                            else if (fieldName === 'comic-transcript') currentData.comic_transcript = newValue;
                            else if (fieldName === 'comic-chapter') currentData.comic_chapter = newValue;

                            editedRows.set(comicId, currentData);
                            setUnsavedChanges(true);
                            showMessage('Änderung für ' + comicId + ' (' + fieldName + ') lokal gespeichert. Klicken Sie auf "Alle Änderungen speichern".', 'info');
                            console.log("Local change recorded:", currentData);

                            const isTranscriptEffectivelyEmpty = (currentData.comic_transcript === '' || currentData.comic_transcript === '<p><br></p>' || currentData.comic_transcript === '&nbsp;');
                            const isChapterEffectivelyEmpty = (currentData.comic_chapter === '' || isNaN(parseFloat(currentData.comic_chapter.replace(',', '.'))) || parseFloat(currentData.comic_chapter.replace(',', '.')) < 0);

                            if (fieldName === 'comic-type') {
                                if (currentData.comic_type === '') target.classList.add('missing-info');
                                else target.classList.remove('missing-info');
                            } else if (fieldName === 'comic-name') {
                                if (currentData.comic_name === '') target.classList.add('missing-info');
                                else target.classList.remove('missing-info');
                            } else if (fieldName === 'comic-transcript') {
                                if (isTranscriptEffectivelyEmpty) target.classList.add('missing-info');
                                else target.classList.remove('missing-info');
                            } else if (fieldName === 'comic-chapter') {
                                if (isChapterEffectivelyEmpty) target.classList.add('missing-info');
                                else target.classList.remove('missing-info');
                            }

                            const rowType = row.querySelector('.comic-type-display').textContent;
                            const rowName = row.querySelector('.comic-name-display').textContent;
                            const rowTranscript = row.querySelector('.comic-transcript-display').textContent;
                            const rowChapter = row.querySelector('.comic-chapter-display').textContent;

                            const rowIsTranscriptEffectivelyEmpty = (rowTranscript === '' || rowTranscript === '<p><br></p>' || rowTranscript === '&nbsp;');
                            const rowIsChapterEffectivelyEmpty = (rowChapter === '' || isNaN(parseFloat(rowChapter.replace(',', '.'))) || parseFloat(rowChapter.replace(',', '.')) < 0);

                            if (rowType === '' || rowName === '' || rowIsTranscriptEffectivelyEmpty || rowIsChapterEffectivelyEmpty) {
                                row.classList.add('missing-info-row');
                                console.log("Row " + comicId + " now has missing info after inline edit.");
                            } else {
                                row.classList.remove('missing-info-row');
                                console.log("Row " + comicId + " now complete after inline edit.");
                            }

                        } else {
                            target.innerHTML = originalValue;
                            console.log("No change in value, restored original content.");
                        }
                    });

                    if (inputElement.tagName === 'INPUT' || inputElement.tagName === 'TEXTAREA') {
                        inputElement.addEventListener('keypress', function (e) {
                            if (e.key === 'Enter' && inputElement.tagName === 'INPUT') {
                                inputElement.blur();
                                console.log("Enter key pressed, blurring input.");
                            }
                        });
                    }
                }
            }
        });

        document.querySelectorAll('.collapsible-header').forEach(header => {
            const section = header.closest('.collapsible-section');
            const icon = header.querySelector('i');

            if (section.classList.contains('expanded')) {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }

            header.addEventListener('click', function () {
                const wasExpanded = section.classList.contains('expanded');
                section.classList.toggle('expanded');
                if (section.classList.contains('expanded')) {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                    if (section.classList.contains('form-section') && !wasExpanded) {
                        initializeSummernote();
                    }
                    if (section.classList.contains('form-section')) {
                        section.scrollIntoView({ behavior: 'smooth' });
                    }
                } else {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                }
                console.log("Collapsible header clicked. Section expanded status:", section.classList.contains('expanded'));
            });
        });
    });
</script>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
    if ($debugMode)
        error_log("DEBUG: Footer in data_editor_comic.php eingebunden.");
} else {
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>