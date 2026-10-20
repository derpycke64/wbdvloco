-- LOCO platform completion migration
-- MySQL 8.0+. Apply to an existing loco_db database.
-- This migration is additive and does not drop or reset existing data.

CREATE TABLE IF NOT EXISTS payment_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  transaction_type ENUM('game_purchase','wallet_top_up','marketplace_purchase','marketplace_sale','refund') NOT NULL,
  payment_method ENUM('wallet','sandbox_card','sandbox_bank','sandbox_ewallet') NOT NULL DEFAULT 'wallet',
  status ENUM('pending','completed','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'PHP',
  reference_code VARCHAR(64) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  metadata JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_transactions_reference (reference_code),
  KEY idx_payment_transactions_user_created (user_id, created_at),
  KEY idx_payment_transactions_status (status),
  CONSTRAINT fk_payment_transactions_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_payment_transactions_amount CHECK (amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS wallet_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  payment_transaction_id BIGINT UNSIGNED DEFAULT NULL,
  transaction_type ENUM('top_up','purchase','marketplace_sale','marketplace_purchase','refund','adjustment') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  balance_before DECIMAL(10,2) NOT NULL,
  balance_after DECIMAL(10,2) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wallet_transactions_user_created (user_id, created_at),
  KEY idx_wallet_transactions_payment (payment_transaction_id),
  CONSTRAINT fk_wallet_transactions_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wallet_transactions_payment
    FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- A user should only wishlist a game once and own a game once.
-- Check existing duplicate rows before adding these optional constraints to older databases.
-- CREATE UNIQUE INDEX uq_wishlists_user_game ON wishlists(user_id, game_id);
-- CREATE UNIQUE INDEX uq_library_user_game ON library(user_id, game_id);
