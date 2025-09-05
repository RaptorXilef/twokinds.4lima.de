<?php
/**
 * Dies ist die Administrationsseite für die Erstkonfiguration der Webseite.
 * Hier können grundlegende Einstellungen wie die Erstellung notwendiger Ordner
 * und die Überprüfung/Sortierung der Comic-Datenbankdatei vorgenommen werden.
 *
 * Angepasst an das neue Design mit einheitlichen Statusmeldungen, Button-Stilen
 * und einem schwebenden Button für die Ordnererstellung.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade zu den benötigten Ressourcen
$robotsContent = 'noindex, nofollow';
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json'; // Wird weiterhin für Sortierung benötigt

// Definiere die benötigten Ordnerpfade relativ zum aktuellen Admin-Verzeichnis.
$requiredFolders = [
    __DIR__ . '/../assets',
    __DIR__ . '/../assets/comic_hires',
    __DIR__ . '/../assets/comic_lowres',
    __DIR__ . '/../assets/comic_socialmedia',
    __DIR__ . '/../assets/comic_thumbnails',
    __DIR__ . '/../src/config',
];
if ($debugMode)
    error_log("DEBUG: Erforderliche Ordnerpfade definiert.");

// Definiere die benötigten JSON-Dateien und den Pfad zu ihren Vorlagen
$requiredJsonFiles = [
    'archive_chapters.json' => __DIR__ . '/../src/config/archive_chapters.json',
    'comic_var.json' => __DIR__ . '/../src/config/comic_var.json',
    'rss_config.json' => __DIR__ . '/../src/config/rss_config.json',
    'sitemap.json' => __DIR__ . '/../src/config/sitemap.json',
    'version.json' => __DIR__ . '/../src/config/version.json',
];
$jsonTemplatesPath = __DIR__ . '/json-vorlagen/';
if ($debugMode) {
    error_log("DEBUG: Erforderliche JSON-Dateien definiert.");
    error_log("DEBUG: JSON-Vorlagenpfad: " . $jsonTemplatesPath);
}


$message = ''; // Wird für Statusmeldungen an den Benutzer verwendet.

// --- Funktionen für die Tools ---

/**
 * Überprüft den Status der erforderlichen Ordner.
 * @param array $folders Die Liste der zu überprüfenden Ordnerpfade.
 * @return array Eine Liste von Arrays mit 'path', 'basename' und 'exists' Status.
 */
function getFolderStatuses(array $folders, bool $debugMode): array
{
    $statuses = [];
    foreach ($folders as $folder) {
        $exists = is_dir($folder);
        $statuses[] = [
            'path' => $folder,
            'basename' => basename($folder),
            'exists' => $exists
        ];
        if ($debugMode)
            error_log("DEBUG: Ordnerstatus für " . basename($folder) . ": " . ($exists ? "Existiert" : "Fehlt"));
    }
    return $statuses;
}

/**
 * Erstellt die angegebenen Ordner rekursiv.
 * @param array $folders Die Liste der zu erstellenden Ordnerpfade.
 * @return array Eine Liste der Ordner, die erfolgreich erstellt wurden.
 */
function createFolders(array $folders, bool $debugMode): array
{
    $created = [];
    foreach ($folders as $folder) {
        if (!is_dir($folder)) {
            if (mkdir($folder, 0777, true)) {
                $created[] = $folder;
                if ($debugMode)
                    error_log("DEBUG: Ordner erfolgreich erstellt: " . $folder);
            } else {
                error_log("Fehler beim Erstellen des Ordners: " . $folder);
                if ($debugMode)
                    error_log("DEBUG: Fehler beim Erstellen des Ordners: " . $folder);
            }
        }
    }
    if ($debugMode)
        error_log("DEBUG: Ordnererstellung abgeschlossen. Erstellt: " . count($created));
    return $created;
}

/**
 * Überprüft den Status der erforderlichen JSON-Dateien und ihrer Vorlagen.
 * @param array $jsonFiles Ein assoziatives Array von Dateinamen zu vollständigen Pfaden.
 * @param string $templatesPath Der Pfad zum Verzeichnis der JSON-Vorlagen.
 * @param bool $debugMode
 * @return array Eine Liste von Arrays mit 'name', 'path', 'exists', 'templateExists' Status.
 */
function getJsonFileStatuses(array $jsonFiles, string $templatesPath, bool $debugMode): array
{
    $statuses = [];
    foreach ($jsonFiles as $name => $path) {
        $exists = file_exists($path);
        $templateExists = file_exists($templatesPath . $name);
        $statuses[] = [
            'name' => $name,
            'path' => $path,
            'exists' => $exists,
            'templateExists' => $templateExists
        ];
        if ($debugMode)
            error_log("DEBUG: JSON-Dateistatus für " . $name . ": Existiert=" . ($exists ? "Ja" : "Nein") . ", VorlageExistiert=" . ($templateExists ? "Ja" : "Nein"));
    }
    return $statuses;
}

/**
 * Erstellt fehlende JSON-Dateien, entweder aus Vorlagen oder leer.
 * @param array $jsonFiles Ein assoziatives Array von Dateinamen zu vollständigen Pfaden.
 * @param string $templatesPath Der Pfad zum Verzeichnis der JSON-Vorlagen.
 * @param bool $debugMode
 * @return array Eine Liste von Arrays mit 'name' und 'status' der erstellten/kopierten Dateien.
 */
function createJsonFiles(array $jsonFiles, string $templatesPath, bool $debugMode): array
{
    $results = [];
    foreach ($jsonFiles as $name => $path) {
        if (!file_exists($path)) {
            $templatePath = $templatesPath . $name;
            $dir = dirname($path);

            // Sicherstellen, dass das Zielverzeichnis existiert
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Zielverzeichnis nicht erstellbar'];
                    error_log("Fehler: Zielverzeichnis für JSON-Datei nicht erstellbar: " . $dir);
                    continue;
                }
            }

            if (file_exists($templatePath)) {
                // Vorlage kopieren
                if (copy($templatePath, $path)) {
                    $results[] = ['name' => $name, 'status' => 'copied', 'message' => 'aus Vorlage kopiert'];
                    if ($debugMode)
                        error_log("DEBUG: JSON-Datei " . $name . " aus Vorlage kopiert.");
                } else {
                    $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Fehler beim Kopieren der Vorlage'];
                    error_log("Fehler: Fehler beim Kopieren der JSON-Vorlage " . $templatePath . " nach " . $path);
                }
            } else {
                // Leere Datei erstellen
                $emptyContent = ($name === 'comic_var.json') ? json_encode([], JSON_PRETTY_PRINT) : json_encode(new stdClass(), JSON_PRETTY_PRINT);
                if (file_put_contents($path, $emptyContent) !== false) {
                    $results[] = ['name' => $name, 'status' => 'created_empty', 'message' => 'leer erstellt'];
                    if ($debugMode)
                        error_log("DEBUG: Leere JSON-Datei " . $name . " erstellt.");
                } else {
                    $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Fehler beim Erstellen der leeren Datei'];
                    error_log("Fehler: Fehler beim Erstellen der leeren JSON-Datei " . $path);
                }
            }
        }
    }
    return $results;
}


/**
 * Liest die Comic-Daten aus der JSON-Datei.
 * @param string $filePath Der Pfad zur JSON-Datei.
 * @return array|null Die dekodierten Daten als assoziatives Array oder null bei Fehler/nicht existent.
 */
function getComicData(string $filePath, bool $debugMode): ?array
{
    if (!file_exists($filePath)) {
        if ($debugMode)
            error_log("DEBUG: JSON-Datei nicht gefunden: " . $filePath);
        return null;
    }
    $content = file_get_contents($filePath);
    if ($content === false) {
        error_log("Fehler beim Lesen der JSON-Datei: " . $filePath);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Lesen der JSON-Datei: " . $filePath);
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
        error_log("DEBUG: Comic-Daten aus JSON erfolgreich geladen.");
    return is_array($data) ? $data : [];
}

/**
 * Speichert Comic-Daten in die JSON-Datei.
 * @param string $filePath Der Pfad zur JSON-Datei.
 * @param array $data Die zu speichernden Daten.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveComicData(string $filePath, array $data, bool $debugMode): bool
{
    // Sicherstellen, dass das Verzeichnis existiert
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("Fehler: Verzeichnis für JSON-Datei nicht erstellbar: " . $dir);
            if ($debugMode)
                error_log("DEBUG: Fehler: Verzeichnis für JSON-Datei nicht erstellbar: " . $dir);
            return false;
        }
    }

    $result = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    if ($result === false) {
        error_log("Fehler beim Schreiben der JSON-Datei: " . $filePath);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Schreiben der JSON-Datei: " . $filePath);
    } else {
        if ($debugMode)
            error_log("DEBUG: Comic-Daten in JSON erfolgreich gespeichert.");
    }
    return $result !== false;
}

/**
 * Überprüft, ob ein assoziatives Array alphabetisch nach Schlüsseln geordnet ist.
 * @param array $data Das zu überprüfende Array.
 * @return bool True, wenn alphabetisch geordnet, False sonst.
 */
function isAlphabeticallySorted(array $data, bool $debugMode): bool
{
    $keys = array_keys($data);
    $sortedKeys = $keys;
    sort($sortedKeys); // Alphabetische Sortierung der Schlüssel
    $isSorted = ($keys === $sortedKeys);
    if ($debugMode)
        error_log("DEBUG: Überprüfung auf alphabetische Sortierung: " . ($isSorted ? "Ja" : "Nein"));
    return $isSorted;
}

// --- Verarbeitung von POST-Anfragen ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($debugMode)
        error_log("DEBUG: POST-Anfrage erhalten, Aktion: " . $action);

    switch ($action) {
        case 'create_folders':
            $allFolderStatuses = getFolderStatuses($requiredFolders, $debugMode);
            $missingFolders = array_filter($allFolderStatuses, fn($f) => !$f['exists']);
            $missingFolderPaths = array_column($missingFolders, 'path');

            if (empty($missingFolderPaths)) {
                $message = '<p class="status-message status-orange">Alle erforderlichen Ordner existieren bereits.</p>';
                if ($debugMode)
                    error_log("DEBUG: Alle Ordner existieren bereits.");
            } else {
                $created = createFolders($missingFolderPaths, $debugMode);
                if (!empty($created)) {
                    $message = '<p class="status-message status-green">Folgende Ordner erfolgreich erstellt: ' . implode(', ', array_map('basename', $created)) . '</p>';
                    if ($debugMode)
                        error_log("DEBUG: Ordner erfolgreich erstellt: " . implode(', ', array_map('basename', $created)));
                } else {
                    $message = '<p class="status-message status-red">Fehler beim Erstellen der Ordner. Bitte Dateiberechtigungen prüfen.</p>';
                    if ($debugMode)
                        error_log("DEBUG: Fehler beim Erstellen der Ordner.");
                }
            }
            break;

        case 'create_json_files': // NEUE AKTION FÜR JSON-DATEIEN
            $results = createJsonFiles($requiredJsonFiles, $jsonTemplatesPath, $debugMode);
            $successCount = 0;
            $errorCount = 0;
            $messages = [];

            foreach ($results as $result) {
                if ($result['status'] === 'copied' || $result['status'] === 'created_empty') {
                    $successCount++;
                    $messages[] = '`' . htmlspecialchars($result['name']) . '` ' . htmlspecialchars($result['message']);
                } else {
                    $errorCount++;
                    $messages[] = 'Fehler bei `' . htmlspecialchars($result['name']) . '`: ' . htmlspecialchars($result['message']);
                }
            }

            if ($successCount > 0 && $errorCount === 0) {
                $message = '<p class="status-message status-green">Erfolgreich erstellt/kopiert: ' . implode(', ', $messages) . '</p>';
            } elseif ($successCount > 0 && $errorCount > 0) {
                $message = '<p class="status-message status-orange">Teilweise erfolgreich: ' . implode('; ', $messages) . '</p>';
            } elseif ($errorCount > 0) {
                $message = '<p class="status-message status-red">Fehler beim Erstellen/Kopieren: ' . implode('; ', $messages) . '</p>';
            } else {
                $message = '<p class="status-message status-orange">Alle erforderlichen JSON-Dateien existieren bereits.</p>';
            }
            if ($debugMode)
                error_log("DEBUG: JSON-Dateien Aktion abgeschlossen. Erfolgreich: " . $successCount . ", Fehler: " . $errorCount);
            break;


        case 'sort_json':
            $comicData = getComicData($comicVarJsonPath, $debugMode);
            if ($comicData === null) {
                $message = '<p class="status-message status-red">Die Datei `comic_var.json` existiert nicht oder ist fehlerhaft. Kann nicht sortiert werden.</p>';
                if ($debugMode)
                    error_log("DEBUG: comic_var.json existiert nicht oder ist fehlerhaft für Sortierung.");
            } elseif (empty($comicData)) {
                $message = '<p class="status-message status-orange">Die Datei `comic_var.json` ist leer und muss nicht sortiert werden.</p>';
                if ($debugMode)
                    error_log("DEBUG: comic_var.json ist leer, keine Sortierung nötig.");
            } elseif (isAlphabeticallySorted($comicData, $debugMode)) {
                $message = '<p class="status-message status-orange">Die Datei `comic_var.json` ist bereits korrekt alphabetisch geordnet.</p>';
                if ($debugMode)
                    error_log("DEBUG: comic_var.json ist bereits sortiert.");
            } else {
                ksort($comicData); // Sortiert das Array nach Schlüsseln (alphabetisch)
                if (saveComicData($comicVarJsonPath, $comicData, $debugMode)) {
                    $message = '<p class="status-message status-green">Die Datei `comic_var.json` wurde erfolgreich alphabetisch geordnet.</p>';
                    if ($debugMode)
                        error_log("DEBUG: comic_var.json erfolgreich sortiert.");
                } else {
                    $message = '<p class="status-message status-red">Fehler beim Speichern der sortierten `comic_var.json`. Bitte Dateiberechtigungen prüfen.</p>';
                    if ($debugMode)
                        error_log("DEBUG: Fehler beim Speichern der sortierten comic_var.json.");
                }
            }
            break;
    }
}

// --- Statusermittlung für die Anzeige (unabhängig von POST-Requests) ---
$folderStatuses = getFolderStatuses($requiredFolders, $debugMode);
$jsonFileStatuses = getJsonFileStatuses($requiredJsonFiles, $jsonTemplatesPath, $debugMode);

$allFoldersExist = true;
foreach ($folderStatuses as $status) {
    if (!$status['exists']) {
        $allFoldersExist = false;
        break;
    }
}

$allJsonFilesExist = true;
foreach ($jsonFileStatuses as $status) {
    if (!$status['exists']) {
        $allJsonFilesExist = false;
        break;
    }
}

$jsonFileExistsForSort = file_exists($comicVarJsonPath);
$jsonFileSorted = false;
$currentComicData = [];
if ($jsonFileExistsForSort) {
    $data = getComicData($comicVarJsonPath, $debugMode);
    if ($data !== null) {
        $currentComicData = $data;
        $jsonFileSorted = isAlphabeticallySorted($currentComicData, $debugMode);
    }
}
if ($debugMode)
    error_log("DEBUG: Statusermittlung für Anzeige abgeschlossen.");


// --- HTML-Struktur und Anzeige ---

// Parameter für den Header
$pageTitle = 'Adminbereich - Ersteinrichtung';
$pageHeader = 'Webseiten-Ersteinrichtung';
$siteDescription = 'Tools für die initiale Konfiguration und Wartung der Twokinds-Webseite.';
$robotsContent = 'noindex, nofollow'; // Diese Seite soll nicht indexiert werden
if ($debugMode) {
    error_log("DEBUG: Seiten-Titel: " . $pageTitle);
    error_log("DEBUG: Robots-Content: " . $robotsContent);
}

// Binde den gemeinsamen Header ein.
// Stelle sicher, dass der Pfad korrekt ist.
if (file_exists($headerPath)) {
    include $headerPath;
    if ($debugMode)
        error_log("DEBUG: Header in initial_setup.php eingebunden.");
} else {
    // Fallback oder Fehlerbehandlung, wenn der Header nicht gefunden wird
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<article>
    <div class="admin-form-container">
        <header>
            <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
            <?php if ($debugMode)
                error_log("DEBUG: Nachricht an Benutzer angezeigt: " . strip_tags($message)); ?>
        <?php endif; ?>

        <!-- Tool 1: Ordner überprüfen und erstellen -->
        <section class="content-section">
            <h3>1. Comic-Ordner prüfen und erstellen</h3>
            <p>Überprüft, ob die für die Comics benötigten Ordner existieren, und bietet die Möglichkeit, sie bei Bedarf
                zu erstellen.</p>
            <div class="status-list">
                <?php foreach ($folderStatuses as $folder): ?>
                    <div class="status-item <?php echo $folder['exists'] ? 'status-green-text' : 'status-red-text'; ?>">
                        <?php echo htmlspecialchars($folder['basename']); ?>:
                        <span class="status-indicator">
                            <?php echo $folder['exists'] ? 'Existiert' : 'Fehlt'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$allFoldersExist): ?>
                <!-- Schwebender Button für Ordner erstellen -->
                <div id="fixed-buttons-container-folders" class="fixed-buttons-container">
                    <form action="" method="POST" style="margin: 0;">
                        <button type="submit" name="action" value="create_folders" class="status-green-button">Fehlende
                            Ordner erstellen</button>
                    </form>
                </div>
                <?php if ($debugMode)
                    error_log("DEBUG: Fehlende Ordnerliste und 'Ordner erstellen'-Button angezeigt."); ?>
            <?php else: ?>
                <p class="status-message status-green">Alle erforderlichen Comic-Ordner existieren.</p>
                <?php if ($debugMode)
                    error_log("DEBUG: 'Alle Ordner existieren'-Nachricht angezeigt."); ?>
            <?php endif; ?>
        </section>

        <!-- Tool 2: Einstellungs-Dateien prüfen und als Vorlage erstellen -->
        <section class="content-section">
            <h3>2. Einstellungs-Dateien prüfen und als Vorlage erstellen</h3>
            <p>Überprüft, ob die JSON-Konfigurationsdateien existieren. Fehlende Dateien können aus Vorlagen kopiert
                oder
                leer erstellt werden.</p>
            <div class="status-list">
                <?php foreach ($jsonFileStatuses as $file): ?>
                    <div class="status-item <?php echo $file['exists'] ? 'status-green-text' : 'status-red-text'; ?>">
                        <?php echo htmlspecialchars($file['name']); ?>:
                        <span class="status-indicator">
                            <?php
                            if ($file['exists']) {
                                echo 'Existiert';
                            } else {
                                echo 'Fehlt';
                                if ($file['templateExists']) {
                                    echo ' (Vorlage vorhanden)';
                                } else {
                                    echo ' (Keine Vorlage)';
                                }
                            }
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$allJsonFilesExist): ?>
                <form action="" method="POST">
                    <button type="submit" name="action" value="create_json_files" class="status-green-button">Fehlende
                        Einstellungs-Dateien erstellen</button>
                </form>
                <?php if ($debugMode)
                    error_log("DEBUG: 'Fehlende JSON-Dateien'-Nachricht und 'Erstellen'-Button angezeigt."); ?>
            <?php else: ?>
                <p class="status-message status-green">Alle erforderlichen Einstellungs-Dateien existieren.</p>
                <?php if ($debugMode)
                    error_log("DEBUG: 'Alle JSON-Dateien existieren'-Nachricht angezeigt."); ?>
            <?php endif; ?>
        </section>

        <!-- Tool 3: comic_var.json alphabetisch ordnen -->
        <section class="content-section">
            <h3>3. `comic_var.json` alphabetisch ordnen</h3>
            <p>Prüft den Inhalt der `comic_var.json` auf alphabetische Sortierung der Comic-Einträge. Bei Bedarf kann
                die Datei sortiert werden.</p>
            <?php if (!$jsonFileExistsForSort): ?>
                <p class="status-message status-orange">Die Datei `comic_var.json` existiert nicht. Bitte zuerst erstellen.
                </p>
                <?php if ($debugMode)
                    error_log("DEBUG: 'comic_var.json existiert nicht' für Sortierung angezeigt."); ?>
            <?php elseif (empty($currentComicData)): ?>
                <p class="status-message status-orange">Die Datei `comic_var.json` ist leer und muss nicht sortiert werden.
                </p>
                <?php if ($debugMode)
                    error_log("DEBUG: 'comic_var.json ist leer' für Sortierung angezeigt."); ?>
            <?php elseif ($jsonFileSorted): ?>
                <p class="status-message status-green">Die Datei `comic_var.json` ist bereits korrekt alphabetisch geordnet.
                </p>
                <?php if ($debugMode)
                    error_log("DEBUG: 'comic_var.json ist bereits sortiert' angezeigt."); ?>
            <?php else: ?>
                <p class="status-message status-red">Die Datei `comic_var.json` ist nicht alphabetisch geordnet.</p>
                <form action="" method="POST">
                    <button type="submit" name="action" value="sort_json" class="status-red-button">`comic_var.json`
                        alphabetisch ordnen</button>
                </form>
                <?php if ($debugMode)
                    error_log("DEBUG: 'comic_var.json nicht sortiert' und 'Sortieren'-Button angezeigt."); ?>
            <?php endif; ?>
        </section>
    </div>
</article>

<style>
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

        /* Neue Textfarben für Statusanzeige */
        --status-green-text: #155724;
        --status-red-text: #721c24;
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

        /* Neue Textfarben für Statusanzeige im Dark Mode */
        --status-green-text: #28a745;
        /* Helles Grün */
        --status-red-text: #dc3545;
        /* Helles Rot */
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

    /* Neue Textfarben für die detaillierte Statusliste */
    .status-green-text {
        color: var(--status-green-text);
    }

    .status-red-text {
        color: var(--status-red-text);
    }

    /* Neue Button-Stile */
    .status-red-button {
        background-color: #dc3545;
        /* Bootstrap-Rot */
        color: white;
        border: 1px solid #dc3545;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.2s ease;
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
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.2s ease;
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

    /* Spinner CSS (nicht direkt verwendet, aber zur Konsistenz beibehalten) */
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

    /* Grid Layout für generierte Elemente (nicht direkt verwendet, aber zur Konsistenz beibehalten) */
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
    .fixed-buttons-container {
        /* Geändert von ID zu Klasse */
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
        .fixed-buttons-container {
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
        margin-bottom: 15px;
        /* Abstand zum Button */
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

    /* Message Box Styling (Behälter für die Statusmeldungen) */
    .message {
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 5px;
        font-weight: bold;
    }

    /* Dark Theme für Statusmeldungen (Hintergrund und Rand der Box) */
    .main-container.lights-off .message .status-green {
        background-color: rgba(60, 118, 61, 0.3);
        border-color: rgba(214, 233, 198, 0.3);
    }

    .main-container.lights-off .message .status-red {
        background-color: rgba(169, 68, 66, 0.3);
        border-color: rgba(235, 204, 209, 0.3);
    }

    .main-container.lights-off .message .status-orange {
        background-color: rgba(138, 109, 59, 0.3);
        border-color: rgba(250, 235, 204, 0.3);
    }

    /* Neue Stile für die Statuslisten */
    .status-list {
        margin-top: 10px;
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid var(--missing-grid-border-color);
        /* Dynamisch */
        border-radius: 5px;
        background-color: var(--missing-grid-bg-color);
        /* Dynamisch */
    }

    .status-item {
        padding: 4px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px dashed var(--missing-grid-border-color);
        /* Dynamisch */
    }

    .status-item:last-child {
        border-bottom: none;
    }

    .status-indicator {
        font-weight: bold;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Übergebe die PHP-Debug-Variable an JavaScript
        const debugModeEnabled = <?php echo json_encode($debugMode); ?>;

        // Elemente für die Positionierung der Buttons
        const mainContent = document.getElementById('content'); // Das Haupt-Content-Element
        const fixedButtonsContainerFolders = document.getElementById('fixed-buttons-container-folders'); // Spezifischer Container für Ordner-Buttons

        // *** KORREKTUR: Führe den Code für den schwebenden Button nur aus, WENN der Button-Container existiert. ***
        if (fixedButtonsContainerFolders) {

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
                fixedButtonsContainerFolders.style.position = 'static';
                fixedButtonsContainerFolders.style.top = 'auto';
                fixedButtonsContainerFolders.style.right = 'auto';

                // Die absolute Top-Position des Button-Containers im Dokument
                initialButtonTopOffset = fixedButtonsContainerFolders.getBoundingClientRect().top + window.scrollY;

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
                const currentScrollY = window.scrollY; // Aktuelle Scroll-Position

                if (currentScrollY >= stickyThreshold) {
                    // Wenn der Scroll-Y-Wert den Schwellenwert erreicht oder überschreitet, fixiere die Buttons
                    if (fixedButtonsContainerFolders.style.position !== 'fixed') {
                        fixedButtonsContainerFolders.style.position = 'fixed';
                        fixedButtonsContainerFolders.style.top = `${stickyOffset}px`; // 18px vom oberen Viewport-Rand

                        // Berechne die rechte Position:
                        if (mainContent) {
                            const mainRect = mainContent.getBoundingClientRect();
                            // Abstand vom rechten Viewport-Rand zum rechten Rand des Main-Elements + gewünschter Offset
                            fixedButtonsContainerFolders.style.right = (window.innerWidth - mainRect.right + rightOffset) + 'px';
                        } else {
                            // Fallback: Wenn mainContent nicht gefunden wird, positioniere relativ zum Viewport-Rand
                            fixedButtonsContainerFolders.style.right = `${rightOffset}px`;
                        }
                    }
                } else {
                    // Wenn der Scroll-Y-Wert unter dem Schwellenwert liegt, gib die Buttons frei (normaler Fluss)
                    if (fixedButtonsContainerFolders.style.position === 'fixed') {
                        fixedButtonsContainerFolders.style.position = 'static'; // Zurück zum normalen Fluss
                        fixedButtonsContainerFolders.style.top = 'auto';
                        fixedButtonsContainerFolders.style.right = 'auto';
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
        } else {
            // Gib den Hinweis nur aus, wenn der Debug-Modus in PHP aktiviert ist
            if (debugModeEnabled) {
                console.info("Hinweis: Das Element '#fixed-buttons-container-folders' wurde nicht gefunden, da es nicht benötigt wird (alle Ordner existieren bereits). Der schwebende Button ist daher inaktiv.");
            }
        }
    });
</script>

<?php
// Binde den gemeinsamen Footer ein.
// Stelle sicher, dass der Pfad korrekt ist.
if (file_exists($footerPath)) {
    include $footerPath;
    if ($debugMode)
        error_log("DEBUG: Footer in initial_setup.php eingebunden.");
} else {
    // Fallback oder Fehlerbehandlung, wenn der Footer nicht gefunden wird
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>