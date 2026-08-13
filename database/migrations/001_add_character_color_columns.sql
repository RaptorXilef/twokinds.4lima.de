ALTER TABLE `characters` ADD COLUMN `hair_color` VARCHAR(255) DEFAULT NULL AFTER `languages`;

ALTER TABLE `characters` ADD COLUMN `eye_color` VARCHAR(255) DEFAULT NULL AFTER `hair_color`;

ALTER TABLE `characters` ADD COLUMN `fur_color` VARCHAR(255) DEFAULT NULL AFTER `eye_color`;
