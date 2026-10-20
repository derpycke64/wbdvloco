-- LOCO unified Wishlist / Games tab.
-- Import in phpMyAdmin into loco_db. Safe to rerun; does not touch marketplace_wishlists.
CREATE TABLE IF NOT EXISTS game_wishlists (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  game_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_game_wishlists_user_game (user_id, game_id),
  KEY idx_game_wishlists_game (game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
