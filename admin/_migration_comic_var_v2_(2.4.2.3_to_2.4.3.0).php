<?php
/**
 * Migrationsskript, um comic_var.json auf Schema-Version 2 zu aktualisieren.
 *
 * @file      /admin/_migration_comic_var_v2.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 */

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/src/components/admin_init.php';

$comicVarPath = __DIR__ . '/../src/config/comic_var.json';
$pageTitle = 'Migration: comic_var.json zu v2';
include __DIR__ . '/../src/layout/header.php';

echo '<div class="admin-container" style="padding: 20px;">';
echo "<h1>Migration der <code>comic_var.json</code> auf Schema-Version 2</h1>";

if (!file_exists($comicVarPath)) {
    echo '<p style="color: red;"><strong>Fehler:</strong> Die Datei <code>comic_var.json</code> wurde nicht gefunden.</p>';
} else {
    $content = file_get_contents($comicVarPath);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo '<p style="color: red;"><strong>Fehler:</strong> Die <code>comic_var.json</code> enthält ungültiges JSON und kann nicht verarbeitet werden.</p>';
    } elseif (isset($data['schema_version'])) {
        echo '<p style="color: blue;"><strong>Info:</strong> Die <code>comic_var.json</code> scheint bereits ein versioniertes Schema zu verwenden (Version ' . htmlspecialchars($data['schema_version']) . '). Keine Migration notwendig.</p>';
    } else {
        echo "<p>Die Datei wird nun migriert...</p>";

        // 1. Backup erstellen
        $backupPath = $comicVarPath . '.bak-' . date('Ymd-His');
        if (copy($comicVarPath, $backupPath)) {
            echo '<p style="color: green;"><strong>Schritt 1/2:</strong> Sicherungskopie erfolgreich erstellt unter <code>' . htmlspecialchars($backupPath) . '</code>.</p>';

            // 2. Neues Datenformat erstellen und speichern
            $newData = [
                'schema_version' => 2,
                'comics' => $data
            ];

            $newJson = json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if (file_put_contents($comicVarPath, $newJson)) {
                echo '<p style="color: green;"><strong>Schritt 2/2:</strong> Die <code>comic_var.json</code> wurde erfolgreich auf Schema-Version 2 aktualisiert.</p>';
                echo '<h2>Migration abgeschlossen!</h2>';
            } else {
                echo '<p style="color: red;"><strong>FEHLER bei Schritt 2/2:</strong> Die neue Datei konnte nicht geschrieben werden. Bitte überprüfe die Dateiberechtigungen.</p>';
            }
        } else {
            echo '<p style="color: red;"><strong>FEHLER bei Schritt 1/2:</strong> Die Sicherungskopie konnte nicht erstellt werden. Die Migration wurde abgebrochen.</p>';
        }
    }
}

echo '</div>';
include __DIR__ . '/../src/layout/footer.php';
?>
