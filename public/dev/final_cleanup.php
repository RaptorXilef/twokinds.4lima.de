<?php

declare(strict_types=1);

// https://twokinds.4lima.local/final_cleanup.php

\ini_set('display_errors', '1');
\error_reporting(\E_ALL);

echo "<div style='font-family: sans-serif; padding: 20px; background: #1e1e1e; color: #d4d4d4;'>";
echo '<h1>🧹 Finale Bereinigung: Kebab-Case & Ungenutzte Dateien</h1>';

$imgDir = \dirname(__DIR__) . '/public/assets/images';

function slugify(string $filename): string
{
    $info = \pathinfo($filename);
    $name = $info['filename'];
    $ext = isset($info['extension']) ? '.' . \strtolower($info['extension']) : '';

    // Umlaute umwandeln
    $name = \mb_strtolower($name, 'UTF-8');
    $name = \str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);
    // Sonderzeichen zu Bindestrichen
    $name = \preg_replace('/[^a-z0-9]+/', '-', $name);
    $name = \trim(\preg_replace('/-+/', '-', (string) $name), '-');

    return $name . $ext;
}

function exactRename(string $dir, string $oldName, string $newName): void
{
    if ($oldName === $newName) {
        return;
    }

    $oldPath = $dir . '/' . $oldName;
    $newPath = $dir . '/' . $newName;
    $tempPath = $dir . '/temp_' . \uniqid() . '.tmp';

    // Rename via temp file to bypass Windows case-insensitivity locks
    if (\rename($oldPath, $tempPath)) {
        \rename($tempPath, $newPath);
        echo "<span style='color:#ce9178;'>Umbenannt:</span> $oldName ➔ <b>$newName</b><br>";
    } else {
        echo "<span style='color:red;'>Fehler bei:</span> $oldName<br>";
    }
}

// 1. Kebab-Case für alle Charakter-Ordner erzwingen
$foldersToCheck = [
    "$imgDir/characters/profiles",
    "$imgDir/characters/portraits",
    "$imgDir/characters/palettes",
    "$imgDir/characters/refsheets",
];

foreach ($foldersToCheck as $folder) {
    if (!\is_dir($folder)) {
        continue;
    }
    echo '<h3>Prüfe Ordner: ' . \basename($folder) . '</h3>';
    $files = \array_diff(\scandir($folder), ['.', '..']);
    foreach ($files as $file) {
        $slug = \slugify($file);
        if ($file === $slug) {
            continue;
        }

        \exactRename($folder, $file, $slug);
    }
}

// 2. Ungenutzte Dateien & Ordner löschen
echo '<h3>🗑️ Lösche ungenutzte Altlasten</h3>';

$unusedFiles = [
    "$imgDir/system/403-hires.webp",
    "$imgDir/system/404-hires.webp",
];

foreach ($unusedFiles as $f) {
    if (!\is_file($f)) {
        continue;
    }

    \unlink($f);
    echo "<span style='color:#6a9955;'>Gelöscht:</span> " . \basename($f) . '<br>';
}

function delTree(string $dir): void
{
    if (!\is_dir($dir)) {
        return;
    }
    $files = \array_diff(\scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        \is_dir("$dir/$file") ? \delTree("$dir/$file") : @\unlink("$dir/$file");
    }
    @\rmdir($dir);
}

// Den nutzlosen avatars Ordner löschen
$avatarsDir = "$imgDir/characters/avatars";
if (\is_dir($avatarsDir)) {
    \delTree($avatarsDir);
    echo "<span style='color:#6a9955;'>Gelöscht (Komplett ungenutzt):</span> characters/avatars/ <br>";
}

echo "<h2 style='color:#569cd6;'>✅ Alle Dateinamen und Altlasten sind nun perfekt bereinigt!</h2>";
echo '</div>';
