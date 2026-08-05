<?php

declare(strict_types=1);

// https://twokinds.4lima.local/migrate.php?test=1

// Fehleranzeige aktivieren, damit wir sofort sehen, falls etwas hakt
\ini_set('display_errors', '1');
\error_reporting(\E_ALL);

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo '<h1>🚀 Twokinds JSON zu MySQL Migration</h1>';

// 1. Datenbank-Konfiguration laden (Aus storage.php)
$baseDir = \dirname(__DIR__);
// $configFile = $baseDir . '/config/storage.php';
$configFile = $baseDir . '/config/config.local.php';

if (! \file_exists($configFile)) {
    exit("<b style='color:red;'>Fehler:</b> storage.php nicht gefunden (Erwartet in: {$configFile}).");
}
$config = require $configFile;
$db     = $config['database'];

try {
    $pdo = new \PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['user'],
        $db['pass'],
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

foreach ($paths as $path) {
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
        // Ignoriere den Platzhalter-Eintrag ohne ID
        if (empty($chapter['chapterId'])) {
            continue;
        }
        $stmtChapter->execute([
            ':id'          => (string) $chapter['chapterId'],
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
                ':character_ids' => \json_encode(\array_values($idsArray)),
            ]);
            ++$groupCount;
        }
    }
    echo "<p>✓ <b>{$groupCount}</b> Charakter-Gruppen importiert.</p>";

    // ==========================================
    // D) COMICS MIGRATION (Inkl. Character JSON)
    // ==========================================
    $comicData = \json_decode(\file_get_contents($paths['comics']), true);

    // HIER ANGEPASST: character_ids ist nun eine JSON Spalte in der Comics-Tabelle!
    $stmtComic = $pdo->prepare('
        INSERT INTO `comics` (`id`, `type`, `name`, `transcript`, `chapter_id`, `character_ids`, `original_url`, `sketch_url`)
        VALUES (:id, :type, :name, :transcript, :chapter_id, :character_ids, :original_url, :sketch_url)
        ON DUPLICATE KEY UPDATE
            `type` = :type, `name` = :name, `transcript` = :transcript,
            `chapter_id` = :chapter_id, `character_ids` = :character_ids, `original_url` = :original_url, `sketch_url` = :sketch_url
    ');

    $comicCount = 0;

    if (isset($comicData['comics'])) {
        foreach ($comicData['comics'] as $comicId => $comic) {

            // Verknüpfte Charaktere als JSON formatieren
            $charIdsJson = '[]';
            if (isset($comic['charaktere']) && \is_array($comic['charaktere'])) {
                $charIdsJson = \json_encode(\array_values($comic['charaktere']));
            }

            // Sicherstellen, dass chapter_id nicht leer ist
            $chapterId = isset($comic['chapter']) && $comic['chapter'] !== '' ? (string) $comic['chapter'] : null;

            $stmtComic->execute([
                ':id'            => $comicId,
                ':type'          => $comic['type'] ?? 'Comicseite',
                ':name'          => $comic['name'] ?? '',
                ':transcript'    => $comic['transcript'] ?? '',
                ':chapter_id'    => $chapterId,
                ':character_ids' => $charIdsJson,
                ':original_url'  => $comic['url_originalbild'] ?? '',
                ':sketch_url'    => $comic['url_originalsketch'] ?? '',
            ]);
            ++$comicCount;
        }
    }
    echo "<p>✓ <b>{$comicCount}</b> Comics erfolgreich importiert.</p>";

    // Wenn bis hierhin kein Fehler aufgetreten ist: In die Datenbank schreiben!
    $pdo->commit();
    echo "<h2 style='color:green;'>🎉 Migration komplett abgeschlossen!</h2>";
    echo '<p>Alle Daten sind nun sicher in der MySQL Datenbank. Du kannst die Datei <code>migrate.php</code> und den Ordner <code>migration_data</code> jetzt löschen.</p>';

} catch (\Exception $e) {
    // Bei einem Fehler machen wir alles rückgängig, damit die DB nicht halb-befüllt bleibt
    $pdo->rollBack();
    exit("<h2 style='color:red;'>🔥 Migration fehlgeschlagen!</h2><p>Fehler-Details: " . $e->getMessage() . '</p><p>In Zeile: ' . $e->getLine() . '</p>');
}

echo '</div>';
