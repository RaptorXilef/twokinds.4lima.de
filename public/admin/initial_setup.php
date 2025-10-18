<?php
/**
 * Dies ist die Administrationsseite für die Erstkonfiguration der Webseite.
 * Hier können grundlegende Einstellungen wie die Erstellung notwendiger Ordner
 * und die Überprüfung/Sortierung der Comic-Datenbankdatei vorgenommen werden.
 * 
 * @file      ROOT/public/admin/initial_setup.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.1.0 Angepasst an das neue Design mit einheitlichen Statusmeldungen, Button-Stilen
 * @since     2.2.0 Anpassung an versionierte comic_var.json (Schema v2).
 * @since     3.0.0 Umstellung auf neue, ausgelagerte Verzeichnisstruktur (twokinds_src).
 * @since     3.0.1 asset Ordner wieder prüfen
 * @since     3.1.0 Implementiert GitHub-Fallback für fehlende Konfigurationsvorlagen.
 * @since     3.5.0 Vollständige Umstellung auf Konstanten und reinen GitHub-Download, Entfernung lokaler Vorlagen.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 * @since     4.0.1 Zwei Korrekturen bei $requiredFiles [getComponent wird zu getConfig für config_main.php und config_folder_path.php
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- ZU PRÜFENDE ORDNER UND DATEIEN ---
$requiredFolders = [
    'Private > Source' => DIRECTORY_PRIVATE_SRC,
    'Private > Config' => DIRECTORY_PRIVATE_CONFIG,
    'Private > Config > Secrets' => DIRECTORY_PRIVATE_SECRETS,
    'Private > Data' => DIRECTORY_PRIVATE_DATA,
    'Private > Data > Cache' => DIRECTORY_PRIVATE_CACHE,
    'Public > Assets' => DIRECTORY_PUBLIC_ASSETS,
    'Public > Assets > Comic Hires' => DIRECTORY_PUBLIC_IMG_COMIC_HIRES,
    'Public > Assets > Comic Lowres' => DIRECTORY_PUBLIC_IMG_COMIC_LOWRES,
    'Public > Assets > Comic Socialmedia' => DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA,
    'Public > Assets > Comic Thumbnails' => DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS,
];

$requiredFiles = [
    'config_main.php' => ['target' => Path::getConfig('config_main.php'), 'github_path' => 'config/config_main.php'],
    'config_folder_path.php' => ['target' => Path::getConfig('config_folder_path.php'), 'github_path' => 'config/config_folder_path.php'],
    'admin_users.json' => ['target' => Path::getSecret('admin_users.json'), 'github_path' => 'config/secrets/admin_users.json'],
    'login_attempts.json' => ['target' => Path::getSecret('login_attempts.json'), 'github_path' => 'config/secrets/login_attempts.json'],
    'version.json' => ['target' => Path::getData('version.json'), 'github_path' => 'data/version.json'],
    'archive_chapters.json' => ['target' => Path::getData('archive_chapters.json'), 'github_path' => 'data/archive_chapters.json'],
    'charaktere.json' => ['target' => Path::getData('charaktere.json'), 'github_path' => 'data/charaktere.json'],
    'comic_image_cache.json' => ['target' => Path::getCache('comic_image_cache.json'), 'github_path' => 'data/cache/comic_image_cache.json'],
    'comic_var.json' => ['target' => Path::getData('comic_var.json'), 'github_path' => 'data/comic_var.json'],
    'config_generator_settings.json' => ['target' => Path::getConfig('config_generator_settings.json'), 'github_path' => 'config/config_generator_settings.json'],
    'config_rss.json' => ['target' => Path::getConfig('config_rss.json'), 'github_path' => 'config/config_rss.json'],
    'sitemap.json' => ['target' => Path::getData('sitemap.json'), 'github_path' => 'data/sitemap.json'],
];

$message = '';

// --- FUNKTIONEN ---
function getFolderStatuses(array $folders): array
{
    $statuses = [];
    foreach ($folders as $name => $path) {
        $statuses[] = ['name' => $name, 'path' => $path, 'exists' => is_dir($path)];
    }
    return $statuses;
}

function createFolders(array $folders): array
{
    $created = [];
    foreach ($folders as $folder) {
        if (!is_dir($folder['path']) && mkdir($folder['path'], 0777, true)) {
            $created[] = $folder['name'];
        }
    }
    return $created;
}

function getFileStatuses(array $files): array
{
    $statuses = [];
    foreach ($files as $name => $details) {
        $statuses[] = ['name' => $name, 'path' => $details['target'], 'exists' => file_exists($details['target'])];
    }
    return $statuses;
}

function createRequiredFiles(array $files): array
{
    $results = [];
    $githubBaseUrl = 'https://raw.githubusercontent.com/RaptorXilef/twokinds.4lima.de/main/';

    foreach ($files as $name => $details) {
        $targetPath = $details['target'];
        if (!file_exists($targetPath)) {
            $githubUrl = $githubBaseUrl . $details['github_path'];
            $templateContent = @file_get_contents($githubUrl);

            if ($templateContent !== false) {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                if (file_put_contents($targetPath, $templateContent) !== false) {
                    $results[] = ['name' => $name, 'status' => 'success', 'message' => 'aus GitHub geladen'];
                } else {
                    $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Fehler beim Speichern der GitHub-Datei'];
                }
            } else {
                $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Datei auf GitHub nicht gefunden'];
            }
        }
    }
    return $results;
}

function getComicData(string $filePath): ?array
{
    if (!file_exists($filePath))
        return null;
    $content = file_get_contents($filePath);
    if ($content === false)
        return null;
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE)
        return null;
    if (is_array($data)) {
        return $data['comics'] ?? $data; // Handles both v2 and v1
    }
    return [];
}

function saveComicData(string $filePath, array $comicsData): bool
{
    $dataToSave = ['schema_version' => 2, 'comics' => $comicsData];
    return file_put_contents($filePath, json_encode($dataToSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function isAlphabeticallySorted(array $data): bool
{
    if (empty($data))
        return true;
    $keys = array_keys($data);
    $sortedKeys = $keys;
    sort($sortedKeys);
    return ($keys === $sortedKeys);
}

// --- POST-VERARBEITUNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';
    $comicVarJsonPath = Path::getData('comic_var.json'); // Definiere den Pfad hier

    switch ($action) {
        case 'create_folders':
            $missingFolders = array_filter(getFolderStatuses($requiredFolders), fn($f) => !$f['exists']);
            if (empty($missingFolders)) {
                $message = '<p class="status-message status-orange">Alle erforderlichen Ordner existieren bereits.</p>';
            } else {
                $created = createFolders($missingFolders);
                $message = !empty($created) ? '<p class="status-message status-green">Ordner erfolgreich erstellt: ' . implode(', ', $created) . '</p>' : '<p class="status-message status-red">Fehler beim Erstellen der Ordner.</p>';
            }
            break;
        case 'create_files':
            $results = createRequiredFiles($requiredFiles);
            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            if ($successCount > 0) {
                $message = '<p class="status-message status-green">' . $successCount . ' Datei(en) erfolgreich von GitHub erstellt.</p>';
            } elseif (empty($results)) {
                $message = '<p class="status-message status-orange">Alle erforderlichen Dateien existieren bereits.</p>';
            } else {
                $message = '<p class="status-message status-red">Fehler beim Erstellen der Dateien.</p>';
            }
            break;
        case 'sort_json':
            $comicData = getComicData($comicVarJsonPath);
            if ($comicData === null) {
                $message = '<p class="status-message status-red">`comic_var.json` existiert nicht oder ist fehlerhaft.</p>';
            } elseif (empty($comicData)) {
                $message = '<p class="status-message status-orange">`comic_var.json` ist leer, keine Sortierung nötig.</p>';
            } elseif (isAlphabeticallySorted($comicData)) {
                $message = '<p class="status-message status-orange">`comic_var.json` ist bereits korrekt geordnet.</p>';
            } else {
                ksort($comicData);
                $message = saveComicData($comicVarJsonPath, $comicData)
                    ? '<p class="status-message status-green">`comic_var.json` wurde erfolgreich alphabetisch geordnet.</p>'
                    : '<p class="status-message status-red">Fehler beim Speichern der sortierten `comic_var.json`.</p>';
            }
            break;
    }
}

// Status für die Anzeige ermitteln
$folderStatuses = getFolderStatuses($requiredFolders);
$fileStatuses = getFileStatuses($requiredFiles);
$allFoldersExist = !in_array(false, array_column($folderStatuses, 'exists'));
$allFilesExist = !in_array(false, array_column($fileStatuses, 'exists'));

$comicVarJsonPathForCheck = Path::getData('comic_var.json');
$currentComicData = getComicData($comicVarJsonPathForCheck);
$jsonFileSorted = $currentComicData === null ? false : isAlphabeticallySorted($currentComicData);


$pageTitle = 'Adminbereich - Ersteinrichtung';
$pageHeader = 'Webseiten-Ersteinrichtung';
require_once Path::getTemplatePartial('header.php');
?>

<article>
    <div class="admin-form-container">
        <header>
            <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
        </header>
        <?php if (!empty($message))
            echo $message; ?>

        <section class="content-section">
            <h3>1. Verzeichnisstruktur prüfen</h3>
            <div class="status-list">
                <?php foreach ($folderStatuses as $folder): ?>
                    <div class="status-item"><span><?php echo htmlspecialchars($folder['name']); ?>:</span><span
                            class="status-indicator <?php echo $folder['exists'] ? 'status-green-text' : 'status-red-text'; ?>"><?php echo $folder['exists'] ? 'Existiert' : 'Fehlt'; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$allFoldersExist): ?>
                <form action="" method="POST"><input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($csrfToken); ?>"><button type="submit" name="action"
                        value="create_folders" class="status-green-button">Fehlende Ordner erstellen</button></form>
            <?php endif; ?>
        </section>

        <section class="content-section">
            <h3>2. Konfigurations- & Datendateien prüfen</h3>
            <div class="status-list">
                <?php foreach ($fileStatuses as $file): ?>
                    <div class="status-item"><span><?php echo htmlspecialchars($file['name']); ?>:</span><span
                            class="status-indicator <?php echo $file['exists'] ? 'status-green-text' : 'status-red-text'; ?>"><?php echo $file['exists'] ? 'Existiert' : 'Fehlt (wird von GitHub geladen)'; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$allFilesExist): ?>
                <form action="" method="POST"><input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($csrfToken); ?>"><button type="submit" name="action"
                        value="create_files" class="status-green-button">Fehlende Dateien erstellen</button></form>
            <?php endif; ?>
        </section>

        <section class="content-section">
            <h3>5. `comic_var.json` alphabetisch ordnen</h3>
            <?php if ($currentComicData === null): ?>
                <p class="status-message status-orange">Datei `comic_var.json` existiert nicht. Bitte zuerst erstellen.</p>
            <?php elseif (empty($currentComicData)): ?>
                <p class="status-message status-orange">Datei `comic_var.json` ist leer und muss nicht sortiert werden.</p>
            <?php elseif ($jsonFileSorted): ?>
                <p class="status-message status-green">Datei `comic_var.json` ist bereits korrekt alphabetisch geordnet.</p>
            <?php else: ?>
                <p class="status-message status-red">Datei `comic_var.json` ist nicht alphabetisch geordnet.</p>
                <form action="" method="POST"><input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><button type="submit" name="action"
                        value="sort_json" class="status-red-button">`comic_var.json` alphabetisch ordnen</button></form>
            <?php endif; ?>
        </section>
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    :root {
        --missing-grid-border-color: #e0e0e0;
        --missing-grid-bg-color: #f9f9f9;
        --default-text-color: #333;
        --status-green-text: #155724;
        --status-red-text: #721c24;
    }

    body.theme-night {
        --missing-grid-border-color: #045d81;
        --missing-grid-bg-color: #03425b;
        --default-text-color: #f0f0f0;
        --status-green-text: #28a745;
        --status-red-text: #dc3545;
    }

    .admin-form-container {
        max-width: 825px;
        margin: 20px auto;
        padding: 20px;
        border: 1px solid rgba(221, 221, 221, 0.2);
        border-radius: 8px;
        background-color: rgba(240, 240, 240, 0.2);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        color: var(--default-text-color);
    }

    body.theme-night .admin-form-container {
        background-color: rgba(30, 30, 30, 0.2);
        border-color: rgba(80, 80, 80, 0.15);
    }

    .content-section {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #eee;
    }

    body.theme-night .content-section {
        border-bottom: 1px dashed #555;
    }

    .content-section:last-child {
        border-bottom: none;
    }

    .content-section h3 {
        margin-bottom: 10px;
    }

    .message {
        margin-bottom: 15px;
    }

    .status-message {
        padding: 8px 12px;
        border-radius: 5px;
        margin-top: 10px;
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

    .status-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .status-green-text,
    .status-red-text {
        font-weight: bold;
    }

    .status-green-text {
        color: var(--status-green-text);
    }

    .status-red-text {
        color: var(--status-red-text);
    }

    .status-red-button,
    .status-green-button {
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        border: none;
        display: block;
        width: fit-content;
        margin-top: 10px;
    }

    .status-red-button {
        background-color: #dc3545;
    }

    .status-red-button:hover {
        background-color: #c82333;
    }

    .status-green-button {
        background-color: #28a745;
    }

    .status-green-button:hover {
        background-color: #218838;
    }

    .status-list {
        margin-top: 10px;
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid var(--missing-grid-border-color);
        border-radius: 5px;
        background-color: var(--missing-grid-bg-color);
    }

    .status-item {
        padding: 4px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px dashed var(--missing-grid-border-color);
    }

    .status-item:last-child {
        border-bottom: none;
    }

    .status-indicator {
        font-weight: bold;
    }
</style>

<?php require_once Path::getTemplatePartial('footer.php'); ?>