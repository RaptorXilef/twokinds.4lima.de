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
 *
 * @since 2.0.0 - 4.0.0
 * - Infrastruktur & Verzeichnisse:
 *  - Umstellung auf ausgelagerte Verzeichnisstruktur (`twokinds_src`) und dynamische Path-Helfer-Klasse.
 *  - Einführung von Konstanten für Pfade und vollständiger Wechsel auf GitHub-Download als Quelle.
 *  - Implementierung eines GitHub-Fallbacks für fehlende Konfigurationsvorlagen.
 *
 * - Daten & Anpassungen:
 *  - Anpassung an versionierte `comic_var.json` (Schema v2).
 *  - UI-Anpassung an das neue Design (einheitliche Statusmeldungen und Button-Stile).
 *
 * - Fixes:
 *  - Korrekturen bei der Prüfung von Asset-Ordnern und `$requiredFiles`.
 *
 * @since     5.0.0
 * - refactor(UI): Komplettes Redesign mit SCSS (.setup-container, .status-list).
 * - refactor(Code): Entfernung von Inline-CSS und veralteten HTML-Strukturen.
 * - fix(Standard): Nutzung der globalen Status-Klassen (.status-message).
 * - refactor(Config): `config_generator_settings.json` nach `config/admin/` verschoben.
 * - feat(Config): Ordner `config/admin` zu den benötigten Ordnern hinzugefügt.
 * - refactor(UI): Letzte Inline-Styles entfernt (Float).
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- ZU PRÜFENDE ORDNER UND DATEIEN ---
$requiredFolders = [
    'Private > Source' => DIRECTORY_PRIVATE_SRC,
    'Private > Config' => DIRECTORY_PRIVATE_CONFIG,
    'Private > Config > Secrets' => DIRECTORY_PRIVATE_SECRETS,
    'Private > Config > Admin' => DIRECTORY_PRIVATE_CONFIG . DIRECTORY_SEPARATOR . 'admin', // NEU: Admin Config Ordner
    'Private > Data' => DIRECTORY_PRIVATE_DATA,
    'Private > Data > Cache' => DIRECTORY_PRIVATE_CACHE,
    'Public > Assets' => DIRECTORY_PUBLIC_ASSETS,
    'Public > Assets > Comic Hires' => DIRECTORY_PUBLIC_IMG_COMIC_HIRES,
    'Public > Assets > Comic Lowres' => DIRECTORY_PUBLIC_IMG_COMIC_LOWRES,
    'Public > Assets > Comic Socialmedia' => DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA,
    'Public > Assets > Comic Thumbnails' => DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS,
];

$requiredFiles = [
    'config_main.php' => ['target' => Path::getConfigPath('config_main.php'), 'github_path' => 'config/config_main.php'],
    'config_folder_path.php' => ['target' => Path::getConfigPath('config_folder_path.php'), 'github_path' => 'config/config_folder_path.php'],
    'admin_users.json' => ['target' => Path::getSecretPath('admin_users.json'), 'github_path' => 'config/secrets/admin_users.json'],
    'login_attempts.json' => ['target' => Path::getSecretPath('login_attempts.json'), 'github_path' => 'config/secrets/login_attempts.json'],
    'version.json' => ['target' => Path::getDataPath('version.json'), 'github_path' => 'data/version.json'],
    'archive_chapters.json' => ['target' => Path::getDataPath('archive_chapters.json'), 'github_path' => 'data/archive_chapters.json'],
    'charaktere.json' => ['target' => Path::getDataPath('charaktere.json'), 'github_path' => 'data/charaktere.json'],
    'comic_image_cache.json' => ['target' => Path::getCachePath('comic_image_cache.json'), 'github_path' => 'data/cache/comic_image_cache.json'],
    'comic_var.json' => ['target' => Path::getDataPath('comic_var.json'), 'github_path' => 'data/comic_var.json'],
    // NEU: Zielpfad ist nun im admin-Unterordner
    'config_generator_settings.json' => ['target' => Path::getConfigPath('admin/config_generator_settings.json'), 'github_path' => 'config/config_generator_settings.json'],
    'config_rss.json' => ['target' => Path::getConfigPath('config_rss.json'), 'github_path' => 'config/config_rss.json'],
    'sitemap.json' => ['target' => Path::getDataPath('sitemap.json'), 'github_path' => 'data/sitemap.json'],
];

$message = '';
$messageType = 'info';

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
                    $results[] = ['name' => $name, 'status' => 'error', 'message' => 'Fehler beim Speichern der von GitHub bezogenen Datei'];
                }
            } else {
                $results[] = ['name' => $name, 'status' => 'error', 'message' => 'GitHub-Quelle nicht gefunden'];
            }
        }
    }
    return $results;
}

function getComicData(string $filePath): ?array
{
    if (!file_exists($filePath)) {
        return null;
    }
    $content = file_get_contents($filePath);
    if ($content === false) {
        return null;
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
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
    if (empty($data)) {
        return true;
    }
    $keys = array_keys($data);
    $sortedKeys = $keys;
    sort($sortedKeys);
    return ($keys === $sortedKeys);
}

// --- POST-VERARBEITUNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';
    $comicVarJsonPath = Path::getDataPath('comic_var.json');

    switch ($action) {
        case 'create_folders':
            $missingFolders = array_filter(getFolderStatuses($requiredFolders), fn($f) => !$f['exists']);
            $created = createFolders($missingFolders);
            if (!empty($created)) {
                $message = count($created) . ' Ordner wurden erfolgreich erstellt.';
                $messageType = 'success';
            } else {
                $message = 'Es konnten keine Ordner erstellt werden (evtl. Berechtigungsproblem).';
                $messageType = 'error';
            }
            break;

        case 'create_files':
            $missingFiles = array_filter(getFileStatuses($requiredFiles), fn($f) => !$f['exists']);
            // Filtere nur die benötigten Details
            $filesToCreate = array_intersect_key($requiredFiles, array_flip(array_column($missingFiles, 'name')));
            $results = createRequiredFiles($filesToCreate);

            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            if ($successCount > 0) {
                $message = "$successCount Datei(en) wurden erstellt.";
                $messageType = 'success';
            } else {
                $message = 'Fehler beim Erstellen der Dateien.';
                $messageType = 'error';
            }
            break;

        case 'sort_json':
            $comicData = getComicData($comicVarJsonPath);
            if ($comicData !== null) {
                ksort($comicData);
                if (saveComicData($comicVarJsonPath, $comicData)) {
                    $message = '`comic_var.json` wurde erfolgreich sortiert und als Schema v2 gespeichert.';
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Speichern der `comic_var.json`.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Konnte `comic_var.json` nicht laden.';
                $messageType = 'error';
            }
            break;
    }
}

// --- STATUS-CHECK ---
$folderStatuses = getFolderStatuses($requiredFolders);
$allFoldersExist = empty(array_filter($folderStatuses, fn($f) => !$f['exists']));

$fileStatuses = getFileStatuses($requiredFiles);
$allFilesExist = empty(array_filter($fileStatuses, fn($f) => !$f['exists']));

$comicVarJsonPath = Path::getDataPath('comic_var.json');
$currentComicData = getComicData($comicVarJsonPath);
$jsonFileSorted = ($currentComicData !== null) && isAlphabeticallySorted($currentComicData);


$pageTitle = 'Initial Setup';
$pageHeader = 'System-Einrichtung';
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="setup-container content-section">
        <div id="settings-and-actions-container">
            <h2>Initial Setup</h2>
            <p>Überprüfung und Einrichtung der Systemumgebung.</p>
        </div>

        <?php if ($message) : ?>
            <div class="status-message status-<?php echo ($messageType === 'success' ? 'green' : ($messageType === 'error' ? 'red' : 'info')); ?> visible">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- 1. ORDNER STRUKTUR -->
        <section class="setup-section collapsible-section expanded">
            <div class="collapsible-header">
                <h3>1. Ordner-Struktur</h3>
                <!-- Icon oder Status hier -->
            </div>
            <div class="collapsible-content">
                <ul class="status-list">
                    <?php foreach ($folderStatuses as $folder) : ?>
                        <li class="status-item <?php echo $folder['exists'] ? 'status-ok' : 'status-missing'; ?>">
                            <span class="status-label"><?php echo htmlspecialchars($folder['name']); ?></span>
                            <span class="status-icon">
                                <?php if ($folder['exists']) : ?>
                                    <i class="fas fa-check-circle" title="Vorhanden"></i>
                                    <span class="sr-only">OK</span>
                                <?php else : ?>
                                    <i class="fas fa-times-circle" title="Fehlt"></i>
                                    <span class="sr-only">Fehlt</span>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!$allFoldersExist) : ?>
                    <form action="" method="POST" class="setup-actions">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit" name="action" value="create_folders" class="button button-green setup-action-btn">
                            <i class="fas fa-folder-plus"></i> Fehlende Ordner erstellen
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <!-- 2. DATEIEN -->
        <section class="setup-section collapsible-section expanded">
             <div class="collapsible-header">
                <h3>2. Notwendige Dateien</h3>
            </div>
            <div class="collapsible-content">
                <ul class="status-list">
                    <?php foreach ($fileStatuses as $file) : ?>
                        <li class="status-item <?php echo $file['exists'] ? 'status-ok' : 'status-missing'; ?>">
                            <span class="status-label"><?php echo htmlspecialchars($file['name']); ?></span>
                            <span class="status-icon">
                                <?php if ($file['exists']) : ?>
                                    <i class="fas fa-check-circle" title="Vorhanden"></i>
                                <?php else : ?>
                                    <i class="fas fa-times-circle" title="Fehlt"></i>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!$allFilesExist) : ?>
                    <form action="" method="POST" class="setup-actions">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit" name="action" value="create_files" class="button button-green setup-action-btn">
                            <i class="fas fa-file-download"></i> Fehlende Dateien laden (GitHub)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <!-- 3. DATENBANK SORTIERUNG -->
        <section class="setup-section collapsible-section expanded">
             <div class="collapsible-header">
                <h3>3. Datenbank-Prüfung</h3>
            </div>
            <div class="collapsible-content">
                <p class="instructions">
                    Hier wird geprüft, ob die Comic-Datenbank korrekt sortiert ist (nach ID). Dies ist wichtig für die Navigation.
                </p>
                <?php if ($currentComicData === null) : ?>
                    <p class="status-message status-orange visible">Datei `comic_var.json` existiert nicht.</p>
                <?php elseif (empty($currentComicData)) : ?>
                    <p class="status-message status-info visible">Datei `comic_var.json` ist leer.</p>
                <?php elseif ($jsonFileSorted) : ?>
                    <p class="status-message status-green visible"><i class="fas fa-check"></i> Datei ist korrekt sortiert.</p>
                <?php else : ?>
                    <p class="status-message status-red visible"><i class="fas fa-exclamation-triangle"></i> Datei ist nicht sortiert.</p>
                    <form action="" method="POST" class="setup-actions">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit" name="action" value="sort_json" class="button delete-button setup-action-btn">
                            <i class="fas fa-sort-numeric-down"></i> Jetzt sortieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>
</article>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
