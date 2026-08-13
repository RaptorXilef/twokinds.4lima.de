ALTER TABLE `characters` ADD COLUMN `keidran_age` VARCHAR(255) DEFAULT NULL AFTER `age`;

ALTER TABLE `characters` ADD COLUMN `is_dead` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fur_color`;
