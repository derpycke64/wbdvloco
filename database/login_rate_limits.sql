-- Import once using phpMyAdmin before using the updated login.php.
-- Only SHA-256 digests of IP addresses and account identifiers are stored.
CREATE TABLE IF NOT EXISTS login_rate_limits (
  key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  failures SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  window_started_at DATETIME NOT NULL,
  PRIMARY KEY (key_hash),
  KEY idx_login_rate_window (window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional periodic cleanup (e.g., run manually during maintenance):
-- DELETE FROM login_rate_limits WHERE window_started_at < UTC_TIMESTAMP() - INTERVAL 2 DAY;
