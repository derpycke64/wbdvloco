-- Compatibility migration for the existing LOCO Workshop tables.
-- Non-destructive: preserves creator_id, preview_image_url, existing IDs and subscriptions.
CREATE TABLE IF NOT EXISTS workshop_creations (
 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 game_id INT NOT NULL,
 creator_id INT NOT NULL,
 title VARCHAR(150) NOT NULL,
 description TEXT DEFAULT NULL,
 preview_image_url VARCHAR(255) DEFAULT NULL,
 created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_workshop_game (game_id),
 KEY idx_workshop_creator (creator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS workshop_subscriptions (
 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 creation_id INT NOT NULL,
 subscribed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_workshop_sub_user (user_id),
 KEY idx_workshop_sub_creation (creation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @ws_add_archive = (
 SELECT IF(COUNT(*)=0,'ALTER TABLE workshop_creations ADD COLUMN archive_filename VARCHAR(100) NULL','SELECT 1')
 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME='workshop_creations' AND COLUMN_NAME='archive_filename'
);
PREPARE ws_migration FROM @ws_add_archive;
EXECUTE ws_migration;
DEALLOCATE PREPARE ws_migration;

SET @ws_add_original = (
 SELECT IF(COUNT(*)=0,'ALTER TABLE workshop_creations ADD COLUMN original_filename VARCHAR(180) NULL','SELECT 1')
 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME='workshop_creations' AND COLUMN_NAME='original_filename'
);
PREPARE ws_migration FROM @ws_add_original;
EXECUTE ws_migration;
DEALLOCATE PREPARE ws_migration;

SET @ws_add_cover = (
 SELECT IF(COUNT(*)=0,'ALTER TABLE workshop_creations ADD COLUMN cover_filename VARCHAR(100) NULL','SELECT 1')
 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME='workshop_creations' AND COLUMN_NAME='cover_filename'
);
PREPARE ws_migration FROM @ws_add_cover;
EXECUTE ws_migration;
DEALLOCATE PREPARE ws_migration;
