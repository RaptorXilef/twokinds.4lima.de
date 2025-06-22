<?php
/**
 * Dies ist die Administrationsseite für die Erstkonfiguration der Webseite.
 * Hier können grundlegende Einstellungen wie die Erstellung notwendiger Ordner
 * und die Überprüfung/Sortierung der Comic-Datenbankdatei vorgenommen werden.
 */

// Starte die PHP-Sitzung. Notwendig, wenn diese Seite in den Admin-Bereich eingebunden ist.
session_start();

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    header('Location: index.php');
    exit;
}

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
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
if ($jsonFileExists) {
    $currentComicData = getComicData($comicVarJsonPath);
    if ($currentComicData !== null) { // Prüfen, ob Daten erfolgreich geladen wurden
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
    <!-- CSS für das Admin-Formular, konsistent mit admin/index.php -->
    <style>
        /* Container für das Formularfeld */
        .admin-form-container {
            max-width: 600px; /* Etwas breiter für diese Tools */
            margin: 20px auto;
            padding: 20px;
            border: 1px solid rgba(221, 221, 221, 0.2);
            border-radius: 8px;
            background-color: rgba(240, 240, 240, 0.3);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .main-container.lights-off .admin-form-container {
            background-color: rgba(30, 30, 30, 0.2);
            border-color: rgba(80, 80, 80, 0.15);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            /* Hier wird die Standardtextfarbe für den Dark Mode gesetzt */
            color: #f0f0f0; 
        }
        
        .admin-tool-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #eee;
        }
        .main-container.lights-off .admin-tool-section {
            border-bottom: 1px dashed #555;
        }

        .admin-tool-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .admin-tool-section h3 {
            margin-bottom: 10px;
            color: #333;
        }
        /* Alle Textelemente innerhalb von .admin-form-container sollen im Dunkelmodus weiß sein */
        .main-container.lights-off .admin-form-container h1,
        .main-container.lights-off .admin-form-container h2,
        .main-container.lights-off .admin-form-container h3,
        .main-container.lights-off .admin-form-container p,
        .main-container.lights-off .admin-form-container li { /* Auch Listenelemente */
            color: #f0f0f0 !important; /* Sicherstellen, dass es weiß ist und andere Regeln überschreibt */
        }
        
        /* Spezifische Farben für Statusmeldungen über Klassen */
        .status-message {
            margin: 0; /* Entferne den Standard-Margin des p-Tags innerhalb der Meldungsbox */
            padding: 0; /* Entferne den Standard-Padding des p-Tags innerhalb der Meldungsbox */
            font-weight: bold; /* Standard Bold für alle Meldungen */
        }
        /* Feste Farben für Statusmeldungen im hellen und dunklen Modus */
        .status-green {
            color: #3dcf3f !important; /* Feste grüne Farbe */
        }
        .status-red {
            color: #a94442 !important; /* Feste rote Farbe */
        }
        .status-orange {
            color: #8a6d3b !important; /* Feste orange Farbe */
        }

        .admin-tool-section ul {
            list-style-type: disc;
            margin-left: 20px;
            margin-bottom: 10px;
        }

        .admin-tool-section button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            background-color: #007bff; /* Blau für Standard-Aktionen */
            color: white;
            transition: background-color 0.3s ease;
        }
        .admin-tool-section button:hover {
            background-color: #0056b3;
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
            /* Textfarbe wird durch .status-green gesetzt */
        }
        .main-container.lights-off .message .status-red {
            background-color: rgba(169, 68, 66, 0.3);
            border-color: rgba(235, 204, 209, 0.3);
            /* Textfarbe wird durch .status-red gesetzt */
        }
        .main-container.lights-off .message .status-orange {
            background-color: rgba(138, 109, 59, 0.3);
            border-color: rgba(250, 235, 204, 0.3);
            /* Textfarbe wird durch .status-orange gesetzt */
        }
        
        /* Buttons, die im Dark Theme schwarz bleiben sollen (falls relevant) */
        .admin-tool-section button {
            color: white; /* Standardmäßig weiß für alle Buttons in diesem Abschnitt */
        }
    </style>

    <div class="admin-form-container">
        <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Tool 1: Ordner überprüfen und erstellen -->
        <section class="admin-tool-section">
            <h3>1. Comic-Ordner prüfen und erstellen</h3>
            <p>Überprüft, ob die für die Comics benötigten Ordner existieren, und bietet die Möglichkeit, sie bei Bedarf zu erstellen.</p>
            <?php if (!empty($missingFolders)): ?>
                <p class="status-message status-red">Folgende Ordner fehlen:</p>
                <ul>
                    <?php foreach ($missingFolders as $folder): ?>
                        <li><?php echo htmlspecialchars(basename($folder)); ?></li>
                    <?php endforeach; ?>
                </ul>
                <form action="" method="POST">
                    <button type="submit" name="action" value="create_folders">Fehlende Ordner erstellen</button>
                </form>
            <?php else: ?>
                <p class="status-message status-green">Alle erforderlichen Comic-Ordner existieren.</p>
            <?php endif; ?>
        </section>

        <!-- Tool 2: comic_var.json überprüfen und erstellen -->
        <section class="admin-tool-section">
            <h3>2. `comic_var.json` prüfen und als Vorlage erstellen</h3>
            <p>Überprüft, ob die JSON-Datei für die Comic-Variablen existiert. Falls nicht, kann eine leere Vorlage erstellt werden.</p>
            <?php if (!$jsonFileExists): ?>
                <p class="status-message status-red">Die Datei `comic_var.json` existiert nicht.</p>
                <form action="" method="POST">
                    <button type="submit" name="action" value="create_json">Leere `comic_var.json` erstellen</button>
                </form>
            <?php else: ?>
                <p class="status-message status-green">Die Datei `comic_var.json` existiert.</p>
            <?php endif; ?>
        </section>

        <!-- Tool 3: comic_var.json alphabetisch ordnen -->
        <section class="admin-tool-section">
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
                    <button type="submit" name="action" value="sort_json">`comic_var.json` alphabetisch ordnen</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</article>

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
