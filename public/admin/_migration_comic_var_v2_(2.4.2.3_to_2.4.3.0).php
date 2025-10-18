<?php
/**
 * Migrationsskript, um comic_var.json auf Schema-Version 2 zu aktualisieren.
 *
 * @file      ROOT/public/admin/_migration_comic_var_v2.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.0.0
 * @since     1.1.0 Umstellung auf Konstanten, Entfernung von Inline-Styles und Code-Bereinigung.
 * @since     2.0.0 Vollständige Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

$comicVarJsonPath = Path::getData('comic_var.json');
$comicVarJsonFile = basename($comicVarJsonPath);

$pageTitle = 'Migration: ' . $comicVarJsonFile . ' zu v2';
require_once Path::getTemplatePartial('header.php');
?>

<article>
    <div class="content-section">
        <style nonce="<?php echo htmlspecialchars($nonce); ?>">
            .status-message {
                margin-bottom: 15px;
                padding: 10px;
                border-radius: 5px;
                font-weight: bold;
            }

            .status-red {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .status-green {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .status-info {
                background-color: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
            }
        </style>

        <h1>Migration der <code><?php echo htmlspecialchars($comicVarJsonFile); ?></code> auf Schema-Version 2</h1>

        <?php
        if (!file_exists($comicVarJsonPath)) {
            echo '<p class="status-message status-red"><strong>Fehler:</strong> Die Datei <code>' . htmlspecialchars($comicVarJsonFile) . '</code> wurde nicht gefunden.</p>';
        } else {
            $content = file_get_contents($comicVarJsonPath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                echo '<p class="status-message status-red"><strong>Fehler:</strong> Die <code>' . htmlspecialchars($comicVarJsonFile) . '</code> enthält ungültiges JSON und kann nicht verarbeitet werden.</p>';
            } elseif (isset($data['schema_version'])) {
                echo '<p class="status-message status-info"><strong>Info:</strong> Die <code>' . htmlspecialchars($comicVarJsonFile) . '</code> scheint bereits ein versioniertes Schema zu verwenden (Version ' . htmlspecialchars($data['schema_version']) . '). Keine Migration notwendig.</p>';
            } else {
                echo "<p>Die Datei wird nun migriert...</p>";

                // 1. Backup erstellen
                $backupPath = $comicVarJsonPath . '.bak-' . date('Ymd-His');
                if (copy($comicVarJsonPath, $backupPath)) {
                    echo '<p class="status-message status-green"><strong>Schritt 1/2:</strong> Sicherungskopie erfolgreich erstellt unter <code>' . htmlspecialchars($backupPath) . '</code>.</p>';

                    // 2. Neues Datenformat erstellen und speichern
                    $newData = [
                        'schema_version' => 2,
                        'comics' => $data
                    ];

                    $newJson = json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    if (file_put_contents($comicVarJsonPath, $newJson)) {
                        echo '<p class="status-message status-green"><strong>Schritt 2/2:</strong> Die <code>' . htmlspecialchars($comicVarJsonFile) . '</code> wurde erfolgreich auf Schema-Version 2 aktualisiert.</p>';
                        echo '<h2>Migration abgeschlossen!</h2>';
                    } else {
                        echo '<p class="status-message status-red"><strong>FEHLER bei Schritt 2/2:</strong> Die neue Datei konnte nicht geschrieben werden. Bitte überprüfe die Dateiberechtigungen.</p>';
                    }
                } else {
                    echo '<p class="status-message status-red"><strong>FEHLER bei Schritt 1/2:</strong> Die Sicherungskopie konnte nicht erstellt werden. Die Migration wurde abgebrochen.</p>';
                }
            }
        }
        ?>
    </div>
</article>

<?php require_once Path::getTemplatePartial('footer.php'); ?>