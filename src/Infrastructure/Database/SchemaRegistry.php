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
            // --- DAS NEUE BENUTZERSYSTEM ---
            'roles' => 'CREATE TABLE IF NOT EXISTS `roles` (
                `id` VARCHAR(50) PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `permissions` JSON
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'users' => 'CREATE TABLE IF NOT EXISTS `users` (
                `id` VARCHAR(50) PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `role_id` VARCHAR(50) NOT NULL DEFAULT \'user\',
                `wants_newsletter` TINYINT(1) NOT NULL DEFAULT 0,
                `wants_newsletter_transcript` TINYINT(1) NOT NULL DEFAULT 0,
                `wants_notification_report` TINYINT(1) NOT NULL DEFAULT 0,
                `avatar_url` VARCHAR(255) DEFAULT NULL,
                `bio` TEXT DEFAULT NULL,
                `social_links` JSON DEFAULT NULL,
                `public_bookmarks` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                UNIQUE KEY `idx_username` (`username`),
                UNIQUE KEY `idx_email` (`email`),
                INDEX `idx_role` (`role_id`),
                INDEX `idx_newsletter` (`wants_newsletter`),
                INDEX `idx_newsletter_transcript` (`wants_newsletter_transcript`),
                INDEX `idx_notification_report` (`wants_notification_report`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'user_bookmarks' => 'CREATE TABLE IF NOT EXISTS `user_bookmarks` (
                `user_id` VARCHAR(50) NOT NULL,
                `comic_id` VARCHAR(8) NOT NULL,
                `added_at` DATETIME NOT NULL,
                PRIMARY KEY (`user_id`, `comic_id`),
                INDEX `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            // --- SICHERHEIT ---
            'login_attempts' => 'CREATE TABLE IF NOT EXISTS `login_attempts` (
                `ip_address` VARCHAR(45) PRIMARY KEY,
                `attempts` INT NOT NULL DEFAULT 1,
                `last_attempt` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            // --- TWOKINDS CORE ---
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
                `user_ids` JSON DEFAULT NULL,
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
                `full_name` VARCHAR(255),
                `pic_url` VARCHAR(255),
                `description` TEXT,
                `alt_names` VARCHAR(255),
                `gender` VARCHAR(100),
                `age` VARCHAR(100),
                `rank` VARCHAR(100),
                `species` VARCHAR(100),
                `subspecies` VARCHAR(100),
                `languages` VARCHAR(255),
                `main_pic` VARCHAR(255),
                `swatch_pic` VARCHAR(255),
                `ref_sheets` JSON
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'character_groups' => 'CREATE TABLE IF NOT EXISTS `character_groups` (
                `name` VARCHAR(100) PRIMARY KEY,
                `character_ids` JSON,
                `sort_order` INT NOT NULL DEFAULT 0,
                `manual_sort` TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            // Domain: Reports
            'reports' => 'CREATE TABLE IF NOT EXISTS `reports` (
                `id` VARCHAR(50) PRIMARY KEY,
                `comic_id` VARCHAR(8) DEFAULT NULL,
                `user_id` VARCHAR(50) DEFAULT NULL,
                `date` DATETIME NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT \'open\',
                `ip_hash` VARCHAR(64) NOT NULL,
                `submitter_name` VARCHAR(255) NOT NULL,
                `wants_credit` TINYINT(1) NOT NULL DEFAULT 0,
                `type` VARCHAR(50) NOT NULL,
                `screenshot_url` VARCHAR(255),
                `description` TEXT,
                `transcript_suggestion` TEXT,
                `transcript_original` TEXT,
                `debug_info` TEXT,
                INDEX `idx_status` (`status`),
                INDEX `idx_comic` (`comic_id`),
                INDEX `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'magic_links' => 'CREATE TABLE IF NOT EXISTS `magic_links` (
                `token` VARCHAR(64) PRIMARY KEY,
                `email` VARCHAR(255) NOT NULL,
                `code` VARCHAR(10),
                `expires` DATETIME,
                INDEX `idx_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'mail_queue' => 'CREATE TABLE IF NOT EXISTS `mail_queue` (
                `id` VARCHAR(50) PRIMARY KEY,
                `recipient` VARCHAR(255) NOT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `template` VARCHAR(100) NOT NULL,
                `data` JSON NOT NULL,
                `attempts` INT DEFAULT 0,
                `priority` INT NOT NULL DEFAULT 10,
                `created_at` DATETIME NOT NULL,
                INDEX `idx_priority` (`priority`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',

            'mail_logs' => 'CREATE TABLE IF NOT EXISTS `mail_logs` (
                `id` VARCHAR(50) PRIMARY KEY,
                `timestamp` DATETIME NOT NULL,
                `recipient` VARCHAR(255) NOT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `template` VARCHAR(100) NOT NULL,
                `status` TEXT,
                `data` JSON,
                INDEX `idx_recipient` (`recipient`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
        ];
    }
}
