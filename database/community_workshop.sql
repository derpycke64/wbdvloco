-- LOCO Workshop: apply to loco_db after the base schema.
-- Additive migration: does not alter or delete existing marketplace or inventory data.
CREATE TABLE IF NOT EXISTS workshop_creations (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  game_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  archive_filename VARCHAR(100) NOT NULL,
  original_filename VARCHAR(180) NOT NULL,
  cover_filename VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_workshop_creator (user_id, created_at),
  KEY idx_workshop_game (game_id, created_at),
  CONSTRAINT fk_workshop_creation_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_workshop_creation_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS workshop_subscriptions (
  user_id INT NOT NULL,
  creation_id INT NOT NULL,
  subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, creation_id),
  KEY idx_workshop_sub_creation (creation_id),
  CONSTRAINT fk_workshop_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_workshop_sub_creation FOREIGN KEY (creation_id) REFERENCES workshop_creations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
