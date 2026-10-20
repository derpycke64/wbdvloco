-- Apply this migration after importing loco_db.sql.
-- Moves the former demo marketplace cards into persistent database records.
-- Safe to run more than once: existing matching demo items/listings are not duplicated.

CREATE TABLE IF NOT EXISTS marketplace_wishlists (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_marketplace_wishlist_user_listing (user_id, listing_id),
    KEY idx_marketplace_wishlist_listing (listing_id),
    CONSTRAINT fk_marketplace_wishlist_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_marketplace_wishlist_listing
        FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO game_items (game_id, item_name, description, rarity, image_url)
SELECT g.id, seed.item_name, seed.description, seed.rarity, seed.image_url
FROM (
    SELECT 'Nebula Drift' AS game_title, 'Starfarer Suit' AS item_name, 'An exploration suit with a pearlescent visor finish.' AS description, 'Excellent' AS rarity, 'images/market_item_01.svg' AS image_url
    UNION ALL SELECT 'Crimson Freight', 'Ember Rail Token', 'A rare conductor token from the Emberline route.', 'Excellent', 'images/market_item_02.svg'
    UNION ALL SELECT 'Moonroot Hollow', 'Mooncap Satchel', 'A stitched satchel patterned with moonlit mushrooms.', 'Very good', 'images/market_item_03.svg'
    UNION ALL SELECT 'Abyss Protocol', 'Pressure Diver Rig', 'A deep-sea rig cosmetic with luminous gauge details.', 'Excellent', 'images/market_item_04.svg'
    UNION ALL SELECT 'Stormbanner Tactics', 'Silver Hawk Standard', 'A commander banner with silver-threaded edges.', 'Good', 'images/market_item_05.svg'
    UNION ALL SELECT 'Neon Velocity', 'Pulseframe Bike Skin', 'A magenta-cyan body kit with animated light accents.', 'Excellent', 'images/market_item_06.svg'
    UNION ALL SELECT 'Clockwork Reverie', 'Brass Key Set', 'A set of ornate keys for collectors of mechanical curiosities.', 'Very good', 'images/market_item_07.svg'
    UNION ALL SELECT 'Ashen Crown', 'Cinderblade Wrap', 'A dark steel finish with glowing ember veins.', 'Excellent', 'images/market_item_08.svg'
    UNION ALL SELECT 'Skydrift Homestead', 'Cloudseed Planter', 'A floating planter designed for sky-island gardens.', 'Good', 'images/market_item_09.svg'
    UNION ALL SELECT 'The Last Bell', 'Seaglass Locket', 'A tiny locket holding a fragment of sea-polished glass.', 'Excellent', 'images/market_item_10.svg'
    UNION ALL SELECT 'Emberline Chronicles', 'Phoenix Sigil', 'An old-world sigil with a warm, ember-like glow.', 'Excellent', 'images/market_item_11.svg'
    UNION ALL SELECT 'Starfall Cartographer', 'Celestial Compass', 'A polished compass casing inspired by mapped star routes.', 'Very good', 'images/market_item_12.svg'
    UNION ALL SELECT 'Riftbound Arena', 'Rift Core Badge', 'A limited arena badge with a prismatic rift motif.', 'Excellent', 'images/market_item_13.svg'
    UNION ALL SELECT 'Lanterns of Luma', 'Glowpet Lantern', 'A soft-glowing lantern charm shaped like a tiny spirit.', 'Good', 'images/market_item_14.svg'
    UNION ALL SELECT 'Iron Orchard', 'Clockwork Apple', 'A small brass fruit from the orchard engineering set.', 'Very good', 'images/market_item_15.svg'
    UNION ALL SELECT 'Echoes Below', 'Sonar Charm', 'A sonar-themed charm for deep exploration loadouts.', 'Excellent', 'images/market_item_16.svg'
    UNION ALL SELECT 'Glass Harbor', 'Tideglass Vase', 'A translucent vase with a blue-green tide pattern.', 'Good', 'images/market_item_17.svg'
    UNION ALL SELECT 'Wild Circuit', 'Volt Runner Gloves', 'Racing gloves with electric linework and reinforced knuckles.', 'Excellent', 'images/market_item_18.svg'
) AS seed
INNER JOIN games g ON g.title = seed.game_title
WHERE NOT EXISTS (
    SELECT 1 FROM game_items existing
    WHERE existing.game_id = g.id AND existing.item_name = seed.item_name
);

-- The schema ties a listing to a real account. Seed the demo catalog under the
-- first existing account, if one exists; real users can publish their own listings.
INSERT INTO marketplace_listings (seller_id, item_id, price, status)
SELECT u.id, gi.id,
       CASE gi.item_name
         WHEN 'Starfarer Suit' THEN 2490.00
         WHEN 'Ember Rail Token' THEN 420.00
         WHEN 'Mooncap Satchel' THEN 315.00
         WHEN 'Pressure Diver Rig' THEN 1290.00
         WHEN 'Silver Hawk Standard' THEN 575.00
         WHEN 'Pulseframe Bike Skin' THEN 1399.00
         WHEN 'Brass Key Set' THEN 245.00
         WHEN 'Cinderblade Wrap' THEN 850.00
         WHEN 'Cloudseed Planter' THEN 199.00
         WHEN 'Seaglass Locket' THEN 360.00
         WHEN 'Phoenix Sigil' THEN 1490.00
         WHEN 'Celestial Compass' THEN 699.00
         WHEN 'Rift Core Badge' THEN 0.00
         WHEN 'Glowpet Lantern' THEN 325.00
         WHEN 'Clockwork Apple' THEN 410.00
         WHEN 'Sonar Charm' THEN 590.00
         WHEN 'Tideglass Vase' THEN 275.00
         WHEN 'Volt Runner Gloves' THEN 799.00
         ELSE 0.00
       END,
       'active'
FROM users u
JOIN game_items gi ON 1 = 1
JOIN games g ON g.id = gi.game_id
WHERE u.id = (SELECT MIN(id) FROM users)
  AND gi.item_name IN (
    'Starfarer Suit','Ember Rail Token','Mooncap Satchel','Pressure Diver Rig',
    'Silver Hawk Standard','Pulseframe Bike Skin','Brass Key Set','Cinderblade Wrap',
    'Cloudseed Planter','Seaglass Locket','Phoenix Sigil','Celestial Compass',
    'Rift Core Badge','Glowpet Lantern','Clockwork Apple','Sonar Charm',
    'Tideglass Vase','Volt Runner Gloves'
  )
  AND NOT EXISTS (
    SELECT 1 FROM marketplace_listings ml
    WHERE ml.item_id = gi.id AND ml.seller_id = u.id
  );
