-- LOCO database upgrade migration
-- Apply this file to the existing loco_db AFTER importing loco_db.sql.
-- This migration is additive: it does not DROP tables or delete existing rows.
-- Marketplace wishlist and transaction history are created by their existing migrations;
-- this file adds persistent community records and checkout/order records.

-- Preserve the existing discussions table and add the optional category/tag used by Community.
SET @loco_add_discussion_tag = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE discussions ADD COLUMN tag VARCHAR(40) NOT NULL DEFAULT ''General'' AFTER content',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'discussions'
      AND COLUMN_NAME = 'tag'
);
PREPARE loco_tag_stmt FROM @loco_add_discussion_tag;
EXECUTE loco_tag_stmt;
DEALLOCATE PREPARE loco_tag_stmt;

-- Discussion replies are separate from discussions so existing discussion rows remain intact.
CREATE TABLE IF NOT EXISTS discussion_replies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    discussion_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_discussion_replies_discussion_created (discussion_id, created_at),
    KEY idx_discussion_replies_user (user_id),
    CONSTRAINT fk_discussion_replies_discussion
        FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE,
    CONSTRAINT fk_discussion_replies_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Persistent trade requests. Offer inventory is nullable for purchase inquiries.
CREATE TABLE IF NOT EXISTS trade_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id INT NOT NULL,
    requester_id INT NOT NULL,
    seller_id INT NOT NULL,
    offered_inventory_id INT NULL,
    message VARCHAR(500) DEFAULT NULL,
    status ENUM('pending','accepted','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trade_requests_seller_status (seller_id, status, created_at),
    KEY idx_trade_requests_requester (requester_id, created_at),
    KEY idx_trade_requests_listing (listing_id),
    CONSTRAINT fk_trade_requests_listing
        FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_trade_requests_requester
        FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_trade_requests_seller
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_trade_requests_offer_inventory
        FOREIGN KEY (offered_inventory_id) REFERENCES inventories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Status audit trail for trade requests.
CREATE TABLE IF NOT EXISTS trade_request_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    trade_request_id BIGINT UNSIGNED NOT NULL,
    changed_by INT NULL,
    old_status ENUM('pending','accepted','rejected','cancelled','completed') DEFAULT NULL,
    new_status ENUM('pending','accepted','rejected','cancelled','completed') NOT NULL,
    note VARCHAR(500) DEFAULT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trade_history_request_time (trade_request_id, changed_at),
    CONSTRAINT fk_trade_history_request
        FOREIGN KEY (trade_request_id) REFERENCES trade_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_trade_history_user
        FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Marketplace moderation reports.
CREATE TABLE IF NOT EXISTS marketplace_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason VARCHAR(500) NOT NULL,
    status ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
    moderator_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_marketplace_reports_status_created (status, created_at),
    KEY idx_marketplace_reports_listing (listing_id),
    KEY idx_marketplace_reports_reporter (reporter_id),
    CONSTRAINT fk_marketplace_reports_listing
        FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_marketplace_reports_reporter
        FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Order header and line-item snapshots for persistent checkout receipts.
-- This schema does not process real payments; wallet checkout must be implemented in PHP
-- with a DB transaction before these records are used for actual balance deductions.
CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    reference_code VARCHAR(64) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('wallet','demo','external') NOT NULL DEFAULT 'wallet',
    status ENUM('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_reference (reference_code),
    KEY idx_orders_user_created (user_id, created_at),
    KEY idx_orders_status (status),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    game_id INT NULL,
    game_title VARCHAR(150) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_game (game_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_game
        FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Ensure duplicate ownership records cannot be inserted by normal checkout code.
-- Existing duplicate library rows are left untouched; this migration does not add a
-- unique constraint that could fail on an existing database with duplicate rows.
