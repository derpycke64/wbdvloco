-- LOCO community inventory ownership and marketplace listing linkage.
-- Run after loco_db.sql and database/marketplace_database.sql.
-- Existing listings are linked to inventory records so the listed item has an owner.

SET @loco_add_listing_inventory_id = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE marketplace_listings ADD COLUMN inventory_id INT NULL AFTER item_id, ADD KEY idx_marketplace_listings_inventory (inventory_id)',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'marketplace_listings'
      AND COLUMN_NAME = 'inventory_id'
);
PREPARE loco_stmt FROM @loco_add_listing_inventory_id;
EXECUTE loco_stmt;
DEALLOCATE PREPARE loco_stmt;

-- Backfill one owned inventory entry for any legacy listing item that is not already
-- represented in the seller's inventory. Existing inventory records are preserved.
INSERT INTO inventories (user_id, item_id)
SELECT ml.seller_id, ml.item_id
FROM marketplace_listings ml
WHERE ml.inventory_id IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM inventories i
      WHERE i.user_id = ml.seller_id
        AND i.item_id = ml.item_id
  );

-- Link listings to their seller-owned inventory item.
UPDATE marketplace_listings ml
JOIN inventories i
  ON i.user_id = ml.seller_id
 AND i.item_id = ml.item_id
SET ml.inventory_id = i.id
WHERE ml.inventory_id IS NULL;

SET @loco_add_listing_inventory_fk = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE marketplace_listings ADD CONSTRAINT fk_marketplace_listing_inventory FOREIGN KEY (inventory_id) REFERENCES inventories(id) ON DELETE SET NULL',
        'SELECT 1')
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'marketplace_listings'
      AND CONSTRAINT_NAME = 'fk_marketplace_listing_inventory'
);
PREPARE loco_stmt FROM @loco_add_listing_inventory_fk;
EXECUTE loco_stmt;
DEALLOCATE PREPARE loco_stmt;

-- Verify listing ownership and personal inventory:
-- SELECT ml.id, ml.seller_id, ml.inventory_id, i.user_id AS inventory_owner
-- FROM marketplace_listings ml
-- LEFT JOIN inventories i ON i.id = ml.inventory_id;
-- SELECT i.id, u.username, gi.item_name, g.title AS game
-- FROM inventories i
-- JOIN users u ON u.id = i.user_id
-- JOIN game_items gi ON gi.id = i.item_id
-- JOIN games g ON g.id = gi.game_id
-- ORDER BY u.username, gi.item_name;
