<?php
/**
 * Dies ist die Administrationsseite für die Erstkonfiguration der Webseite.
 * Hier können grundlegende Einstellungen wie die Erstellung notwendiger Ordner
 * und die Überprüfung/Sortierung der Comic-Datenbankdatei vorgenommen werden.
 * 
 * @file      /admin/initial_setup.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   3.1.0
 * @since     2.1.0 Angepasst an das neue Design mit einheitlichen Statusmeldungen, Button-Stilen
 * @since     2.2.0 Anpassung an versionierte comic_var.json (Schema v2).
 * @since     3.0.0 Umstellung auf neue, ausgelagerte Verzeichnisstruktur (twokinds_src).
 * @since     3.0.1 asset Ordner wieder prüfen
 * @since     3.1.0 Implementiert GitHub-Fallback für fehlende Konfigurationsvorlagen.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG (lädt configLoader.php via admin_init.php) ===
require_once __DIR__ . '/src/components/admin_init.php';

// --- Pfad-Konfiguration ---
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$configTemplatesPath = __DIR__ . '/../config_templates/';

// Fallback-Pfade, falls die Konfiguration noch nicht geladen werden konnte
$srcRootPath = __DIR__ . '/../../../../../twokinds_src';
$configsPath = $srcRootPath . '/configs';
$dbAssetsPath = $srcRootPath . '/assets';

// Pfad-Konstanten aus configLoader.php verwenden, falls verfügbar
if (isset($configExist) && $configExist === true) {
    $srcRootPath = ROOT_SRC_PATH;
    $configsPath = CONFIG_PATH;
    $dbAssetsPath = DB_ASSETS_PATH;
}
$comicVarJsonPath = $dbAssetsPath . '/comic_var.json';

// Definiere die benötigten Ordner und Dateien
$requiredSrcFolders = [
    'Source-Verzeichnis' => $srcRootPath,
    'Configs-Verzeichnis' => $configsPath,
    'Assets-Verzeichnis' => $dbAssetsPath,
];
$requiredPublicFolders = [
    '../assets' => __DIR__ . '/../assets',
    '../assets/comic_hires' => __DIR__ . '/../assets/comic_hires',
    '../assets/comic_lowres' => __DIR__ . '/../assets/comic_lowres',
    '../assets/comic_socialmedia' => __DIR__ . '/../assets/comic_socialmedia',
    '../assets/comic_thumbnails' => __DIR__ . '/../assets/comic_thumbnails',
];
$requiredConfigFiles = [
    'configLoader.php' => ['target' => $srcRootPath . '/configLoader.php', 'template_subpath' => 'configLoader.example.php'],
    'config_main.php' => ['target' => $configsPath . '/config_main.php', 'template_subpath' => 'configs/config_main.example.php'],
    'version.json' => ['target' => __DIR__ . '/../version.json', 'template_subpath' => 'configs/version.example.json'],
];
$requiredAssetFiles = [
    'admin_users.json' => ['target' => $dbAssetsPath . '/admin_users.json', 'template_subpath' => 'assets/admin_users.example.json'],
    'login_attempts.json' => ['target' => $dbAssetsPath . '/login_attempts.json', 'template_subpath' => 'assets/login_attempts.example.json'],
    'archive_chapters.json' => ['target' => $dbAssetsPath . '/archive_chapters.json', 'template_subpath' => 'assets/archive_chapters.example.json'],
    'charaktere.json' => ['target' => $dbAssetsPath . '/charaktere.json', 'template_subpath' => 'assets/charaktere.example.json'],
    'comic_image_cache.json' => ['target' => $dbAssetsPath . '/comic_image_cache.json', 'template_subpath' => 'assets/comic_image_cache.example.json'],
    'comic_var.json' => ['target' => $comicVarJsonPath, 'template_subpath' => 'assets/comic_var.example.json'],
    'generator_settings.json' => ['target' => $dbAssetsPath . '/generator_settings.json', 'template_subpath' => 'assets/generator_settings.example.json'],
    'rss_config.json' => ['target' => $dbAssetsPath . '/rss_config.json', 'template_subpath' => 'assets/rss_config.example.json'],
    'sitemap.json' => ['target' => $dbAssetsPath . '/sitemap.json', 'template_subpath' => 'assets/sitemap.example.json'],
];
$message = '';

// --- Funktionen ---
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
        if (!is_dir($folder) && mkdir($folder, 0777, true)) {
            $created[] = $folder;
        }
    }
    return $created;
}

function getRequiredFileStatuses(array $files, string $templatesPath): array
{
    $statuses = [];
    foreach ($files as $name => $details) {
        $statuses[] = [
            'name' => $name,
            'path' => $details['target'],
            'exists' => file_exists($details['target']),
            'templateExists' => file_exists($templatesPath . $details['template_subpath'])
        ];
    }
    return $statuses;
}

function createRequiredFiles(array $files, string $templatesPath): array
{
    $results = [];
    foreach ($files as $name => $details) {
        $targetPath = $details['target'];
        if (!file_exists($targetPath)) {
            $templatePath = $templatesPath . $details['template_subpath'];

            if (!file_exists($templatePath)) {
                // Versuche, von GitHub zu laden
                $githubUrl = 'https://raw.githubusercontent.com/RaptorXilef/twokinds.4lima.de/main/config_templates/' . $details['template_subpath'];
                $templateContent = @file_get_contents($githubUrl);

                if ($templateContent !== false) {
                    if (file_put_contents($targetPath, $templateContent) !== false) {
                        $results[] = ['name' => $name, 'status' => 'copied', 'message' => 'aus GitHub-Repository geladen'];
                    } else {
                        $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Fehler beim Speichern der GitHub-Vorlage'];
                    }
                } else {
                    $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Vorlage lokal und auf GitHub nicht gefunden'];
                }
                continue;
            }

            if (copy($templatePath, $targetPath)) {
                $results[] = ['name' => $name, 'status' => 'copied', 'message' => 'aus lokaler Vorlage kopiert'];
            } else {
                $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Fehler beim Kopieren der lokalen Vorlage'];
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

// --- POST-Verarbeitung ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_src_folders':
        case 'create_public_folders':
            $foldersToCheck = ($action === 'create_src_folders') ? $requiredSrcFolders : $requiredPublicFolders;
            $missingFolders = array_filter(getFolderStatuses($foldersToCheck), fn($f) => !$f['exists']);
            if (empty($missingFolders)) {
                $message = '<p class="status-message status-orange">Alle erforderlichen Ordner existieren bereits.</p>';
            } else {
                $created = createFolders(array_column($missingFolders, 'path'));
                $message = !empty($created) ? '<p class="status-message status-green">Ordner erfolgreich erstellt: ' . implode(', ', array_map('basename', $created)) . '</p>' : '<p class="status-message status-red">Fehler beim Erstellen der Ordner.</p>';
            }
            break;
        case 'create_core_configs':
        case 'create_asset_files':
            $filesToCreate = ($action === 'create_core_configs') ? $requiredConfigFiles : $requiredAssetFiles;
            $results = createRequiredFiles($filesToCreate, $configTemplatesPath);
            $successCount = count(array_filter($results, fn($r) => $r['status'] !== 'error'));
            if ($successCount > 0) {
                $message = '<p class="status-message status-green">Dateien erfolgreich erstellt/kopiert.</p>';
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
$folderStatusesSrc = getFolderStatuses($requiredSrcFolders);
$folderStatusesPublic = getFolderStatuses($requiredPublicFolders);
$configStatuses = getRequiredFileStatuses($requiredConfigFiles, $configTemplatesPath);
$assetStatuses = getRequiredFileStatuses($requiredAssetFiles, $configTemplatesPath);

$allSrcFoldersExist = !in_array(false, array_column($folderStatusesSrc, 'exists'));
$allPublicFoldersExist = !in_array(false, array_column($folderStatusesPublic, 'exists'));
$allConfigsExist = !in_array(false, array_column($configStatuses, 'exists'));
$allAssetsExist = !in_array(false, array_column($assetStatuses, 'exists'));

$currentComicData = getComicData($comicVarJsonPath);
$jsonFileSorted = ($currentComicData !== null) ? isAlphabeticallySorted($currentComicData) : true;

// --- HTML-Struktur und Anzeige ---
$pageTitle = 'Adminbereich - Ersteinrichtung';
$pageHeader = 'Webseiten-Ersteinrichtung';
include $headerPath;
?>

<article>
    <div class="admin-form-container">
        <header>
            <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
        </header>

        <?php if (!empty($message))
            echo "<div class=\"message\">{$message}</div>"; ?>
        <?php if (!isset($configExist) || $configExist !== true)
            echo '<p class="status-message status-red"><strong>WICHTIG:</strong> Die zentrale Konfigurationsdatei (`configLoader.php`) konnte nicht geladen werden. Bitte erstellen Sie zuerst die Ordner und Kern-Konfigurationsdateien.</p>'; ?>
        
        <section class="content-section">
            <h3>1. System-Verzeichnisstruktur prüfen</h3>
            <div class="status-list">
                <?php foreach ($folderStatusesSrc as $folder): ?>
                    <div class="status-item"><span><?php echo htmlspecialchars($folder['name']); ?>:</span><span
                            class="status-indicator <?php echo $folder['exists'] ? 'status-green-text' : 'status-red-text'; ?>"><?php echo $folder['exists'] ? 'Existiert' : 'Fehlt'; ?></span>
                </div>
                <?php endforeach; ?>
                </div>
                <?php if (!$allSrcFoldersExist): ?>
                    <form action="" method="POST"><input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><button type="submit" name="action"
                            value="create_src_folders" class="status-green-button">Fehlende System-Ordner erstellen</button></form>
                <?php endif; ?>
        </section>

        <section class="content-section">
            <h3>2. Öffentliche Asset-Ordner prüfen</h3>
            <div class="status-list">
                <?php foreach ($folderStatusesPublic as $folder): ?>
                    <div class="status-item"><span><?php echo htmlspecialchars($folder['name']); ?>:</span><span
                            class="status-indicator <?php echo $folder['exists'] ? 'status-green-text' : 'status-red-text'; ?>"><?php echo $folder['exists'] ? 'Existiert' : 'Fehlt'; ?></span>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php if (!$allPublicFoldersExist): ?>
                <form action="" method="POST"><input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><button type="submit" name="action"
                        value="create_public_folders" class="status-green-button">Fehlende Asset-Ordner erstellen</button></form>
            <?php endif; ?>
            </section>
            
            <section class="content-section">
                <h3>3. Kern-Konfigurationsdateien prüfen</h3>
                <div class="status-list">
                    <?php foreach ($configStatuses as $file): ?>
                        <div class="status-item"><span><?php echo htmlspecialchars($file['name']); ?>:</span><span
                                class="status-indicator <?php echo $file['exists'] ? 'status-green-text' : 'status-red-text'; ?>"><?php echo $file['exists'] ? 'Existiert' : ($file['templateExists'] ? 'Fehlt (Vorlage da)' : 'Fehlt'); ?></span>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php if (!$allConfigsExist): ?>
                    <form action="" method="POST"><input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><button type="submit" name="action"
                        value="create_core_configs" class="status-green-button">Fehlende Konfig-Dateien erstellen</button></form>
            <?php endif; ?>
        </section>

        <section class="content-section">
            <h3>4. Asset- & Datenbank-Dateien prüfen</h3>
            <div class="status-list">
                <?php foreach ($assetStatuses as $file): ?>
                    <div class="status-item"><span><?php echo htmlspecialchars($file['name']); ?>:</span><span
                            class="status-indicator <?php echo $file['exists'] ? 'status-green-text' : 'status-red-text'; ?>"><?php echo $file['exists'] ? 'Existiert' : ($file['templateExists'] ? 'Fehlt (Vorlage da)' : 'Fehlt'); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$allAssetsExist): ?>
                <form action="" method="POST"><input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><button type="submit" name="action"
                    value="create_asset_files" class="status-green-button">Fehlende Asset-Dateien erstellen</button></form>
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
    :root { --missing-grid-border-color: #e0e0e0; --missing-grid-bg-color: #f9f9f9; --default-text-color: #333; --status-green-text: #155724; --status-red-text: #721c24; }
    body.theme-night { --missing-grid-border-color: #045d81; --missing-grid-bg-color: #03425b; --default-text-color: #f0f0f0; --status-green-text: #28a745; --status-red-text: #dc3545; }
    .admin-form-container { max-width: 825px; margin: 20px auto; padding: 20px; border: 1px solid rgba(221, 221, 221, 0.2); border-radius: 8px; background-color: rgba(240, 240, 240, 0.2); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); color: var(--default-text-color); }
    body.theme-night .admin-form-container { background-color: rgba(30, 30, 30, 0.2); border-color: rgba(80, 80, 80, 0.15); }
    .content-section { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px dashed #eee; }
    body.theme-night .content-section { border-bottom: 1px dashed #555; }
    .content-section:last-child { border-bottom: none; }
    .content-section h3 { margin-bottom: 10px; }
    .message { margin-bottom: 15px; }
    .status-message { padding: 8px 12px; border-radius: 5px; margin-top: 10px; margin-bottom: 10px; }
    .status-green { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-orange { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .status-red { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .status-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .status-green-text, .status-red-text { font-weight: bold; }
    .status-green-text { color: var(--status-green-text); }
    .status-red-text { color: var(--status-red-text); }
    .status-red-button, .status-green-button { color: white; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 1em; border: none; display: block; width: fit-content; margin-top: 10px; }
    .status-red-button { background-color: #dc3545; }
    .status-red-button:hover { background-color: #c82333; }
    .status-green-button { background-color: #28a745; }
    .status-green-button:hover { background-color: #218838; }
    .status-list { margin-top: 10px; margin-bottom: 15px; padding: 10px; border: 1px solid var(--missing-grid-border-color); border-radius: 5px; background-color: var(--missing-grid-bg-color); }
    .status-item { padding: 4px 0; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed var(--missing-grid-border-color); }
    .status-item:last-child { border-bottom: none; }
    .status-indicator { font-weight: bold; }
</style>

<?php include $footerPath; ?>