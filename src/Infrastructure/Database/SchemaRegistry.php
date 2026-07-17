<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

/**
 * TODO mysql schema
 * TODO sql schema anpassen
 */
final class SchemaRegistry
{
    public static function getSchemas(): array
    {
        return [
            // Admin & Security
            'admin_users' => 'CREATE TABLE IF NOT EXISTS `admin_users` (
                `username` VARCHAR(50) PRIMARY KEY,
                `password_hash` VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'login_attempts' => 'CREATE TABLE IF NOT EXISTS `login_attempts` (
                `ip_address` VARCHAR(45) PRIMARY KEY,
                `attempts` INT NOT NULL DEFAULT 1,
                `last_attempt` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            // Domain: Comics & Chapters
            'chapters' => 'CREATE TABLE IF NOT EXISTS `chapters` (
                `id` VARCHAR(50) PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `description` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'comics' => 'CREATE TABLE IF NOT EXISTS `comics` (
                `id` VARCHAR(8) PRIMARY KEY,
                `type` VARCHAR(50) NOT NULL DEFAULT \'Comicseite\',
                `name` VARCHAR(255) NOT NULL,
                `transcript` TEXT,
                `chapter_id` VARCHAR(50),
                `character_ids` JSON,
                `original_url` VARCHAR(255),
                `sketch_url` VARCHAR(255),
                `image_updated_at` INT NULL,
                INDEX `idx_chapter` (`chapter_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'comic_revisions' => 'CREATE TABLE IF NOT EXISTS `comic_revisions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `comic_id` VARCHAR(8) NOT NULL,
                `revision_data` JSON NOT NULL,
                `created_at` DATETIME NOT NULL,
                INDEX `idx_comic` (`comic_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            // Domain: Characters
            'characters' => 'CREATE TABLE IF NOT EXISTS `characters` (
                `id` VARCHAR(20) PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `pic_url` VARCHAR(255),
                `description` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'character_groups' => 'CREATE TABLE IF NOT EXISTS `character_groups` (
                `name` VARCHAR(100) PRIMARY KEY,
                `character_ids` JSON
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            // Domain: Reports
            'reports' => 'CREATE TABLE IF NOT EXISTS `reports` (
                `id` VARCHAR(50) PRIMARY KEY,
                `comic_id` VARCHAR(8) NOT NULL,
                `date` DATETIME NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT \'open\',
                `ip_hash` VARCHAR(64) NOT NULL,
                `submitter_name` VARCHAR(255) NOT NULL,
                `type` VARCHAR(50) NOT NULL,
                `description` TEXT,
                `transcript_suggestion` TEXT,
                `transcript_original` TEXT,
                `debug_info` TEXT,
                INDEX `idx_status` (`status`),
                INDEX `idx_comic` (`comic_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
        ];
    }
}
