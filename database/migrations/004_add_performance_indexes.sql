CREATE INDEX `idx_name`
ON `characters` (`name`);

CREATE INDEX `idx_last_attempt`
ON `login_attempts` (`last_attempt`);

CREATE INDEX `idx_created_at`
ON `comic_revisions` (`created_at`);

CREATE INDEX `idx_ip_date`
ON `reports` (`ip_hash`, `date`);

CREATE INDEX `idx_expires`
ON `magic_links` (`expires`);

CREATE INDEX `idx_queue_processing`
ON `mail_queue` (`attempts`, `priority`, `created_at`);

CREATE INDEX `idx_created`
ON `users` (`created_at`);
