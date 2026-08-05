<?php

declare(strict_types=1);

// https://twokinds.4lima.local/image_fixer.php

\ini_set('display_errors', '1');
\error_reporting(\E_ALL);

echo "<div style='font-family: sans-serif; padding: 20px; background: #1e1e1e; color: #d4d4d4;'>";
echo '<h1>🔨 Phase 1.5: Windows-Lock Fixer</h1>';

$imgDir = \dirname(__DIR__) . '/public/assets/images';

function slugify(string $filename, bool $rtrimSwatch = false): string
{
    $info = \pathinfo($filename);
    $name = $info['filename'];
    $ext  = isset($info['extension']) ? '.' . \strtolower($info['extension']) : '';

    $name = \mb_strtolower($name, 'UTF-8');
    $name = \str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);

    if ($rtrimSwatch && \str_ends_with($name, 'swatch')) {
        $name = \substr($name, 0, -6);
    }

    $name = \preg_replace('/[^a-z0-9]+/', '-', $name);
    $name = \trim(\preg_replace('/-+/', '-', (string) $name), '-');

    return $name . $ext;
}

function forceMove(string $src, string $dest): void
{
    if (! \file_exists($src)) {
        return;
    }
    if (! \is_dir(\dirname($dest))) {
        \mkdir(\dirname($dest), 0o755, true);
    }
    // Trick gegen Windows-Locks: Kopieren und altes löschen
    if ($src !== $dest) {
        if (\copy($src, $dest)) {
            @\unlink($src);
        }
    }
}

function delTree(string $dir): void
{
    if (! \is_dir($dir)) {
        return;
    }
    $files = \array_diff(\scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        \is_dir("$dir/$file") ? \delTree("$dir/$file") : @\unlink("$dir/$file");
    }
    @\rmdir($dir);
}

// 1. Profiles bereinigen (in-place)
echo '<p>Verarbeite Profiles...</p>';
if (\is_dir("$imgDir/characters/profiles")) {
    foreach (\scandir("$imgDir/characters/profiles") as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        \forceMove("$imgDir/characters/profiles/$f", "$imgDir/characters/profiles/" . \slugify($f));
    }
}

// 2. Main -> Portraits verschieben
echo '<p>Verarbeite Portraits (Ehemals Main)...</p>';
if (\is_dir("$imgDir/characters/main")) {
    foreach (\scandir("$imgDir/characters/main") as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        \forceMove("$imgDir/characters/main/$f", "$imgDir/characters/portraits/" . \slugify($f));
    }
}

// 3. Swatches -> Palettes verschieben
echo '<p>Verarbeite Palettes (Ehemals Swatches)...</p>';
if (\is_dir("$imgDir/characters/swatches")) {
    foreach (\scandir("$imgDir/characters/swatches") as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        \forceMove("$imgDir/characters/swatches/$f", "$imgDir/characters/palettes/" . \slugify($f, true));
    }
}

// 4. Refsheets bereinigen (in-place)
echo '<p>Verarbeite Refsheets...</p>';
if (\is_dir("$imgDir/characters/refsheets")) {
    foreach (\scandir("$imgDir/characters/refsheets") as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        \forceMove("$imgDir/characters/refsheets/$f", "$imgDir/characters/refsheets/" . \slugify($f));
    }
}

// 5. Hard-Cleanup der alten Windows-Gelees
echo '<p>Lösche alte Ordner...</p>';
\delTree("$imgDir/characters/main");
\delTree("$imgDir/characters/swatches");
\delTree("$imgDir/characters/faces");
\delTree("$imgDir/layout");
@\unlink("$imgDir/admin/ui/loading.webp");
\delTree("$imgDir/admin/ui");
\delTree("$imgDir/admin");
\delTree("$imgDir/comic"); // Falls noch Reste vom alten Comic-Ordner da sind

echo "<h2 style='color:#569cd6;'>✅ Fix erfolgreich!</h2>";
echo '</div>';
