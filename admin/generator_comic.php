<?php
/**
 * Dies ist die Administrationsseite für den Comic-Seiten-Generator im Admin-bereich.
 * Sie überprüft fehlende Comic-PHP-Dateien basierend auf der comic_var.json und den Bilddateien
 * und bietet die Möglichkeit, diese automatisiert zu erstellen.
 *
 * Angepasst an das neue Grid-Design, Hell/Dunkel-Modus, schwebende Buttons,
 * Ladekreis und Fortschrittsanzeige, analog zu den Bildgeneratoren.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG (enthält Nonce und CSRF-Setup) ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';
$comicLowresDirPath = __DIR__ . '/../assets/comic_lowres/';
$comicPagesDirPath = __DIR__ . '/../comic/'; // Pfad zum Ordner, wo die Comic-PHP-Dateien liegen
if ($debugMode) {
    error_log("DEBUG: Pfade definiert: comicVarJsonPath=" . $comicVarJsonPath . ", comicLowresDirPath=" . $comicLowresDirPath . ", comicPagesDirPath=" . $comicPagesDirPath);
}

// --- Hilfsfunktionen ---

/**
 * Liest die Comic-Daten aus der JSON-Datei.
 * @param string $filePath Der Pfad zur JSON-Datei.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array|null Die dekodierten Daten als assoziatives Array oder null bei Fehler/nicht existent.
 */
function getComicData(string $filePath, bool $debugMode): ?array
{
    if ($debugMode)
        error_log("DEBUG: getComicData() aufgerufen für: " . basename($filePath));
    if (!file_exists($filePath)) {
        if ($debugMode)
            error_log("DEBUG: JSON-Datei nicht gefunden: " . $filePath);
        return null;
    }
    $content = file_get_contents($filePath);
    if ($content === false) {
        error_log("Fehler beim Lesen der JSON-Datei: " . $filePath);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Lesen des Inhalts von: " . $filePath);
        return null;
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren der JSON-Datei: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Dekodieren der JSON-Datei: " . json_last_error_msg());
        return null;
    }
    if ($debugMode)
        error_log("DEBUG: JSON-Datei erfolgreich geladen und geparst: " . basename($filePath));
    return is_array($data) ? $data : [];
}

/**
 * Ermittelt alle Comic-IDs basierend auf den Bilddateien im lowres-Ordner.
 * @param string $dirPath Der Pfad zum lowres-Comic-Ordner.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste der Comic-IDs (Dateinamen ohne Erweiterung), sortiert.
 */
function getComicIdsFromImages(string $dirPath, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: getComicIdsFromImages() aufgerufen für: " . $dirPath);
    $imageIds = [];
    if (is_dir($dirPath)) {
        $files = scandir($dirPath);
        if ($files === false) {
            if ($debugMode)
                error_log("DEBUG: scandir() fehlgeschlagen für " . $dirPath);
            return [];
        }
        foreach ($files as $file) {
            // Ignoriere . und .. sowie versteckte Dateien und "in_translation" Bilder
            if ($file === '.' || $file === '..' || substr($file, 0, 1) === '.' || strpos($file, 'in_translation') !== false) {
                if ($debugMode)
                    error_log("DEBUG: Datei übersprungen ('.' oder '..' oder versteckt oder 'in_translation'): " . $file);
                continue;
            }
            // Extrahiere den Dateinamen ohne Erweiterung (z.B. "20250604" aus "20250604.png")
            $info = pathinfo($file);
            // Stellt sicher, dass es ein Datum im FormatYYYYMMDD ist und eine gültige Bild-Extension hat
            if (isset($info['filename']) && preg_match('/^\d{8}$/', $info['filename']) && isset($info['extension']) && in_array(strtolower($info['extension']), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $imageIds[] = $info['filename'];
                if ($debugMode)
                    error_log("DEBUG: Comic-ID aus Bild gefunden: " . $info['filename']);
            } else {
                if ($debugMode)
                    error_log("DEBUG: Datei übersprungen (ungültiges Format/Extension): " . $file);
            }
        }
    } else {
        if ($debugMode)
            error_log("DEBUG: Comic-Lowres-Verzeichnis nicht gefunden oder ist kein Verzeichnis: " . $dirPath);
    }
    sort($imageIds); // IDs sortieren, um eine konsistente Reihenfolge zu gewährleisten
    if ($debugMode)
        error_log("DEBUG: " . count($imageIds) . " Comic-IDs aus Bildern gefunden und sortiert.");
    return $imageIds;
}

/**
 * Überprüft, welche Comic-PHP-Dateien fehlen.
 * @param array $comicIds Die Liste der erwarteten Comic-IDs.
 * @param string $comicPagesDir Der Pfad zum Comic-Seiten-Ordner.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste der fehlenden Comic-IDs.
 */
function getMissingComicPageFiles(array $comicIds, string $comicPagesDir, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: getMissingComicPageFiles() aufgerufen.");
    $missingFiles = [];
    foreach ($comicIds as $id) {
        $filePath = $comicPagesDir . $id . '.php';
        if (!file_exists($filePath)) {
            $missingFiles[] = $id;
            if ($debugMode)
                error_log("DEBUG: Fehlende Comic-Seite gefunden: " . $id . ".php");
        }
    }
    if ($debugMode)
        error_log("DEBUG: " . count($missingFiles) . " fehlende Comic-PHP-Dateien gefunden.");
    return $missingFiles;
}

/**
 * Erstellt eine einzelne Comic-PHP-Datei.
 * @param string $comicId Die ID des Comics, für das eine Datei erstellt werden soll.
 * @param string $comicPagesDir Der Pfad zum Comic-Seiten-Ordner.
 * @param bool $debugMode Debug-Modus Flag.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function createSingleComicPageFile(string $comicId, string $comicPagesDir, bool $debugMode): bool
{
    if ($debugMode)
        error_log("DEBUG: createSingleComicPageFile() aufgerufen für Comic-ID: " . $comicId);
    // Sicherstellen, dass das Verzeichnis existiert
    if (!is_dir($comicPagesDir)) {
        if ($debugMode)
            error_log("DEBUG: Comic-Seiten-Verzeichnis existiert nicht, versuche zu erstellen: " . $comicPagesDir);
        if (!mkdir($comicPagesDir, 0777, true)) {
            error_log("Fehler: Comic-Seiten-Verzeichnis nicht erstellbar: " . $comicPagesDir);
            if ($debugMode)
                error_log("DEBUG: Fehler: Comic-Seiten-Verzeichnis nicht erstellbar: " . $comicPagesDir);
            return false;
        }
        if ($debugMode)
            error_log("DEBUG: Comic-Seiten-Verzeichnis erfolgreich erstellt: " . $comicPagesDir);
    }

    $filePath = $comicPagesDir . $comicId . '.php';
    $fileContent = "<?php require_once __DIR__ . '/../src/components/comic_page_renderer.php'; ?>";
    if ($debugMode)
        error_log("DEBUG: Versuche Datei zu erstellen: " . $filePath);

    if (file_put_contents($filePath, $fileContent) !== false) {
        if ($debugMode)
            error_log("DEBUG: Comic-Seite erfolgreich erstellt: " . $filePath);
        return true;
    } else {
        error_log("Fehler beim Erstellen der Datei: " . $filePath);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Erstellen der Datei: " . $filePath);
        return false;
    }
}


// --- AJAX-Anfrage-Handler ---
// Dieser Block wird nur ausgeführt, wenn eine POST-Anfrage mit der Aktion 'create_single_comic_page' gesendet wird.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_single_comic_page') {
    // SICHERHEIT: CSRF-Token validieren
    verify_csrf_token();

    if ($debugMode)
        error_log("DEBUG: AJAX-Anfrage 'create_single_comic_page' erkannt.");
    // Leere und beende den Output Buffer, um sicherzustellen, dass keine unerwünschten Ausgaben gesendet werden.
    ob_end_clean();
    // Temporär Fehleranzeige deaktivieren und Error Reporting unterdrücken, um JSON-Ausgabe nicht zu stören.
    ini_set('display_errors', 0);
    error_reporting(0);

    header('Content-Type: application/json'); // Wichtig für JSON-Antwort
    $response = ['success' => false, 'message' => ''];

    $comicId = $_POST['comic_id'] ?? '';
    if (empty($comicId)) {
        $response['message'] = 'Keine Comic-ID für die Generierung angegeben.';
        error_log("AJAX-Anfrage: Keine Comic-ID angegeben.");
        if ($debugMode)
            error_log("DEBUG: AJAX: Keine Comic-ID angegeben, sende Fehlerantwort.");
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    if ($debugMode)
        error_log("DEBUG: AJAX: Starte Generierung für Comic-ID: " . $comicId);

    // Pfad für die einzelne Generierung (muss hier neu definiert werden, da es ein separater Request ist)
    $comicPagesDirPath = __DIR__ . '/../comic/';

    if (createSingleComicPageFile($comicId, $comicPagesDirPath, $debugMode)) {
        $response['success'] = true;
        $response['message'] = 'Comic-Seite für ' . $comicId . ' erfolgreich erstellt.';
        $response['comicId'] = $comicId;
        if ($debugMode)
            error_log("DEBUG: AJAX: Comic-Seite für Comic-ID " . $comicId . " erfolgreich generiert.");
    } else {
        $response['message'] = 'Fehler beim Erstellen der Comic-Seite für ' . $comicId . '.';
        error_log("AJAX-Anfrage: Fehler beim Erstellen der Comic-Seite für Comic-ID '$comicId'.");
        if ($debugMode)
            error_log("DEBUG: AJAX: Fehler bei der Generierung für Comic-ID " . $comicId . ".");
        http_response_code(500);
    }

    // Überprüfe, ob json_encode einen Fehler hatte
    $jsonOutput = json_encode($response);
    if ($jsonOutput === false) {
        $jsonError = json_last_error_msg();
        error_log("AJAX-Anfrage: json_encode Fehler für Comic-ID '$comicId': " . $jsonError);
        if ($debugMode)
            error_log("DEBUG: AJAX: JSON-Encoding fehlgeschlagen für Comic-ID " . $comicId . ": " . $jsonError);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Interner Serverfehler: JSON-Encoding fehlgeschlagen.']);
    } else {
        echo $jsonOutput;
    }
    exit; // WICHTIG: Beende die Skriptausführung für AJAX-Anfragen hier!
}
// --- Ende AJAX-Anfrage-Handler ---


// --- Statusermittlung für die Anzeige (unabhängig von POST-Requests) ---
// Leere den Output Buffer und sende den Inhalt, der bis hierhin gesammelt wurde.
ob_end_flush();
if ($debugMode)
    error_log("DEBUG: Normale Seitenladung (kein AJAX-Call). Output Buffer wird geleert und ausgegeben.");

$comicIdsFromImages = getComicIdsFromImages($comicLowresDirPath, $debugMode);
$comicDataFromJson = getComicData($comicVarJsonPath, $debugMode);

$allComicIds = [];
if ($comicDataFromJson) {
    $allComicIds = array_unique(array_merge(array_keys($comicDataFromJson), $comicIdsFromImages));
    if ($debugMode)
        error_log("DEBUG: Comic-Daten aus JSON und Bildern zusammengeführt.");
} else {
    $allComicIds = $comicIdsFromImages;
    if ($debugMode)
        error_log("DEBUG: Comic-Daten nur aus Bildern verwendet (JSON nicht verfügbar).");
}
sort($allComicIds);
if ($debugMode)
    error_log("DEBUG: Alle Comic-IDs sortiert. Anzahl: " . count($allComicIds));

$missingComicPages = getMissingComicPageFiles($allComicIds, $comicPagesDirPath, $debugMode);
if ($debugMode)
    error_log("DEBUG: " . count($missingComicPages) . " fehlende Comic-Seiten für Anzeige.");


// --- HTML-Struktur und Anzeige ---

// Parameter für den Header
$pageTitle = 'Adminbereich - Comic Generator';
$pageHeader = 'Comic-Seiten-Generator';
$siteDescription = 'Generiert fehlende Comic-PHP-Seiten basierend auf vorhandenen Bildern und JSON-Daten.';

// Binde den gemeinsamen Header ein.
$robotsContent = 'noindex, nofollow';
if (file_exists($headerPath)) {
    include $headerPath;
    if ($debugMode)
        error_log("DEBUG: Header in generator_comic.php eingebunden.");
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<article>
    <div class="admin-form-container">
        <header>
            <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
        </header>

        <div class="content-section">
            <h2>Status der Comic-Seiten</h2>

            <!-- Container für die Buttons -->
            <div id="fixed-buttons-container">
                <button type="button" id="generate-pages-button" <?php echo empty($missingComicPages) ? 'disabled' : ''; ?>>Fehlende Comic-Seiten erstellen</button>
                <button type="button" id="toggle-pause-resume-button" style="display:none;"></button>
            </div>

            <!-- Ergebnisse der Generierung -->
            <div id="generation-results-section" style="margin-top: 20px; display: none;">
                <h2 style="margin-top: 20px;">Ergebnisse der Generierung</h2>
                <p id="overall-status-message" class="status-message"></p>
                <div id="created-items-container" class="generated-items-grid">
                    <!-- Hier werden die erfolgreich generierten Dateien angezeigt -->
                </div>
                <p class="status-message status-red" style="display: none;" id="error-header-message">Fehler bei der
                    Generierung:</p>
                <ul id="generation-errors-list">
                    <!-- Hier werden Fehler angezeigt -->
                </ul>
            </div>

            <!-- Lade-Indikator und Fortschrittsanzeige -->
            <div id="loading-spinner" style="display: none; text-align: center; margin-top: 20px;">
                <div class="spinner"></div>
                <p id="progress-text">Generiere Comic-Seiten...</p>
            </div>

            <?php if (empty($allComicIds)): ?>
                <p class="status-message status-orange">Es wurden keine Comic-IDs in <code>comic_var.json</code> oder im
                    <code>./assets/comic_lowres/</code> Ordner gefunden. Bitte stellen Sie sicher, dass Comics vorhanden
                    sind.
                </p>
                <?php if ($debugMode)
                    error_log("DEBUG: Keine Comic-IDs gefunden, Statusmeldung angezeigt."); ?>
            <?php elseif (empty($missingComicPages)): ?>
                <p class="status-message status-green">Alle Comic-PHP-Dateien scheinen vorhanden zu sein.</p>
                <?php if ($debugMode)
                    error_log("DEBUG: Alle Comic-PHP-Dateien vorhanden, Statusmeldung angezeigt."); ?>
            <?php else: ?>
                <p class="status-message status-red">Es fehlen <strong><?php echo count($missingComicPages); ?></strong>
                    Comic-PHP-Dateien:</p>
                <div id="missing-pages-grid" class="missing-items-grid">
                    <?php foreach ($missingComicPages as $id): ?>
                        <span class="missing-item"
                            data-comic-id="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($id); ?>.php</span>
                    <?php endforeach; ?>
                </div>
                <?php if ($debugMode)
                    error_log("DEBUG: Fehlende Comic-PHP-Dateien angezeigt."); ?>
            <?php endif; ?>
        </div>
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    /* CSS-Variablen für Light- und Dark-Mode */
    :root {
        /* Light Mode Defaults */
        --missing-grid-border-color: #e0e0e0;
        --missing-grid-bg-color: #f9f9f9;
        --missing-item-bg-color: #e9e9e9;
        --missing-item-text-color: #333;
        /* Standardtextfarbe */
        --generated-item-bg-color: #d4edda;
        --generated-item-text-color: #155724;
        --generated-item-border-color: #c3e6cb;
    }

    body.theme-night {
        /* Dark Mode Overrides */
        --missing-grid-border-color: #045d81;
        --missing-grid-bg-color: #03425b;
        --missing-item-bg-color: #025373;
        --missing-item-text-color: #f0f0f0;
        /* Hellerer Text für Dark Mode */
        --generated-item-bg-color: #2a6177;
        --generated-item-text-color: #fff;
        --generated-item-border-color: #48778a;
    }

    /* Allgemeine Statusmeldungen */
    .status-message {
        padding: 8px 12px;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .status-green {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-orange {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .status-red {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Neue Button-Stile */
    #generate-pages-button,
    #toggle-pause-resume-button {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 15px;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    #generate-pages-button {
        background-color: #007bff;
        color: white;
    }

    #generate-pages-button:hover {
        background-color: #0056b3;
    }

    #generate-pages-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        cursor: not-allowed;
    }

    .status-red-button {
        background-color: #dc3545;
        /* Bootstrap-Rot */
        color: white;
        border: 1px solid #dc3545;
    }

    .status-red-button:hover {
        background-color: #c82333;
    }

    .status-red-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #e9ecef;
        cursor: not-allowed;
    }

    .status-green-button {
        background-color: #28a745;
        /* Bootstrap-Grün */
        color: white;
        border: 1px solid #28a745;
    }

    .status-green-button:hover {
        background-color: #218838;
    }

    .status-green-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #e9ecef;
        cursor: not-allowed;
    }

    /* Spinner CSS */
    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border-left-color: #09f;
        animation: spin 1s ease infinite;
        margin: 0 auto 10px auto;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Grid Layout für generierte Elemente */
    .generated-items-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
        padding-bottom: 20px;
    }

    .generated-item {
        text-align: center;
        border: 1px solid var(--generated-item-border-color);
        padding: 8px 12px;
        border-radius: 8px;
        background-color: var(--generated-item-bg-color);
        color: var(--generated-item-text-color);
        font-size: 0.9em;
        word-break: break-all;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 120px;
        /* Mindestbreite für bessere Lesbarkeit */
        max-width: 200px;
        /* Maximale Breite, bevor Umbruch */
        flex-grow: 1;
        /* Elemente können wachsen, um den Platz zu füllen */
    }

    /* Stil für den Button-Container - initial statisch, wird per JS zu 'fixed' */
    #fixed-buttons-container {
        z-index: 1000;
        /* Stellt sicher, dass die Buttons über anderen Inhalten liegen */
        display: flex;
        /* Für nebeneinanderliegende Buttons */
        gap: 10px;
        /* Abstand zwischen den Buttons */
        margin-top: 20px;
        /* Fügt etwas Abstand hinzu, wenn die Buttons statisch sind */
        margin-bottom: 20px;
        /* Abstand nach unten, wenn statisch */
        justify-content: flex-end;
        /* Richtet die Buttons im statischen Zustand am rechten Rand aus */
        /* top und right werden dynamisch per JavaScript gesetzt, position wird auch per JS gesetzt */
    }

    /* Anpassung für kleinere Bildschirme, falls die Buttons zu viel Platz einnehmen */
    @media (max-width: 768px) {
        #fixed-buttons-container {
            flex-direction: column;
            /* Buttons untereinander auf kleinen Bildschirmen */
            gap: 5px;
            align-items: flex-end;
            /* Auch im Spalten-Layout rechts ausrichten */
        }
    }

    /* NEUE STILE FÜR DIE KOMPAKTE LISTE DER FEHLENDEN ELEMENTE */
    .missing-items-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        /* Abstand zwischen den Elementen */
        max-height: 300px;
        /* Maximale Höhe */
        overflow-y: auto;
        /* Scrollbar, wenn Inhalt die Höhe überschreitet */
        border: 1px solid var(--missing-grid-border-color);
        /* Dynamischer Rahmen */
        padding: 10px;
        border-radius: 5px;
        background-color: var(--missing-grid-bg-color);
        /* Dynamischer Hintergrund */
    }

    .missing-item {
        background-color: var(--missing-item-bg-color);
        /* Dynamischer Hintergrund */
        color: var(--missing-item-text-color);
        /* Dynamische Textfarbe */
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.9em;
        white-space: nowrap;
        /* Verhindert Zeilenumbruch innerhalb eines Eintrags */
        overflow: hidden;
        text-overflow: ellipsis;
        /* Fügt "..." hinzu, wenn der Text zu lang ist */
        max-width: 150px;
        /* Begrenzt die Breite jedes Eintrags */
        flex-shrink: 0;
        /* Verhindert, dass Elemente schrumpfen */
    }

    /* Bestehende Admin-Formular-Stile beibehalten und anpassen */
    .admin-form-container {
        max-width: 825px;
        /* Angepasst an article Breite */
        margin: 20px auto;
        padding: 20px;
        border: 1px solid rgba(221, 221, 221, 0.2);
        border-radius: 8px;
        background-color: rgba(240, 240, 240, 0.2);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .main-container.lights-off .admin-form-container {
        background-color: rgba(30, 30, 30, 0.2);
        border-color: rgba(80, 80, 80, 0.15);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        color: #f0f0f0;
    }

    .content-section {
        /* Ersetzt admin-tool-section für den Hauptinhalt */
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #eee;
    }

    .main-container.lights-off .content-section {
        border-bottom: 1px dashed #555;
    }

    .content-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .content-section h2,
    .content-section h3 {
        margin-bottom: 10px;
        color: #333;
        /* Standardfarbe */
    }

    .main-container.lights-off .admin-form-container h1,
    .main-container.lights-off .admin-form-container h2,
    .main-container.lights-off .admin-form-container h3,
    .main-container.lights-off .admin-form-container p,
    .main-container.lights-off .admin-form-container li,
    .main-container.lights-off .admin-form-container span {
        color: #f0f0f0 !important;
        /* Textfarbe für Dark Mode */
    }

    /* Alte .message Klasse wird durch .status-message ersetzt */
    .message {
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 5px;
        font-weight: bold;
    }

    /* Diese spezifischen .message Styles sind nicht mehr nötig, da .status-message direkt die Farben setzt */
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        // SICHERHEIT: CSRF-Token für JavaScript verfügbar machen
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const generateButton = document.getElementById('generate-pages-button');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
        const loadingSpinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const missingPagesGrid = document.getElementById('missing-pages-grid'); // ID angepasst
        const createdItemsContainer = document.getElementById('created-items-container'); // ID angepasst
        const generationResultsSection = document.getElementById('generation-results-section');
        const overallStatusMessage = document.getElementById('overall-status-message');
        const errorHeaderMessage = document.getElementById('error-header-message');
        const errorsList = document.getElementById('generation-errors-list');

        // Die Liste der fehlenden IDs, direkt von PHP übergeben
        const initialMissingIds = <?php echo json_encode($missingComicPages); ?>;
        let remainingIds = [...initialMissingIds];
        let createdCount = 0;
        let errorCount = 0;
        let isPaused = false; // Status für die Pause-Funktion
        let isGenerationActive = false; // Neuer Flag, um zu verfolgen, ob die Generierung läuft

        // Elemente für die Positionierung der Buttons
        const mainContent = document.getElementById('content'); // Das Haupt-Content-Element
        const fixedButtonsContainer = document.getElementById('fixed-buttons-container');

        // Sicherheitscheck: Wenn der Button-Container nicht gefunden wird, breche ab.
        if (!fixedButtonsContainer) {
            console.error("Fehler: Das Element '#fixed-buttons-container' wurde nicht gefunden. Die Buttons können nicht positioniert werden.");
            return;
        }

        let initialButtonTopOffset; // Die absolute Top-Position der Buttons im Dokument, wenn sie nicht fixed sind
        let stickyThreshold; // Der Scroll-Y-Wert, ab dem die Buttons fixiert werden sollen
        const stickyOffset = 18; // Gewünschter Abstand vom oberen Viewport-Rand, wenn sticky
        const rightOffset = 24; // Gewünschter Abstand vom rechten Rand des Main-Elements, wenn sticky

        /**
         * Berechnet die initialen Positionen und den Schwellenwert für das "Klebenbleiben".
         * Diese Funktion muss aufgerufen werden, wenn sich das Layout ändert (z.B. bei Fenstergröße).
         */
        function calculateInitialPositions() {
            // Sicherstellen, dass die Buttons nicht 'fixed' sind, um ihre natürliche Position zu ermitteln
            fixedButtonsContainer.style.position = 'static';
            fixedButtonsContainer.style.top = 'auto';
            fixedButtonsContainer.style.right = 'auto';

            // Die absolute Top-Position des Button-Containers im Dokument
            initialButtonTopOffset = fixedButtonsContainer.getBoundingClientRect().top + window.scrollY;

            // Der Schwellenwert: Wenn der Benutzer so weit scrollt, dass die Buttons
            // 'stickyOffset' (18px) vom oberen Viewport-Rand entfernt wären, sollen sie fixiert werden.
            stickyThreshold = initialButtonTopOffset - stickyOffset;

            if (!mainContent) {
                console.warn("Warnung: Das 'main' Element mit ID 'content' wurde nicht gefunden. Die rechte Position der Buttons wird relativ zum Viewport berechnet.");
            }
        }

        /**
         * Behandelt das Scroll-Ereignis, um die Buttons zu fixieren oder freizugeben.
         */
        function handleScroll() {
            if (!fixedButtonsContainer) return; // Sicherheitscheck

            const currentScrollY = window.scrollY; // Aktuelle Scroll-Position

            if (currentScrollY >= stickyThreshold) {
                // Wenn der Scroll-Y-Wert den Schwellenwert erreicht oder überschreitet, fixiere die Buttons
                if (fixedButtonsContainer.style.position !== 'fixed') {
                    fixedButtonsContainer.style.position = 'fixed';
                    fixedButtonsContainer.style.top = `${stickyOffset}px`; // 18px vom oberen Viewport-Rand

                    // Berechne die rechte Position:
                    if (mainContent) {
                        const mainRect = mainContent.getBoundingClientRect();
                        // Abstand vom rechten Viewport-Rand zum rechten Rand des Main-Elements + gewünschter Offset
                        fixedButtonsContainer.style.right = (window.innerWidth - mainRect.right + rightOffset) + 'px';
                    } else {
                        // Fallback: Wenn mainContent nicht gefunden wird, positioniere relativ zum Viewport-Rand
                        fixedButtonsContainer.style.right = `${rightOffset}px`;
                    }
                }
            } else {
                // Wenn der Scroll-Y-Wert unter dem Schwellenwert liegt, gib die Buttons frei (normaler Fluss)
                if (fixedButtonsContainer.style.position === 'fixed') {
                    fixedButtonsContainer.style.position = 'static'; // Zurück zum normalen Fluss
                    fixedButtonsContainer.style.top = 'auto';
                    fixedButtonsContainer.style.right = 'auto';
                }
            }
        }

        /**
         * Behandelt das Resize-Ereignis, um Positionen neu zu berechnen und den Scroll-Status anzupassen.
         */
        function handleResize() {
            calculateInitialPositions(); // Positionen neu berechnen, da sich das Layout geändert haben könnte
            handleScroll(); // Den Sticky-Zustand basierend auf den neuen Positionen neu bewerten
        }

        // Initiales Setup beim Laden der Seite
        // Zuerst Positionen berechnen, dann den Scroll-Status anpassen
        calculateInitialPositions();
        handleScroll(); // Setze den initialen Zustand basierend auf der aktuellen Scroll-Position

        // Event Listener für Scroll- und Resize-Ereignisse
        window.addEventListener('scroll', handleScroll);
        window.addEventListener('resize', handleResize);

        // Funktion zum Aktualisieren des Button-Zustands (Text, Farbe und Sichtbarkeit)
        function updateButtonState() {
            if (initialMissingIds.length === 0) {
                // Keine Seiten zum Generieren vorhanden
                generateButton.style.display = 'inline-block';
                generateButton.disabled = true;
                togglePauseResumeButton.style.display = 'none';
            } else if (isGenerationActive) {
                // Generierung ist aktiv oder pausiert
                generateButton.style.display = 'none';
                togglePauseResumeButton.style.display = 'inline-block';
                if (isPaused) {
                    togglePauseResumeButton.textContent = 'Generierung fortsetzen';
                    togglePauseResumeButton.className = 'status-green-button';
                } else {
                    togglePauseResumeButton.textContent = 'Generierung pausieren';
                    togglePauseResumeButton.className = 'status-red-button';
                }
                togglePauseResumeButton.disabled = false;
            } else if (remainingIds.length === 0 && createdCount + errorCount === initialMissingIds.length) {
                // Alle Seiten verarbeitet (Generierung abgeschlossen)
                generateButton.style.display = 'inline-block';
                generateButton.disabled = true; // Nichts mehr zu generieren
                togglePauseResumeButton.style.display = 'none';
            } else {
                // Initialer Zustand: Seiten zum Generieren vorhanden, aber noch nicht gestartet
                generateButton.style.display = 'inline-block';
                generateButton.disabled = false;
                togglePauseResumeButton.style.display = 'none';
            }
        }

        // Initialen Zustand der Buttons beim Laden der Seite setzen
        updateButtonState();

        if (generateButton) {
            generateButton.addEventListener('click', function () {
                if (initialMissingIds.length === 0) {
                    console.log('Keine Comic-Seiten zum Generieren vorhanden.');
                    return;
                }

                // UI zurücksetzen und Ladezustand anzeigen
                loadingSpinner.style.display = 'block';
                generationResultsSection.style.display = 'block';
                overallStatusMessage.textContent = '';
                overallStatusMessage.className = 'status-message'; // Klasse zurücksetzen
                createdItemsContainer.innerHTML = '';
                errorsList.innerHTML = '';
                errorHeaderMessage.style.display = 'none'; // Fehler-Header initial ausblenden

                // Setze remainingIds neu, falls der Button erneut geklickt wird nach Abschluss
                remainingIds = [...initialMissingIds];
                createdCount = 0;
                errorCount = 0;
                isPaused = false;

                isGenerationActive = true; // Generierung starten
                updateButtonState(); // Buttons anpassen (Generieren aus, Pause an)
                processNextPage();
            });
        }

        if (togglePauseResumeButton) {
            togglePauseResumeButton.addEventListener('click', function () {
                isPaused = !isPaused; // Zustand umschalten
                if (isPaused) {
                    progressText.textContent = `Generierung pausiert. ${createdCount + errorCount} von ${initialMissingIds.length} verarbeitet.`;
                }
                updateButtonState(); // Button-Text und Sichtbarkeit aktualisieren
                if (!isPaused) { // Wenn gerade fortgesetzt wurde
                    processNextPage(); // Generierung fortsetzen
                }
            });
        }

        async function processNextPage() {
            if (isPaused) {
                // Wenn pausiert, beende die Ausführung, bis fortgesetzt wird
                return;
            }

            if (remainingIds.length === 0) {
                // Alle Seiten verarbeitet
                loadingSpinner.style.display = 'none';
                progressText.textContent = `Generierung abgeschlossen. ${createdCount} erfolgreich, ${errorCount} Fehler.`;
                isGenerationActive = false; // Generierung beendet
                updateButtonState(); // Buttons anpassen (Toggle aus, Generieren an)

                if (errorCount > 0) {
                    overallStatusMessage.textContent = `Generierung abgeschlossen mit Fehlern: ${createdCount} erfolgreich, ${errorCount} Fehler.`;
                    overallStatusMessage.className = 'status-message status-orange';
                    errorHeaderMessage.style.display = 'block';
                } else {
                    overallStatusMessage.textContent = `Alle ${createdCount} Comic-Seiten erfolgreich generiert!`;
                    overallStatusMessage.className = 'status-message status-green';
                }
                return;
            }

            const currentId = remainingIds.shift(); // Nächste ID aus der Liste nehmen
            progressText.textContent = `Generiere Comic-Seite ${createdCount + errorCount + 1} von ${initialMissingIds.length} (${currentId}.php)...`;

            try {
                const response = await fetch(window.location.href, { // Anfrage an dasselbe PHP-Skript
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'create_single_comic_page', // Spezifische Aktion für AJAX
                        comic_id: currentId,
                        csrf_token: csrfToken // SICHERHEIT: CSRF-Token mitsenden
                    })
                });

                let data;
                try {
                    data = await response.json();
                } catch (jsonError) {
                    const responseText = await response.text();
                    throw new Error(`Fehler beim Parsen der JSON-Antwort für ${currentId}: ${jsonError.message}. Antwort war: ${responseText.substring(0, 200)}...`);
                }


                if (data.success) {
                    createdCount++;
                    const generatedItemDiv = document.createElement('div');
                    generatedItemDiv.className = 'generated-item';
                    generatedItemDiv.textContent = `${data.comicId}.php`;
                    createdItemsContainer.appendChild(generatedItemDiv);

                    // Entferne das Element aus dem Grid der fehlenden Seiten
                    if (missingPagesGrid) {
                        const missingItemSpan = missingPagesGrid.querySelector(`span[data-comic-id="${data.comicId}"]`);
                        if (missingItemSpan) {
                            missingItemSpan.remove();
                        }
                    }

                } else {
                    errorCount++;
                    const errorItem = document.createElement('li');
                    errorItem.textContent = `Fehler für ${currentId}.php: ${data.message}`;
                    errorsList.appendChild(errorItem);
                    errorHeaderMessage.style.display = 'block';
                }
            } catch (error) {
                errorCount++;
                const errorItem = document.createElement('li');
                errorItem.textContent = `Netzwerkfehler oder unerwartete Antwort für ${currentId}.php: ${error.message}`;
                errorsList.appendChild(errorItem);
                errorHeaderMessage.style.display = 'block';
            }

            // Fügen Sie hier eine kleine Verzögerung ein, bevor die nächste Seite verarbeitet wird
            setTimeout(() => {
                processNextPage();
            }, 1000); // 1000 Millisekunden (1 Sekunde) Verzögerung
        }
    });
</script>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
    if ($debugMode)
        error_log("DEBUG: Footer in generator_comic.php eingebunden.");
} else {
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>