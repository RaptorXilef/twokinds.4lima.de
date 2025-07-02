<?php
/**
 * Dies ist die Administrationsseite für den Comic-Seiten-Generator im Admin-Bereich.
 * Sie überprüft fehlende Comic-PHP-Dateien basierend auf der comic_var.json und den Bilddateien
 * und bietet die Möglichkeit, diese automatisiert zu erstellen.
 */

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
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
$comicLowresDirPath = __DIR__ . '/../assets/comic_lowres/';
$comicPagesDirPath = __DIR__ . '/../comic/'; // Pfad zum Ordner, wo die Comic-PHP-Dateien liegen

$message = ''; // Wird für Statusmeldungen an den Benutzer verwendet.

// --- Hilfsfunktionen ---

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
 * Ermittelt alle Comic-IDs basierend auf den Bilddateien im lowres-Ordner.
 * @param string $dirPath Der Pfad zum lowres-Comic-Ordner.
 * @return array Eine Liste der Comic-IDs (Dateinamen ohne Erweiterung), sortiert.
 */
function getComicIdsFromImages(string $dirPath): array {
    $imageIds = [];
    if (is_dir($dirPath)) {
        $files = scandir($dirPath);
        foreach ($files as $file) {
            // Ignoriere . und .. sowie versteckte Dateien und "in_translation" Bilder
            if ($file === '.' || $file === '..' || substr($file, 0, 1) === '.' || strpos($file, 'in_translation') !== false) {
                continue;
            }
            // Extrahiere den Dateinamen ohne Erweiterung (z.B. "20250604" aus "20250604.png")
            $info = pathinfo($file);
            if (isset($info['filename']) && preg_match('/^\d{8}$/', $info['filename'])) { // Stellt sicher, dass es ein Datum im Format YYYYMMDD ist
                $imageIds[] = $info['filename'];
            }
        }
    }
    sort($imageIds); // IDs sortieren, um eine konsistente Reihenfolge zu gewährleisten
    return $imageIds;
}

/**
 * Überprüft, welche Comic-PHP-Dateien fehlen.
 * @param array $comicIds Die Liste der erwarteten Comic-IDs.
 * @param string $comicPagesDir Der Pfad zum Comic-Seiten-Ordner.
 * @return array Eine Liste der fehlenden Comic-IDs.
 */
function getMissingComicPageFiles(array $comicIds, string $comicPagesDir): array {
    $missingFiles = [];
    foreach ($comicIds as $id) {
        $filePath = $comicPagesDir . $id . '.php';
        if (!file_exists($filePath)) {
            $missingFiles[] = $id;
        }
    }
    return $missingFiles;
}

/**
 * Erstellt fehlende Comic-PHP-Dateien.
 * @param array $missingIds Die Liste der IDs, für die Dateien erstellt werden sollen.
 * @param string $comicPagesDir Der Pfad zum Comic-Seiten-Ordner.
 * @return array Eine Liste der erfolgreich erstellten Dateien.
 */
function createComicPageFiles(array $missingIds, string $comicPagesDir): array {
    $createdFiles = [];
    // Sicherstellen, dass das Verzeichnis existiert
    if (!is_dir($comicPagesDir)) {
        if (!mkdir($comicPagesDir, 0777, true)) {
            error_log("Fehler: Comic-Seiten-Verzeichnis nicht erstellbar: " . $comicPagesDir);
            return [];
        }
    }

    $fileContent = "<?php require_once __DIR__ . '/../src/components/comic_page_renderer.php'; ?>";

    foreach ($missingIds as $id) {
        $filePath = $comicPagesDir . $id . '.php';
        if (file_put_contents($filePath, $fileContent) !== false) {
            $createdFiles[] = $id . '.php';
        } else {
            error_log("Fehler beim Erstellen der Datei: " . $filePath);
        }
    }
    return $createdFiles;
}


// --- Verarbeitung von POST-Anfragen ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_missing_comic_pages') {
        $comicIdsFromImages = getComicIdsFromImages($comicLowresDirPath);
        $comicDataFromJson = getComicData($comicVarJsonPath);

        // Kombiniere IDs aus JSON und Bildern, um eine umfassende Liste zu erhalten
        $allComicIds = [];
        if ($comicDataFromJson) {
            $allComicIds = array_unique(array_merge(array_keys($comicDataFromJson), $comicIdsFromImages));
        } else {
            $allComicIds = $comicIdsFromImages;
        }
        sort($allComicIds); // Erneut sortieren nach dem Mergen

        $missingPages = getMissingComicPageFiles($allComicIds, $comicPagesDirPath);

        if (empty($missingPages)) {
            $message = '<p class="status-message status-orange">Es fehlen keine Comic-PHP-Dateien.</p>';
        } else {
            $created = createComicPageFiles($missingPages, $comicPagesDirPath);
            if (!empty($created)) {
                $message = '<p class="status-message status-green">Erfolgreich ' . count($created) . ' Comic-PHP-Dateien erstellt.</p>';
                $message .= '<ul>';
                foreach ($created as $file) {
                    $message .= '<li>' . htmlspecialchars($file) . '</li>';
                }
                $message .= '</ul>';
            } else {
                $message = '<p class="status-message status-red">Fehler beim Erstellen der Comic-PHP-Dateien. Bitte Dateiberechtigungen prüfen.</p>';
            }
        }
    }
}

// --- Statusermittlung für die Anzeige (unabhängig von POST-Requests) ---
$comicIdsFromImages = getComicIdsFromImages($comicLowresDirPath);
$comicDataFromJson = getComicData($comicVarJsonPath);

$allComicIds = [];
if ($comicDataFromJson) {
    $allComicIds = array_unique(array_merge(array_keys($comicDataFromJson), $comicIdsFromImages));
} else {
    $allComicIds = $comicIdsFromImages;
}
sort($allComicIds);

$missingComicPages = getMissingComicPageFiles($allComicIds, $comicPagesDirPath);


// --- HTML-Struktur und Anzeige ---

// Parameter für den Header
$pageTitle = 'Adminbereich - Comic Generator';
$pageHeader = 'Comic-Seiten-Generator';
$siteDescription = 'Generiert fehlende Comic-PHP-Seiten basierend auf vorhandenen Bildern und JSON-Daten.';

// Binde den gemeinsamen Header ein.
$robotsContent = 'noindex, nofollow';
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<article>
    <!-- CSS für das Admin-Formular, konsistent mit admin/index.php und initial_setup.php -->
    <style>
        /* Container für das Formularfeld */
        .admin-form-container {
            max-width: 600px;
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
        .main-container.lights-off .admin-form-container h1,
        .main-container.lights-off .admin-form-container h2,
        .main-container.lights-off .admin-form-container h3,
        .main-container.lights-off .admin-form-container p,
        .main-container.lights-off .admin-form-container li {
            color: #f0f0f0 !important;
        }

        .status-message {
            margin: 0;
            padding: 0;
            font-weight: bold;
        }
        .status-green {
            color: #3dcf3f !important;
        }
        .status-red {
            color: #a94442 !important;
        }
        .status-orange {
            color: #8a6d3b !important;
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
            background-color: #007bff;
            color: white;
            transition: background-color 0.3s ease;
        }
        .admin-tool-section button:hover {
            background-color: #0056b3;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }
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

    <div class="admin-form-container">
        <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <section class="admin-tool-section">
            <h3>Comic-Seiten-Dateien generieren</h3>
            <p>Dieser Generator überprüft, welche Comic-Seiten-PHP-Dateien im <code>./comic/</code> Ordner fehlen, basierend auf den Einträgen in <code>comic_var.json</code> und den vorhandenen Bilddateien im <code>./assets/comic_lowres/</code> Ordner.</p>

            <?php if (empty($allComicIds)): ?>
                <p class="status-message status-orange">Es wurden keine Comic-IDs in <code>comic_var.json</code> oder im <code>./assets/comic_lowres/</code> Ordner gefunden. Bitte stellen Sie sicher, dass Comics vorhanden sind.</p>
            <?php elseif (empty($missingComicPages)): ?>
                <p class="status-message status-green">Alle Comic-PHP-Dateien scheinen vorhanden zu sein.</p>
            <?php else: ?>
                <p class="status-message status-red">Es fehlen <strong><?php echo count($missingComicPages); ?></strong> Comic-PHP-Dateien:</p>
                <ul>
                    <?php foreach ($missingComicPages as $id): ?>
                        <li><code><?php echo htmlspecialchars($id); ?>.php</code></li>
                    <?php endforeach; ?>
                </ul>
                <form action="" method="POST">
                    <button type="submit" name="action" value="generate_missing_comic_pages">Fehlende Comic-Seiten erstellen</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</article>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>
