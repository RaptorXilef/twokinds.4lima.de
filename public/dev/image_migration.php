<?php

declare(strict_types=1);

// phpcs:ignoreFile

// https://twokinds.4lima.local/image_migration.php

\ini_set('display_errors', '1');
\error_reporting(\E_ALL);

echo "<div style='font-family: sans-serif; padding: 20px; background: #1e1e1e; color: #d4d4d4;'>";
echo '<h1>🚀 Phase 1: Asset Reorganisation & DB Migration</h1>';

$baseDir = \dirname(__DIR__);
$configFiles = [
    $baseDir . '/config/config.local.php',
    $baseDir . '/config/storage.php',
];

$dbConfig = null;
foreach ($configFiles as $file) {
    if (!\file_exists($file)) {
        continue;
    }

    $cfg = require $file;
    if (isset($cfg['database'])) {
        $dbConfig = $cfg['database'];

        break;
    }
}

if (!$dbConfig) {
    exit("<b style='color:red;'>Fehler:</b> Datenbank-Konfiguration nicht gefunden.");
}

try {
    $pdo = new \PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
        $dbConfig['user'],
        $dbConfig['pass'],
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
    );
    echo "<p style='color:#6a9955;'>✓ Datenbankverbindung erfolgreich hergestellt.</p>";
} catch (\PDOException $e) {
    exit("<b style='color:red;'>Datenbank-Fehler:</b> " . $e->getMessage());
}

$imgDir = $baseDir . '/public/assets/images';

// Hilfsfunktion zum sauberen Umbenennen in Kebab-Case
function slugify(string $filename, bool $ltrimIcon = false, bool $rtrimSwatch = false): string
{
    $info = \pathinfo($filename);
    $name = $info['filename'];
    $ext = isset($info['extension']) ? '.' . \strtolower($info['extension']) : '';

    // Umlaute und Sonderfälle
    $name = \mb_strtolower($name, 'UTF-8');
    $name = \str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);

    if ($ltrimIcon && \str_starts_with($name, 'icon_')) {
        $name = \substr($name, 5);
    }
    if ($rtrimSwatch && \str_ends_with($name, 'swatch')) {
        $name = \substr($name, 0, -6);
    }

    // Altes Navigations-Präfix "comic-nav-" entfernen
    $name = \str_replace('comic-nav-', '', $name);

    // Alles, was kein Buchstabe/Zahl ist, durch Bindestriche ersetzen
    $name = \preg_replace('/[^a-z0-9]+/', '-', $name);
    // Doppelte Bindestriche entfernen und an den Rändern trimmen
    $name = \trim(\preg_replace('/-+/', '-', (string) $name), '-');

    return $name . $ext;
}

// Hilfsfunktion zum sicheren Verschieben
function safeMove(string $oldPath, string $newPath): void
{
    if (!\file_exists($oldPath)) {
        return;
    }

    $dir = \dirname($newPath);
    if (!\is_dir($dir)) {
        \mkdir($dir, 0o755, true);
    }
    \rename($oldPath, $newPath);
}

// --- 1. NEUE ORDNERSTRUKTUR ANLEGEN ---
$newDirs = [
    "$imgDir/characters/avatars",
    "$imgDir/characters/palettes",
    "$imgDir/characters/portraits",
    "$imgDir/characters/profiles",
    "$imgDir/characters/refsheets",
    "$imgDir/comics/hires",
    "$imgDir/comics/lowres",
    "$imgDir/comics/social",
    "$imgDir/comics/thumbnails",
    "$imgDir/system",
    "$imgDir/theme/banners",
    "$imgDir/theme/footer-chars",
    "$imgDir/theme/icons",
    "$imgDir/theme/nav",
];
foreach ($newDirs as $dir) {
    if (\is_dir($dir)) {
        continue;
    }

    \mkdir($dir, 0o755, true);
}

// --- 2. DATENBANK CHARAKTERE MIGRATION ---
echo '<h3>🔄 Verarbeite Charaktere (Datenbank & Dateien)</h3>';
$pdo->beginTransaction();

try {
    $stmt = $pdo->query('SELECT id, pic_url, main_pic, swatch_pic, ref_sheets FROM characters');
    $chars = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare('UPDATE characters SET pic_url = ?, main_pic = ?, swatch_pic = ?, ref_sheets = ? WHERE id = ?');

    $count = 0;
    foreach ($chars as $char) {
        $newPicUrl = null;
        $newMainPic = null;
        $newSwatchPic = null;
        $newRefSheets = [];

        // Profilbild
        if (!empty($char['pic_url'])) {
            $newPicUrl = \slugify($char['pic_url']);
            \safeMove("$imgDir/characters/profiles/{$char['pic_url']}", "$imgDir/characters/profiles/$newPicUrl");
        }

        // Main / Portraits
        if (!empty($char['main_pic'])) {
            $newMainPic = \slugify($char['main_pic']);
            \safeMove("$imgDir/characters/main/{$char['main_pic']}", "$imgDir/characters/portraits/$newMainPic");
        }

        // Swatches / Palettes
        if (!empty($char['swatch_pic'])) {
            $newSwatchPic = \slugify($char['swatch_pic'], false, true);
            \safeMove("$imgDir/characters/swatches/{$char['swatch_pic']}", "$imgDir/characters/palettes/$newSwatchPic");
        }

        // Refsheets
        $refs = \json_decode($char['ref_sheets'] ?? '[]', true);
        if (\is_array($refs)) {
            foreach ($refs as $ref) {
                $newRef = \slugify($ref);
                \safeMove("$imgDir/characters/refsheets/$ref", "$imgDir/characters/refsheets/$newRef");
                $newRefSheets[] = $newRef;
            }
        }

        $updateStmt->execute([
            $newPicUrl,
            $newMainPic,
            $newSwatchPic,
            \json_encode($newRefSheets, \JSON_UNESCAPED_UNICODE),
            $char['id'],
        ]);
        ++$count;
    }
    $pdo->commit();
    echo "<p style='color:#6a9955;'>✓ $count Charaktere in der Datenbank aktualisiert und Bilder verschoben.</p>";
} catch (\Exception $e) {
    $pdo->rollBack();
    exit("<b style='color:red;'>Migration abgebrochen (DB Fehler):</b> " . $e->getMessage());
}

// --- 3. AVATARE (Faces -> Avatars) ---
echo '<h3>🔄 Verarbeite Avatare (Faces)</h3>';
$facesDir = "$imgDir/characters/faces";
if (\is_dir($facesDir)) {
    $files = \array_diff(\scandir($facesDir), ['.', '..']);
    foreach ($files as $file) {
        $newName = \slugify($file, true, false); // true für ltrimIcon
        \safeMove("$facesDir/$file", "$imgDir/characters/avatars/$newName");
    }
    echo "<p style='color:#6a9955;'>✓ Avatare umbenannt und verschoben.</p>";
}

// --- 4. COMICS ---
echo '<h3>🔄 Verarbeite Comics (Ordner umbenennen)</h3>';
if (\is_dir("$imgDir/comic")) {
    // Wir verschieben die Ordner direkt
    \safeMove("$imgDir/comic/hires", "$imgDir/comics/hires");
    \safeMove("$imgDir/comic/lowres", "$imgDir/comics/lowres");
    \safeMove("$imgDir/comic/thumbnails", "$imgDir/comics/thumbnails");
    \safeMove("$imgDir/comic/socialmedia", "$imgDir/comics/social");
    echo "<p style='color:#6a9955;'>✓ Comic Ordner auf Plural 'comics' und 'social' geändert.</p>";
}

// --- 5. SYSTEM & THEME BILDER ---
echo '<h3>🔄 Verarbeite System & Theme Bilder</h3>';

// Systemdateien
\safeMove("$imgDir/layout/lowres/404.webp", "$imgDir/system/404.webp");
\safeMove("$imgDir/layout/lowres/403.webp", "$imgDir/system/403.webp");
\safeMove("$imgDir/layout/lowres/in_translation.webp", "$imgDir/system/in-translation.webp");
\safeMove("$imgDir/layout/hires/in_translation.webp", "$imgDir/system/in-translation-hires.webp");
\safeMove("$imgDir/layout/thumbnails/placeholder.webp", "$imgDir/system/placeholder.webp");

// Theme - Banners
\safeMove("$imgDir/layout/banner_day.webp", "$imgDir/theme/banners/day.webp");
\safeMove("$imgDir/layout/banner_night.webp", "$imgDir/theme/banners/night.webp");

// Theme - Footer
$footerDir = "$imgDir/layout/footer";
if (\is_dir($footerDir)) {
    $files = \array_diff(\scandir($footerDir), ['.', '..']);
    foreach ($files as $file) {
        $newName = \slugify($file);
        \safeMove("$footerDir/$file", "$imgDir/theme/footer-chars/$newName");
    }
}

// Theme - Icons
$uiDir = "$imgDir/layout/ui";
if (\is_dir($uiDir)) {
    $files = \array_diff(\scandir($uiDir), ['.', '..']);
    foreach ($files as $file) {
        $newName = \slugify($file);
        \safeMove("$uiDir/$file", "$imgDir/theme/icons/$newName");
    }
}

// Theme - Nav (comic-nav- wird im slugify() entfernt)
$navDir = "$imgDir/layout/navigation";
if (\is_dir($navDir)) {
    $files = \array_diff(\scandir($navDir), ['.', '..']);
    foreach ($files as $file) {
        $newName = \slugify($file);
        \safeMove("$navDir/$file", "$imgDir/theme/nav/$newName");
    }
}

// About (Low-Risk Kebab-Case)
$aboutDir = "$imgDir/about";
if (\is_dir($aboutDir)) {
    $files = \array_diff(\scandir($aboutDir), ['.', '..']);
    foreach ($files as $file) {
        $newName = \slugify($file);
        \safeMove("$aboutDir/$file", "$imgDir/about/$newName");
    }
}

echo "<p style='color:#6a9955;'>✓ UI, Layout und Systemdateien wurden sortiert und umbenannt.</p>";

// --- 6. CLEANUP ---
echo '<h3>🧹 Lösche alte, leere Ordner</h3>';
$foldersToDelete = [
    "$imgDir/characters/faces",
    "$imgDir/characters/main",
    "$imgDir/characters/swatches",
    "$imgDir/comic", // War das alte Verzeichnis
    "$imgDir/layout/footer",
    "$imgDir/layout/hires",
    "$imgDir/layout/lowres",
    "$imgDir/layout/navigation",
    "$imgDir/layout/thumbnails",
    "$imgDir/layout/ui",
    "$imgDir/layout",
    "$imgDir/admin/ui/loading.webp", // Veraltete Ladedatei löschen
    "$imgDir/admin/ui",
    "$imgDir/admin",
];

foreach ($foldersToDelete as $f) {
    if (\is_file($f)) {
        @\unlink($f);
    } elseif (\is_dir($f)) {
        @\rmdir($f); // Löscht nur, wenn Ordner leer ist
    }
}

echo "<p style='color:#6a9955;'>✓ Alte Ordner aufgeräumt.</p>";
echo "<h2 style='color:#569cd6;'>🎉 Migration abgeschlossen!</h2>";
echo '<p>Dateisystem und die Datenbank sind nun im neuen Zustand.</p>';
echo '</div>';
