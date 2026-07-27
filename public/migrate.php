<?php

declare(strict_types=1);

// https://twokinds.4lima.local/migrate.php

// Fehleranzeige aktivieren, damit wir sofort sehen, falls etwas hakt
\ini_set('display_errors', '1');
\error_reporting(\E_ALL);

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo '<h1>🚀 Twokinds JSON zu MySQL Migration</h1>';

// 1. Datenbank-Konfiguration laden (Pfad geht aus dem public-Ordner hoch in config)
$baseDir    = \dirname(__DIR__);
$configFile = $baseDir . '/config/config.php';

if (! \file_exists($configFile)) {
    exit("<b style='color:red;'>Fehler:</b> config.php nicht gefunden (Erwartet in: {$configFile}).");
}
$config = require $configFile;

try {
    $pdo = new \PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
    );
    echo "<p style='color:green;'>✓ Datenbankverbindung erfolgreich hergestellt.</p>";
} catch (\PDOException $e) {
    exit("<b style='color:red;'>Datenbank-Fehler:</b> " . $e->getMessage());
}

// 2. Pfade zu den JSON-Dateien
$paths = [
    'chapters'   => __DIR__ . '/migration_data/archive_chapters.json',
    'characters' => __DIR__ . '/migration_data/charaktere.json',
    'comics'     => __DIR__ . '/migration_data/comic_var.json',
];

foreach ($paths as $name => $path) {
    if (! \file_exists($path)) {
        exit("<b style='color:red;'>Fehler:</b> Die Datei <code>{$path}</code> wurde nicht gefunden!");
    }
}

// 3. MIGRATION STARTEN (In einer Transaktion für maximale Sicherheit und Geschwindigkeit)
$pdo->beginTransaction();

try {
    // ==========================================
    // A) KAPITEL MIGRATION
    // ==========================================
    $chaptersData = \json_decode(\file_get_contents($paths['chapters']), true);
    $stmtChapter  = $pdo->prepare('
        INSERT INTO `chapters` (`id`, `title`, `description`)
        VALUES (:id, :title, :description)
        ON DUPLICATE KEY UPDATE `title` = :title, `description` = :description
    ');

    $chapterCount = 0;
    foreach ($chaptersData as $chapter) {
        $stmtChapter->execute([
            ':id'          => $chapter['chapterId'],
            ':title'       => $chapter['title'],
            ':description' => $chapter['description'] ?? '',
        ]);
        ++$chapterCount;
    }
    echo "<p>✓ <b>{$chapterCount}</b> Kapitel importiert.</p>";

    // ==========================================
    // B) CHARAKTERE MIGRATION
    // ==========================================
    $charData = \json_decode(\file_get_contents($paths['characters']), true);
    $stmtChar = $pdo->prepare('
        INSERT INTO `characters` (`id`, `name`, `pic_url`, `description`)
        VALUES (:id, :name, :pic_url, :description)
        ON DUPLICATE KEY UPDATE `name` = :name, `pic_url` = :pic_url, `description` = :description
    ');

    $charCount = 0;
    if (isset($charData['characters'])) {
        foreach ($charData['characters'] as $charId => $char) {
            $stmtChar->execute([
                ':id'          => $charId,
                ':name'        => $char['name'],
                ':pic_url'     => $char['pic_url'] ?? null,
                ':description' => $char['description'] ?? null,
            ]);
            ++$charCount;
        }
    }
    echo "<p>✓ <b>{$charCount}</b> Charaktere importiert.</p>";

    // ==========================================
    // C) CHARAKTER GRUPPEN MIGRATION
    // ==========================================
    $stmtGroup = $pdo->prepare('
        INSERT INTO `character_groups` (`name`, `character_ids`)
        VALUES (:name, :character_ids)
        ON DUPLICATE KEY UPDATE `character_ids` = :character_ids
    ');

    $groupCount = 0;
    if (isset($charData['groups'])) {
        foreach ($charData['groups'] as $groupName => $idsArray) {
            $stmtGroup->execute([
                ':name'          => $groupName,
                ':character_ids' => \json_encode($idsArray),
            ]);
            ++$groupCount;
        }
    }
    echo "<p>✓ <b>{$groupCount}</b> Charakter-Gruppen importiert.</p>";

    // ==========================================
    // D) COMICS & VERKNÜPFUNGEN MIGRATION
    // ==========================================
    $comicData = \json_decode(\file_get_contents($paths['comics']), true);
    $stmtComic = $pdo->prepare('
        INSERT INTO `comics` (`id`, `type`, `name`, `transcript`, `chapter_id`, `original_url`, `sketch_url`)
        VALUES (:id, :type, :name, :transcript, :chapter_id, :original_url, :sketch_url)
        ON DUPLICATE KEY UPDATE
            `type` = :type, `name` = :name, `transcript` = :transcript,
            `chapter_id` = :chapter_id, `original_url` = :original_url, `sketch_url` = :sketch_url
    ');

    $stmtLink = $pdo->prepare('
        INSERT IGNORE INTO `comic_characters` (`comic_id`, `character_id`)
        VALUES (:comic_id, :character_id)
    ');

    $comicCount = 0;
    $linkCount  = 0;

    if (isset($comicData['comics'])) {
        foreach ($comicData['comics'] as $comicId => $comic) {
            $stmtComic->execute([
                ':id'           => $comicId,
                ':type'         => $comic['type'] ?? 'Comicseite',
                ':name'         => $comic['name'] ?? '',
                ':transcript'   => $comic['transcript'] ?? '',
                ':chapter_id'   => $comic['chapter'] ?? '',
                ':original_url' => $comic['url_originalbild'] ?? '',
                ':sketch_url'   => $comic['url_originalsketch'] ?? '',
            ]);
            ++$comicCount;

            if (isset($comic['charaktere']) && \is_array($comic['charaktere'])) {
                foreach ($comic['charaktere'] as $cId) {
                    $stmtLink->execute([
                        ':comic_id'     => $comicId,
                        ':character_id' => $cId,
                    ]);
                    ++$linkCount;
                }
            }
        }
    }
    echo "<p>✓ <b>{$comicCount}</b> Comics und <b>{$linkCount}</b> Charakter-Zuweisungen importiert.</p>";

    $pdo->commit();
    echo "<h2 style='color:green;'>🎉 Migration komplett abgeschlossen!</h2>";
    echo '<p>Bitte lösche die Datei <code>migrate.php</code> und den Ordner <code>migration_data</code> jetzt wieder von deinem Server, um die Sicherheit zu gewährleisten.</p>';

} catch (\Exception $e) {
    $pdo->rollBack();
    exit("<h2 style='color:red;'>🔥 Migration fehlgeschlagen!</h2><p>Fehler-Details: " . $e->getMessage() . '</p>');
}

echo '</div>';
