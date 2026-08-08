<?php

declare(strict_types=1);

// https://.../dev/db_update.php

require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';
$container = require \dirname(__DIR__, 2) . '/src/Bootstrap/app.php';
$pdo = $container->get(\PDO::class);

echo "<pre>Führe Datenbank-Update aus...\n\n";

try {
    // 1. Users Tabelle erweitern
    $pdo->exec('ALTER TABLE `users`
        ADD COLUMN `avatar_url` VARCHAR(255) DEFAULT NULL AFTER `wants_notification_report`,
        ADD COLUMN `bio` TEXT DEFAULT NULL AFTER `avatar_url`,
        ADD COLUMN `social_links` JSON DEFAULT NULL AFTER `bio`,
        ADD COLUMN `public_bookmarks` TINYINT(1) NOT NULL DEFAULT 0 AFTER `social_links`
    ');
    echo "✅ Tabelle 'users' erfolgreich erweitert.\n";
} catch (\PDOException $e) {
    echo "⚠️ Fehler bei 'users' (vielleicht schon vorhanden?): " . $e->getMessage() . "\n";
}

try {
    // 2. Comics Tabelle erweitern
    $pdo->exec('ALTER TABLE `comics` ADD COLUMN `helper_ids` JSON DEFAULT NULL AFTER `character_ids`');
    echo "✅ Tabelle 'comics' erfolgreich erweitert.\n";
} catch (\PDOException $e) {
    echo "⚠️ Fehler bei 'comics': " . $e->getMessage() . "\n";
}

echo "\nUpdate abgeschlossen!</pre>";
