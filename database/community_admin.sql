-- Additive LOCO administrator permissions and moderation audit trail.
-- Back up loco_db first. Existing users remain regular users until explicitly promoted.
SET @add_admin_role = (
 SELECT IF(COUNT(*)=0,
  "ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user'",
  'SELECT 1')
 FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role'
);
PREPARE loco_role_stmt FROM @add_admin_role;
EXECUTE loco_role_stmt;
DEALLOCATE PREPARE loco_role_stmt;

CREATE TABLE IF NOT EXISTS admin_audit_log (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 admin_user_id INT NOT NULL,
 action VARCHAR(70) NOT NULL,
 target_type VARCHAR(45) NOT NULL,
 target_id BIGINT UNSIGNED NOT NULL,
 details VARCHAR(500) DEFAULT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 KEY idx_admin_audit_time (created_at),
 KEY idx_admin_audit_admin (admin_user_id),
 CONSTRAINT fk_admin_audit_admin FOREIGN KEY (admin_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promote your chosen account manually, after verifying its identity:
-- SELECT id, username, role FROM users;
-- UPDATE users SET role='admin' WHERE id=YOUR_CHOSEN_USER_ID;
