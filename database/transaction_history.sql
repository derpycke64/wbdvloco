-- LOCO transaction history migration
-- Run once against loco_db. This migration is additive and does not delete existing data.
CREATE TABLE IF NOT EXISTS transaction_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  transaction_type ENUM('purchase','wallet_topup','refund','marketplace_purchase','marketplace_sale') NOT NULL DEFAULT 'purchase',
  payment_method VARCHAR(40) NOT NULL DEFAULT 'wallet',
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'completed',
  reference_code VARCHAR(64) NOT NULL,
  items_json JSON DEFAULT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_transaction_reference (reference_code),
  KEY idx_transaction_user_created (user_id, created_at),
  KEY idx_transaction_status (status),
  CONSTRAINT fk_transaction_history_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
