<?php
/**
 * Dies ist die Administrationsseite für die Erstkonfiguration der Webseite.
 * Hier können grundlegende Einstellungen wie die Erstellung notwendiger Ordner
 * und die Überprüfung/Sortierung der Comic-Datenbankdatei vorgenommen werden.
 *
 * Angepasst an das neue Design mit einheitlichen Statusmeldungen, Button-Stilen
 * und einem schwebenden Button für die Ordnererstellung.
 */

// Starte die PHP-Sitzung. Notwendig, wenn diese Seite in den Admin-Bereich eingebunden ist.
session_start();

// Logout-Funktion (wird über GET-Parameter ausgelöst)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();     // Entfernt alle Session-Variablen
    session_destroy();   // Zerstört die Session
    header('Location: index.php'); // Weiterleitung zur Login-Seite
    exit;
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    header('Location: index.php');
    exit;
}

// Pfade zu den benötigten Ressourcen
$robotsContent = 'noindex, nofollow';
$headerPath = __DIR__ . '/../src/layout/header_admin.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/components/comic_var.json';

// Definiere die benötigten Ordnerpfade relativ zum aktuellen Admin-Verzeichnis.
// Annahme: Die 'assets'-Ordner liegt eine Ebene über 'admin' und direkt dort sind die Comic-Ordner.
$requiredFolders = [
    __DIR__ . '/../assets/comic_lowres',
    __DIR__ . '/../assets/comic_hires',
    __DIR__ . '/../assets/comic_thumbnails',
];

$message = ''; // Wird für Statusmeldungen an den Benutzer verwendet.

// --- Funktionen für die Tools ---

/**
 * Überprüft, ob die erforderlichen Ordner existieren.
 * @param array $folders Die Liste der zu überprüfenden Ordnerpfade.
 * @return array Eine Liste der fehlenden Ordnerpfade.
 */
function checkMissingFolders(array $folders): array {
    $missing = [];
    foreach ($folders as $folder) {
        if (!is_dir($folder)) {
            $missing[] = $folder;
        }
    }
    return $missing;
}

/**
 * Erstellt die angegebenen Ordner rekursiv.
 * @param array $folders Die Liste der zu erstellenden Ordnerpfade.
 * @return array Eine Liste der Ordner, die erfolgreich erstellt wurden.
 */
function createFolders(array $folders): array {
    $created = [];
    foreach ($folders as $folder) {
        if (!is_dir($folder)) {
            // Versuche, den Ordner mit vollen Berechtigungen rekursiv zu erstellen.
            // Beachte: 0777 ist für Produktionsumgebungen oft zu offen.
            // Für lokale Entwicklung oder bestimmte Serverkonfigurationen kann es notwendig sein.
            if (mkdir($folder, 0777, true)) {
                $created[] = $folder;
            } else {
                error_log("Fehler beim Erstellen des Ordners: " . $folder);
            }
        }
    }
    return $created;
}

/**
 * Liest die Comic-Daten aus der JSON-Datei.
 * @param string $filePath Der Pfad zur JSON-Datei.
 * @return array|null Die dekodierten Daten als assoziatives Array oder null bei Fehler/nicht existent.
 */
function getComicData(string $filePath): ?array {
    if (!file_exists($filePath)) {
        return null;
    }
    $content = file_get_contents($filePath);
    if ($content === false) {
        error_log("Fehler beim Lesen der JSON-Datei: " . $filePath);
        return null;
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren der JSON-Datei: " . json_last_error_msg());
        return null;
    }
    return is_array($data) ? $data : [];
}

/**
 * Speichert Comic-Daten in die JSON-Datei.
 * @param string $filePath Der Pfad zur JSON-Datei.
 * @param array $data Die zu speichernden Daten.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveComicData(string $filePath, array $data): bool {
    // Sicherstellen, dass das Verzeichnis existiert
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("Fehler: Verzeichnis für JSON-Datei nicht erstellbar: " . $dir);
            return false;
        }
    }

    $result = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    if ($result === false) {
        error_log("Fehler beim Schreiben der JSON-Datei: " . $filePath);
    }
    return $result !== false;
}

/**
 * Überprüft, ob ein assoziatives Array alphabetisch nach Schlüsseln geordnet ist.
 * @param array $data Das zu überprüfende Array.
 * @return bool True, wenn alphabetisch geordnet, False sonst.
 */
function isAlphabeticallySorted(array $data): bool {
    $keys = array_keys($data);
    $sortedKeys = $keys;
    sort($sortedKeys); // Alphabetische Sortierung der Schlüssel
    return $keys === $sortedKeys;
}

// --- Verarbeitung von POST-Anfragen ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_folders':
            $missing = checkMissingFolders($requiredFolders);
            if (empty($missing)) {
                $message = '<p class="status-message status-orange">Alle erforderlichen Ordner existieren bereits.</p>';
            } else {
                $created = createFolders($missing);
                if (!empty($created)) {
                    $message = '<p class="status-message status-green">Folgende Ordner erfolgreich erstellt: ' . implode(', ', array_map('basename', $created)) . '</p>';
                } else {
                    $message = '<p class="status-message status-red">Fehler beim Erstellen der Ordner. Bitte Dateiberechtigungen prüfen.</p>';
                }
            }
            break;

        case 'create_json':
            if (file_exists($comicVarJsonPath)) {
                $message = '<p class="status-message status-orange">Die Datei `comic_var.json` existiert bereits.</p>';
            } else {
                if (saveComicData($comicVarJsonPath, [])) { // Erstelle eine leere JSON-Datei
                    $message = '<p class="status-message status-green">Leere `comic_var.json` erfolgreich erstellt.</p>';
                } else {
                    $message = '<p class="status-message status-red">Fehler beim Erstellen der `comic_var.json`. Bitte Dateiberechtigungen prüfen.</p>';
                }
            }
            break;

        case 'sort_json':
            $comicData = getComicData($comicVarJsonPath);
            if ($comicData === null) {
                $message = '<p class="status-message status-red">Die Datei `comic_var.json` existiert nicht oder ist fehlerhaft. Kann nicht sortiert werden.</p>';
            } elseif (empty($comicData)) {
                $message = '<p class="status-message status-orange">Die Datei `comic_var.json` ist leer und muss nicht sortiert werden.</p>';
            } elseif (isAlphabeticallySorted($comicData)) {
                $message = '<p class="status-message status-orange">Die Datei `comic_var.json` ist bereits korrekt alphabetisch geordnet.</p>';
            } else {
                ksort($comicData); // Sortiert das Array nach Schlüsseln (alphabetisch)
                if (saveComicData($comicVarJsonPath, $comicData)) {
                    $message = '<p class="status-message status-green">Die Datei `comic_var.json` wurde erfolgreich alphabetisch geordnet.</p>';
                } else {
                    $message = '<p class="status-message status-red">Fehler beim Speichern der sortierten `comic_var.json`. Bitte Dateiberechtigungen prüfen.</p>';
                }
            }
            break;
    }
}

// --- Statusermittlung für die Anzeige (unabhängig von POST-Requests) ---
$missingFolders = checkMissingFolders($requiredFolders);
$jsonFileExists = file_exists($comicVarJsonPath);
$jsonFileSorted = false;
$currentComicData = []; // Standardwert, falls JSON nicht existiert oder leer ist
if ($jsonFileExists) {
    $data = getComicData($comicVarJsonPath);
    if ($data !== null) { // Prüfen, ob Daten erfolgreich geladen wurden
        $currentComicData = $data;
        $jsonFileSorted = isAlphabeticallySorted($currentComicData);
    }
}


// --- HTML-Struktur und Anzeige ---

// Parameter für den Header
$pageTitle = 'Adminbereich - Ersteinrichtung';
$pageHeader = 'Webseiten-Ersteinrichtung';
$siteDescription = 'Tools für die initiale Konfiguration und Wartung der Twokinds-Webseite.';

// Binde den gemeinsamen Header ein.
// Stelle sicher, dass der Pfad korrekt ist.
if (file_exists($headerPath)) {
    include $headerPath;
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
        <?php endif; ?>

        <!-- Tool 1: Ordner überprüfen und erstellen -->
        <section class="content-section">
            <h3>1. Comic-Ordner prüfen und erstellen</h3>
            <p>Überprüft, ob die für die Comics benötigten Ordner existieren, und bietet die Möglichkeit, sie bei Bedarf zu erstellen.</p>
            <?php if (!empty($missingFolders)): ?>
                <p class="status-message status-red">Folgende Ordner fehlen:</p>
                <div id="missing-folders-grid" class="missing-items-grid">
                    <?php foreach ($missingFolders as $folder): ?>
                        <span class="missing-item"><?php echo htmlspecialchars(basename($folder)); ?></span>
                    <?php endforeach; ?>
                </div>
                <!-- Schwebender Button für Ordner erstellen -->
                <div id="fixed-buttons-container-folders" class="fixed-buttons-container">
                    <form action="" method="POST" style="margin: 0;">
                        <button type="submit" name="action" value="create_folders" class="status-green-button">Fehlende Ordner erstellen</button>
                    </form>
                </div>
            <?php else: ?>
                <p class="status-message status-green">Alle erforderlichen Comic-Ordner existieren.</p>
            <?php endif; ?>
        </section>

        <!-- Tool 2: comic_var.json überprüfen und erstellen -->
        <section class="content-section">
            <h3>2. `comic_var.json` prüfen und als Vorlage erstellen</h3>
            <p>Überprüft, ob die JSON-Datei für die Comic-Variablen existiert. Falls nicht, kann eine leere Vorlage erstellt werden.</p>
            <?php if (!$jsonFileExists): ?>
                <p class="status-message status-red">Die Datei `comic_var.json` existiert nicht.</p>
                <form action="" method="POST">
                    <button type="submit" name="action" value="create_json" class="status-green-button">Leere `comic_var.json` erstellen</button>
                </form>
            <?php else: ?>
                <p class="status-message status-green">Die Datei `comic_var.json` existiert.</p>
            <?php endif; ?>
        </section>

        <!-- Tool 3: comic_var.json alphabetisch ordnen -->
        <section class="content-section">
            <h3>3. `comic_var.json` alphabetisch ordnen</h3>
            <p>Prüft den Inhalt der `comic_var.json` auf alphabetische Sortierung der Comic-Einträge. Bei Bedarf kann die Datei sortiert werden.</p>
            <?php if (!$jsonFileExists): ?>
                <p class="status-message status-orange">Die Datei `comic_var.json` existiert nicht. Bitte zuerst erstellen.</p>
            <?php elseif (empty($currentComicData)): ?>
                <p class="status-message status-orange">Die Datei `comic_var.json` ist leer und muss nicht sortiert werden.</p>
            <?php elseif ($jsonFileSorted): ?>
                <p class="status-message status-green">Die Datei `comic_var.json` ist bereits korrekt alphabetisch geordnet.</p>
            <?php else: ?>
                <p class="status-message status-red">Die Datei `comic_var.json` ist nicht alphabetisch geordnet.</p>
                <form action="" method="POST">
                    <button type="submit" name="action" value="sort_json" class="status-red-button">`comic_var.json` alphabetisch ordnen</button>
                </form>
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
        --missing-item-text-color: #333; /* Standardtextfarbe */
        --generated-item-bg-color: #d4edda;
        --generated-item-text-color: #155724;
        --generated-item-border-color: #c3e6cb;
    }

    body.theme-night {
        /* Dark Mode Overrides */
        --missing-grid-border-color: #045d81;
        --missing-grid-bg-color: #03425b;
        --missing-item-bg-color: #025373;
        --missing-item-text-color: #f0f0f0; /* Hellerer Text für Dark Mode */
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
    .status-red-button {
        background-color: #dc3545; /* Bootstrap-Rot */
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
        background-color: #28a745; /* Bootstrap-Grün */
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
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
        min-width: 120px; /* Mindestbreite für bessere Lesbarkeit */
        max-width: 200px; /* Maximale Breite, bevor Umbruch */
        flex-grow: 1; /* Elemente können wachsen, um den Platz zu füllen */
    }

    /* Stil für den Button-Container - initial statisch, wird per JS zu 'fixed' */
    .fixed-buttons-container { /* Geändert von ID zu Klasse */
        z-index: 1000; /* Stellt sicher, dass die Buttons über anderen Inhalten liegen */
        display: flex; /* Für nebeneinanderliegende Buttons */
        gap: 10px; /* Abstand zwischen den Buttons */
        margin-top: 20px; /* Fügt etwas Abstand hinzu, wenn die Buttons statisch sind */
        margin-bottom: 20px; /* Abstand nach unten, wenn statisch */
        justify-content: flex-end; /* Richtet die Buttons im statischen Zustand am rechten Rand aus */
        /* top und right werden dynamisch per JavaScript gesetzt, position wird auch per JS gesetzt */
    }

    /* Anpassung für kleinere Bildschirme, falls die Buttons zu viel Platz einnehmen */
    @media (max-width: 768px) {
        .fixed-buttons-container {
            flex-direction: column; /* Buttons untereinander auf kleinen Bildschirmen */
            gap: 5px;
            align-items: flex-end; /* Auch im Spalten-Layout rechts ausrichten */
        }
    }

    /* NEUE STILE FÜR DIE KOMPAKTE LISTE DER FEHLENDEN ELEMENTE */
    .missing-items-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px; /* Abstand zwischen den Elementen */
        max-height: 300px; /* Maximale Höhe */
        overflow-y: auto; /* Scrollbar, wenn Inhalt die Höhe überschreitet */
        border: 1px solid var(--missing-grid-border-color); /* Dynamischer Rahmen */
        padding: 10px;
        border-radius: 5px;
        background-color: var(--missing-grid-bg-color); /* Dynamischer Hintergrund */
        margin-bottom: 15px; /* Abstand zum Button */
    }

    .missing-item {
        background-color: var(--missing-item-bg-color); /* Dynamischer Hintergrund */
        color: var(--missing-item-text-color); /* Dynamische Textfarbe */
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.9em;
        white-space: nowrap; /* Verhindert Zeilenumbruch innerhalb eines Eintrags */
        overflow: hidden;
        text-overflow: ellipsis; /* Fügt "..." hinzu, wenn der Text zu lang ist */
        max-width: 150px; /* Begrenzt die Breite jedes Eintrags */
        flex-shrink: 0; /* Verhindert, dass Elemente schrumpfen */
    }

    /* Bestehende Admin-Formular-Stile beibehalten und anpassen */
    .admin-form-container {
        max-width: 825px; /* Angepasst an article Breite */
        margin: 20px auto;
        padding: 20px;
        border: 1px solid rgba(221, 221, 221, 0.2);
        border-radius: 8px;
        background-color: rgba(240, 240, 240, 0.2);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .main-container.lights-off .admin-form-container {
        background-color: rgba(30, 30, 30, 0.2);
        border-color: rgba(80, 80, 80, 0.15);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        color: #f0f0f0;
    }

    .content-section { /* Ersetzt admin-tool-section für den Hauptinhalt */
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

    .content-section h2, .content-section h3 {
        margin-bottom: 10px;
        color: #333; /* Standardfarbe */
    }
    .main-container.lights-off .admin-form-container h1,
    .main-container.lights-off .admin-form-container h2,
    .main-container.lights-off .admin-form-container h3,
    .main-container.lights-off .admin-form-container p,
    .main-container.lights-off .admin-form-container li,
    .main-container.lights-off .admin-form-container span {
        color: #f0f0f0 !important; /* Textfarbe für Dark Mode */
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elemente für die Positionierung der Buttons
    const mainContent = document.getElementById('content'); // Das Haupt-Content-Element
    const fixedButtonsContainerFolders = document.getElementById('fixed-buttons-container-folders'); // Spezifischer Container für Ordner-Buttons

    // Sicherheitscheck: Wenn der Button-Container nicht gefunden wird, breche ab.
    if (!fixedButtonsContainerFolders) {
        console.warn("Warnung: Das Element '#fixed-buttons-container-folders' wurde nicht gefunden. Der schwebende Button wird nicht aktiviert.");
        return; // Skript hier beenden, wenn das Element fehlt
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
        if (!fixedButtonsContainerFolders) return; // Sicherheitscheck

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
});
</script>

<?php
// Binde den gemeinsamen Footer ein.
// Stelle sicher, dass der Pfad korrekt ist.
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    // Fallback oder Fehlerbehandlung, wenn der Footer nicht gefunden wird
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>
