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
    'config_generator_settings.json' => ['target' => Path::getConfigPath('config_generator_settings.json'), 'github_path' => 'config/config_generator_settings.json'],
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
            if (empty($missingFolders)) {
                $message = 'Alle erforderlichen Ordner existieren bereits.';
                $messageType = 'orange';
            } else {
                $created = createFolders($missingFolders);
                if (!empty($created)) {
                    $message = 'Ordner erfolgreich erstellt: ' . implode(', ', $created);
                    $messageType = 'green';
                } else {
                    $message = 'Fehler beim Erstellen der Ordner.';
                    $messageType = 'red';
                }
            }

            break;
        case 'create_files':
            $results = createRequiredFiles($requiredFiles);
            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            if ($successCount > 0) {
                $message = $successCount . ' Datei(en) erfolgreich von GitHub wiederhergestellt.';
                $messageType = 'green';
            } elseif (empty($results)) {
                $message = 'Alle erforderlichen Dateien existieren bereits.';
                $messageType = 'orange';
            } else {
                $message = 'Fehler beim Erstellen der Dateien.';
                $messageType = 'red';
            }

            break;
        case 'sort_json':
            $comicData = getComicData($comicVarJsonPath);
            if ($comicData === null) {
                $message = '`comic_var.json` existiert nicht oder ist fehlerhaft.';
                $messageType = 'red';
            } elseif (empty($comicData)) {
                $message = '`comic_var.json` ist leer, keine Sortierung nötig.';
                $messageType = 'orange';
            } elseif (isAlphabeticallySorted($comicData)) {
                $message = '`comic_var.json` ist bereits korrekt geordnet.';
                $messageType = 'orange';
            } else {
                ksort($comicData);
                if (saveComicData($comicVarJsonPath, $comicData)) {
                    $message = '`comic_var.json` wurde erfolgreich alphabetisch geordnet.';
                    $messageType = 'green';
                } else {
                    $message = 'Fehler beim Speichern der sortierten `comic_var.json`.';
                    $messageType = 'red';
                }
            }

            break;
    }
}

// Status für die Anzeige ermitteln
$folderStatuses = getFolderStatuses($requiredFolders);
$fileStatuses = getFileStatuses($requiredFiles);
$allFoldersExist = !in_array(false, array_column($folderStatuses, 'exists'), true);
$allFilesExist = !in_array(false, array_column($fileStatuses, 'exists'), true);

$comicVarJsonPathForCheck = Path::getDataPath('comic_var.json');
$currentComicData = getComicData($comicVarJsonPathForCheck);
$jsonFileSorted = $currentComicData === null ? false : isAlphabeticallySorted($currentComicData);

$pageTitle = 'Adminbereich - Ersteinrichtung';
$pageHeader = 'Ersteinrichtung';
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="setup-container">
        <header>
            <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
        </header>

        <?php if (!empty($message)) : ?>
            <div class="status-message status-<?php echo $messageType; ?> visible">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- 1. Ordner -->
        <section class="setup-section">
            <h3>1. Verzeichnisstruktur prüfen</h3>
            <div class="status-list">
                <?php foreach ($folderStatuses as $folder) : ?>
                    <div class="status-item">
                        <span class="status-label"><?php echo htmlspecialchars($folder['name']); ?></span>
                        <span class="status-indicator <?php echo $folder['exists'] ? 'success' : 'error'; ?>">
                            <?php echo $folder['exists'] ? 'Existiert' : 'Fehlt'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$allFoldersExist) : ?>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <button type="submit" name="action" value="create_folders" class="button button-green setup-action-btn">
                        <i class="fas fa-folder-plus"></i> Fehlende Ordner erstellen
                    </button>
                </form>
            <?php else : ?>
                <p class="status-message status-green visible"><i class="fas fa-check"></i> Alle Ordner vorhanden.</p>
            <?php endif; ?>
        </section>

        <!-- 2. Dateien -->
        <section class="setup-section">
            <h3>2. Konfigurations- & Datendateien prüfen</h3>
            <div class="status-list">
                <?php foreach ($fileStatuses as $file) : ?>
                    <div class="status-item">
                        <span class="status-label"><?php echo htmlspecialchars($file['name']); ?></span>
                        <span class="status-indicator <?php echo $file['exists'] ? 'success' : 'error'; ?>">
                            <?php echo $file['exists'] ? 'Existiert' : 'Fehlt'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$allFilesExist) : ?>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <button type="submit" name="action" value="create_files" class="button button-green setup-action-btn">
                        <i class="fas fa-file-download"></i> Fehlende Dateien laden (GitHub)
                    </button>
                </form>
            <?php else : ?>
                <p class="status-message status-green visible"><i class="fas fa-check"></i> Alle Dateien vorhanden.</p>
            <?php endif; ?>
        </section>

        <!-- 3. Sortierung -->
        <section class="setup-section">
            <h3>3. `comic_var.json` Wartung</h3>
            <!-- FIX: Inline-Style entfernt, Styling nun via SCSS (.setup-section .instructions) -->
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
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <button type="submit" name="action" value="sort_json" class="button delete-button setup-action-btn">
                        <i class="fas fa-sort-numeric-down"></i> Jetzt sortieren
                    </button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</article>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
