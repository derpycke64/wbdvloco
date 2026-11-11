-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 11, 2026 at 11:54 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `loco_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_log`
--

DROP TABLE IF EXISTS `admin_audit_log`;
CREATE TABLE IF NOT EXISTS `admin_audit_log` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_user_id` int NOT NULL,
  `action` varchar(70) NOT NULL,
  `target_type` varchar(45) NOT NULL,
  `target_id` bigint UNSIGNED NOT NULL,
  `details` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_audit_time` (`created_at`),
  KEY `idx_admin_audit_admin` (`admin_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_audit_log`
--

INSERT INTO `admin_audit_log` (`id`, `admin_user_id`, `action`, `target_type`, `target_id`, `details`, `created_at`) VALUES
(1, 1, 'report_open', 'report', 1, '', '2026-10-20 13:33:45'),
(2, 1, 'remove_listing', 'listing', 15, 'extorsion', '2026-10-20 13:34:32'),
(3, 1, 'report_resolved', 'report', 1, 'yea removed it alr', '2026-10-20 13:34:39'),
(4, 1, 'report_resolved', 'report', 1, 'Report status updated; private note provided; public response provided.', '2026-10-20 13:39:22'),
(5, 1, 'report_resolved', 'report', 1, 'Report status updated; private note provided; public response provided.', '2026-10-20 13:39:25'),
(6, 1, 'remove_listing', 'listing', 4, '', '2026-10-20 13:44:19'),
(7, 1, 'remove_listing', 'listing', 14, '', '2026-10-20 13:44:36'),
(8, 1, 'remove_listing', 'listing', 12, '', '2026-10-20 13:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `discussions`
--

DROP TABLE IF EXISTS `discussions`;
CREATE TABLE IF NOT EXISTS `discussions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tag` varchar(40) NOT NULL DEFAULT 'General',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `discussions`
--

INSERT INTO `discussions` (`id`, `user_id`, `title`, `content`, `tag`, `created_at`) VALUES
(1, 1, 'Does Verity belong to minecraft or does he belong to backrooms', 'I really think he belongs to minecraft. What do you guys think?', 'General', '2026-10-20 13:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `discussion_replies`
--

DROP TABLE IF EXISTS `discussion_replies`;
CREATE TABLE IF NOT EXISTS `discussion_replies` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `discussion_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_discussion_replies_discussion_created` (`discussion_id`,`created_at`),
  KEY `idx_discussion_replies_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `discussion_replies`
--

INSERT INTO `discussion_replies` (`id`, `discussion_id`, `user_id`, `content`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'FRFR he does belong to minecraft', '2026-10-20 13:20:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
CREATE TABLE IF NOT EXISTS `games` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `title`, `description`, `price`, `image_url`, `publisher`) VALUES
(1, 'Nebula Drift', 'Chart a course across luminous alien oceans.', 2490.00, 'images/gameplay_01.jpg', 'stoker'),
(2, 'Crimson Freight', 'A high-stakes locomotive adventure across a frozen wasteland.', 899.00, 'images/gameplay_02.jpg', 'wally-stark'),
(3, 'Moonroot Hollow', 'Stitch custom spells inside moonlit fungal caverns.', 699.00, 'images/gameplay_03.jpg', 'partner'),
(4, 'Abyss Protocol', 'Deep-sea tactical diving loadouts with glowing gauge rigs.', 1299.00, 'images/gameplay_04.jpg', 'stoker'),
(5, 'Stormbanner Tactics', 'Deploy strategic commander banners with silver-threaded edges.', 1150.00, 'images/gameplay_05.jpg', 'wally-stark'),
(6, 'Neon Velocity', 'Tear through a rain-lit megacity in high-speed future cycles.', 1399.00, 'images/gameplay_06.jpg', 'partner'),
(7, 'Clockwork Reverie', 'Collect mechanical curiosities and winding pocket-watch puzzle keys.', 599.00, 'images/gameplay_07.jpg', 'stoker'),
(8, 'Ashen Crown', 'Wield dark steel frames integrated with living ember veins.', 1299.00, 'images/gameplay_08.jpg', 'wally-stark'),
(9, 'Skydrift Homestead', 'Cultivate crop arrangements across floating sky-island gardens.', 799.00, 'images/gameplay_09.jpg', 'partner'),
(10, 'The Last Bell', 'A narrative mystery holding fragments of sea-polished glass tokens.', 499.00, 'images/gameplay_10.jpg', 'stoker'),
(11, 'Emberline Chronicles', 'Uncover historic old-world sigils running down warm routes.', 1499.00, 'images/gameplay_11.jpg', 'wally-stark'),
(12, 'Starfall Cartographer', 'Map out uncharted celestial route pathways using star-routes.', 899.00, 'images/gameplay_12.jpg', 'partner'),
(13, 'Riftbound Arena', 'Prismatic combat encounters inside limited arena dimensions.', 0.00, 'images/gameplay_13.jpg', 'stoker'),
(14, 'Lanterns of Luma', 'Glowpet helper accessory attachments featuring spirit charm models.', 649.00, 'images/gameplay_14.jpg', 'partner'),
(15, 'Iron Orchard', 'Orchard structural engineering sets built with raw brass hardware.', 1099.00, 'images/gameplay_15.jpg', 'wally-stark'),
(16, 'Echoes Below', 'Sonar-themed sonar loadout utilities designed for dark exploration.', 999.00, 'images/gameplay_16.jpg', 'stoker'),
(17, 'Glass Harbor', 'Translucent glassware decor sets showing beautiful tide accents.', 549.00, 'images/gameplay_17.jpg', 'partner'),
(18, 'Wild Circuit', 'Electric cyber racing racing gears with reinforced knuckle designs.', 1199.00, 'images/gameplay_18.jpg', 'wally-stark');

-- --------------------------------------------------------

--
-- Table structure for table `game_items`
--

DROP TABLE IF EXISTS `game_items`;
CREATE TABLE IF NOT EXISTS `game_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text,
  `rarity` varchar(50) DEFAULT 'Common',
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `game_items`
--

INSERT INTO `game_items` (`id`, `game_id`, `item_name`, `description`, `rarity`, `image_url`) VALUES
(1, 1, 'Starfarer Suit', 'An exploration suit with a pearlescent visor finish.', 'Excellent', 'images/market_item_01.svg'),
(2, 2, 'Ember Rail Token', 'A rare conductor token from the Emberline route.', 'Excellent', 'images/market_item_02.svg'),
(3, 3, 'Mooncap Satchel', 'A stitched satchel patterned with moonlit mushrooms.', 'Very good', 'images/market_item_03.svg'),
(4, 4, 'Pressure Diver Rig', 'A deep-sea rig cosmetic with luminous gauge details.', 'Excellent', 'images/market_item_04.svg'),
(5, 5, 'Silver Hawk Standard', 'A commander banner with silver-threaded edges.', 'Good', 'images/market_item_05.svg'),
(6, 6, 'Pulseframe Bike Skin', 'A magenta-cyan body kit with animated light accents.', 'Excellent', 'images/market_item_06.svg'),
(7, 7, 'Brass Key Set', 'A set of ornate keys for collectors of mechanical curiosities.', 'Very good', 'images/market_item_07.svg'),
(8, 8, 'Cinderblade Wrap', 'A dark steel finish with glowing ember veins.', 'Excellent', 'images/market_item_08.svg'),
(9, 9, 'Cloudseed Planter', 'A floating planter designed for sky-island gardens.', 'Good', 'images/market_item_09.svg'),
(10, 10, 'Seaglass Locket', 'A tiny locket holding a fragment of sea-polished glass.', 'Excellent', 'images/market_item_10.svg'),
(11, 11, 'Phoenix Sigil', 'An old-world sigil with a warm, ember-like glow.', 'Excellent', 'images/market_item_11.svg'),
(12, 12, 'Celestial Compass', 'A polished compass casing inspired by mapped star routes.', 'Very good', 'images/market_item_12.svg'),
(13, 13, 'Rift Core Badge', 'A limited arena badge with a prismatic rift motif.', 'Excellent', 'images/market_item_13.svg'),
(14, 14, 'Glowpet Lantern', 'A soft-glowing lantern charm shaped like a tiny spirit.', 'Good', 'images/market_item_14.svg'),
(15, 15, 'Clockwork Apple', 'A small brass fruit from the orchard engineering set.', 'Very good', 'images/market_item_15.svg'),
(16, 16, 'Sonar Charm', 'A sonar-themed charm for deep exploration loadouts.', 'Excellent', 'images/market_item_16.svg'),
(17, 17, 'Tideglass Vase', 'A translucent vase with a blue-green tide pattern.', 'Good', 'images/market_item_17.svg'),
(18, 18, 'Volt Runner Gloves', 'Racing gloves with electric linework and reinforced knuckles.', 'Excellent', 'images/market_item_18.svg'),
(32, 18, 'Volt Runner Gloves', 'User-added inventory item', 'Gloves', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `game_resale_listings`
--

DROP TABLE IF EXISTS `game_resale_listings`;
CREATE TABLE IF NOT EXISTS `game_resale_listings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `game_id` int NOT NULL,
  `buyer_id` int DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('active','sold','cancelled','removed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sold_at` timestamp NULL DEFAULT NULL,
  `active_library_id` int GENERATED ALWAYS AS ((case when (`status` = _utf8mb4'active') then `library_id` else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loco_resale_active_library` (`active_library_id`),
  KEY `idx_loco_resale_active` (`status`,`created_at`),
  KEY `idx_loco_resale_seller` (`seller_id`,`status`),
  KEY `idx_loco_resale_game` (`game_id`,`status`),
  KEY `idx_loco_resale_library_history` (`library_id`,`id`),
  KEY `idx_loco_resale_buyer` (`buyer_id`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `game_resale_listings`
--

INSERT INTO `game_resale_listings` (`id`, `library_id`, `seller_id`, `game_id`, `buyer_id`, `price`, `status`, `created_at`, `sold_at`) VALUES
(1, 3, 2, 3, NULL, 300.00, 'active', '2026-10-20 15:44:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `game_tags`
--

DROP TABLE IF EXISTS `game_tags`;
CREATE TABLE IF NOT EXISTS `game_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `tag_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `game_tags`
--

INSERT INTO `game_tags` (`id`, `game_id`, `tag_name`) VALUES
(1, 1, 'action'),
(2, 1, 'coop'),
(3, 2, 'action'),
(4, 2, 'strategy'),
(5, 2, 'coop'),
(6, 2, 'retro'),
(7, 3, 'indie-rpg'),
(8, 3, 'casual'),
(9, 3, 'coop'),
(10, 3, 'retro'),
(11, 4, 'action'),
(12, 4, 'coop'),
(13, 5, 'strategy'),
(14, 5, 'retro'),
(15, 6, 'action'),
(16, 6, 'cyberpunk'),
(17, 7, 'casual'),
(18, 7, 'retro'),
(19, 8, 'action'),
(20, 8, 'indie-rpg'),
(21, 8, 'coop'),
(22, 8, 'retro'),
(23, 9, 'casual'),
(24, 9, 'coop'),
(25, 10, 'indie-rpg'),
(26, 10, 'casual'),
(27, 10, 'retro'),
(28, 11, 'indie-rpg'),
(29, 11, 'coop'),
(30, 12, 'strategy'),
(31, 12, 'indie-rpg'),
(32, 12, 'retro'),
(33, 13, 'action'),
(34, 13, 'coop'),
(35, 13, 'cyberpunk'),
(36, 14, 'indie-rpg'),
(37, 14, 'casual'),
(38, 14, 'retro'),
(39, 15, 'strategy'),
(40, 15, 'retro'),
(41, 16, 'action'),
(42, 16, 'coop'),
(43, 17, 'indie-rpg'),
(44, 17, 'casual'),
(45, 17, 'retro'),
(46, 18, 'action'),
(47, 18, 'cyberpunk');

-- --------------------------------------------------------

--
-- Table structure for table `game_wishlists`
--

DROP TABLE IF EXISTS `game_wishlists`;
CREATE TABLE IF NOT EXISTS `game_wishlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `game_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_game_wishlists_user_game` (`user_id`,`game_id`),
  KEY `idx_game_wishlists_game` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_wishlists`
--

INSERT INTO `game_wishlists` (`id`, `user_id`, `game_id`, `created_at`) VALUES
(1, 2, 4, '2026-10-20 15:19:32');

-- --------------------------------------------------------

--
-- Table structure for table `inventories`
--

DROP TABLE IF EXISTS `inventories`;
CREATE TABLE IF NOT EXISTS `inventories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `item_id` int NOT NULL,
  `acquired_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventories`
--

INSERT INTO `inventories` (`id`, `user_id`, `item_id`, `acquired_at`) VALUES
(1, 1, 32, '2026-10-20 12:09:11'),
(2, 1, 1, '2026-10-20 12:24:33'),
(3, 1, 2, '2026-10-20 12:24:33'),
(4, 1, 3, '2026-10-20 12:24:33'),
(5, 1, 4, '2026-10-20 12:24:33'),
(6, 1, 5, '2026-10-20 12:24:33'),
(7, 1, 6, '2026-10-20 12:24:33'),
(8, 1, 7, '2026-10-20 12:24:33'),
(9, 1, 8, '2026-10-20 12:24:33'),
(10, 1, 9, '2026-10-20 12:24:33'),
(11, 1, 10, '2026-10-20 12:24:33'),
(12, 1, 11, '2026-10-20 12:24:33'),
(13, 1, 12, '2026-10-20 12:24:33'),
(15, 1, 14, '2026-10-20 12:24:33'),
(16, 1, 15, '2026-10-20 12:24:33'),
(17, 2, 16, '2026-10-20 12:24:33'),
(18, 1, 17, '2026-10-20 12:24:33'),
(19, 2, 18, '2026-10-20 12:24:33');

-- --------------------------------------------------------

--
-- Table structure for table `library`
--

DROP TABLE IF EXISTS `library`;
CREATE TABLE IF NOT EXISTS `library` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `game_id` int NOT NULL,
  `purchase_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `license_key` char(24) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_loco_library_license_key` (`license_key`),
  KEY `user_id` (`user_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `library`
--

INSERT INTO `library` (`id`, `user_id`, `game_id`, `purchase_date`, `license_key`) VALUES
(1, 2, 1, '2026-10-20 14:37:29', 'A47729201DF5AE3345D815CB'),
(2, 2, 6, '2026-10-20 15:20:39', 'BA125BABCAF2C0B4730B7B9F'),
(3, 2, 3, '2026-10-20 15:21:13', '15C62E6A08EFB34F31EFB0BE'),
(4, 2, 5, '2026-10-20 15:22:13', '34B864FCD8D974CD5D457788');

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_listings`
--

DROP TABLE IF EXISTS `marketplace_listings`;
CREATE TABLE IF NOT EXISTS `marketplace_listings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `item_id` int NOT NULL,
  `inventory_id` int DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`),
  KEY `item_id` (`item_id`),
  KEY `idx_marketplace_listings_inventory` (`inventory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `marketplace_listings`
--

INSERT INTO `marketplace_listings` (`id`, `seller_id`, `item_id`, `inventory_id`, `price`, `status`, `created_at`) VALUES
(1, 1, 1, 2, 2490.00, 'active', '2026-10-20 12:07:02'),
(2, 1, 2, 3, 420.00, 'active', '2026-10-20 12:07:02'),
(3, 1, 3, 4, 315.00, 'active', '2026-10-20 12:07:02'),
(4, 1, 4, 5, 1290.00, 'removed', '2026-10-20 12:07:02'),
(5, 1, 5, 6, 575.00, 'active', '2026-10-20 12:07:02'),
(6, 1, 6, 7, 1399.00, 'active', '2026-10-20 12:07:02'),
(7, 1, 7, 8, 245.00, 'active', '2026-10-20 12:07:02'),
(8, 1, 8, 9, 850.00, 'active', '2026-10-20 12:07:02'),
(9, 1, 9, 10, 199.00, 'active', '2026-10-20 12:07:02'),
(10, 1, 10, 11, 360.00, 'active', '2026-10-20 12:07:02'),
(11, 1, 11, 12, 1490.00, 'active', '2026-10-20 12:07:02'),
(12, 1, 12, 13, 699.00, 'removed', '2026-10-20 12:07:02'),
(13, 1, 13, NULL, 0.00, 'sold', '2026-10-20 12:07:02'),
(14, 1, 14, 15, 325.00, 'removed', '2026-10-20 12:07:02'),
(15, 1, 15, 16, 410.00, 'removed', '2026-10-20 12:07:02'),
(16, 1, 16, 17, 590.00, 'traded', '2026-10-20 12:07:02'),
(17, 1, 17, 18, 275.00, 'sold', '2026-10-20 12:07:02'),
(18, 1, 18, 19, 799.00, 'sold', '2026-10-20 12:07:02');

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_reports`
--

DROP TABLE IF EXISTS `marketplace_reports`;
CREATE TABLE IF NOT EXISTS `marketplace_reports` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` enum('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
  `moderator_notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `admin_response` text,
  PRIMARY KEY (`id`),
  KEY `idx_marketplace_reports_status_created` (`status`,`created_at`),
  KEY `idx_marketplace_reports_listing` (`listing_id`),
  KEY `idx_marketplace_reports_reporter` (`reporter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `marketplace_reports`
--

INSERT INTO `marketplace_reports` (`id`, `listing_id`, `reporter_id`, `reason`, `status`, `moderator_notes`, `created_at`, `resolved_at`, `admin_response`) VALUES
(1, 15, 2, 'this mf extorting for an apple br', 'resolved', 'true', '2026-10-20 13:21:53', '2026-10-20 13:39:25', 'yea removed it alr');

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_wishlists`
--

DROP TABLE IF EXISTS `marketplace_wishlists`;
CREATE TABLE IF NOT EXISTS `marketplace_wishlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `listing_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_marketplace_wishlist_user_listing` (`user_id`,`listing_id`),
  KEY `idx_marketplace_wishlist_listing` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reference_code` varchar(64) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('wallet','demo','external') NOT NULL DEFAULT 'wallet',
  `status` enum('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_reference` (`reference_code`),
  KEY `idx_orders_user_created` (`user_id`,`created_at`),
  KEY `idx_orders_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `reference_code`, `subtotal`, `total_amount`, `payment_method`, `status`, `created_at`, `completed_at`) VALUES
(1, 2, 'LOCO-EBBB91C35054E62E', 2490.00, 2490.00, 'external', 'completed', '2026-10-20 14:37:29', '2026-10-20 14:37:29'),
(2, 2, 'LOCO-B1CBC8ADE13CC6E6', 1399.00, 1399.00, 'wallet', 'completed', '2026-10-20 15:20:39', '2026-10-20 15:20:39'),
(3, 2, 'LOCO-ED0CE2990A3A2909', 699.00, 699.00, 'external', 'completed', '2026-10-20 15:21:13', '2026-10-20 15:21:13'),
(4, 2, 'LOCO-D1C11B247C6AC895', 1150.00, 1150.00, 'external', 'completed', '2026-10-20 15:22:13', '2026-10-20 15:22:13');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint UNSIGNED NOT NULL,
  `game_id` int DEFAULT NULL,
  `game_title` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `line_total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_game` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `game_id`, `game_title`, `unit_price`, `quantity`, `line_total`) VALUES
(1, 1, 1, 'Nebula Drift', 2490.00, 1, 2490.00),
(2, 2, 6, 'Neon Velocity', 1399.00, 1, 1399.00),
(3, 3, 3, 'Moonroot Hollow', 699.00, 1, 699.00),
(4, 4, 5, 'Stormbanner Tactics', 1150.00, 1, 1150.00);

-- --------------------------------------------------------

--
-- Table structure for table `trade_requests`
--

DROP TABLE IF EXISTS `trade_requests`;
CREATE TABLE IF NOT EXISTS `trade_requests` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` int NOT NULL,
  `requester_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `offered_inventory_id` int DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trade_requests_seller_status` (`seller_id`,`status`,`created_at`),
  KEY `idx_trade_requests_requester` (`requester_id`,`created_at`),
  KEY `idx_trade_requests_listing` (`listing_id`),
  KEY `fk_trade_requests_offer_inventory` (`offered_inventory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `trade_requests`
--

INSERT INTO `trade_requests` (`id`, `listing_id`, `requester_id`, `seller_id`, `offered_inventory_id`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 16, 2, 1, 18, NULL, 'completed', '2026-10-20 12:37:27', '2026-10-20 12:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `trade_request_history`
--

DROP TABLE IF EXISTS `trade_request_history`;
CREATE TABLE IF NOT EXISTS `trade_request_history` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `trade_request_id` bigint UNSIGNED NOT NULL,
  `changed_by` int DEFAULT NULL,
  `old_status` enum('pending','accepted','rejected','cancelled','completed') DEFAULT NULL,
  `new_status` enum('pending','accepted','rejected','cancelled','completed') NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trade_history_request_time` (`trade_request_id`,`changed_at`),
  KEY `fk_trade_history_user` (`changed_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `trade_request_history`
--

INSERT INTO `trade_request_history` (`id`, `trade_request_id`, `changed_by`, `old_status`, `new_status`, `note`, `changed_at`) VALUES
(1, 1, 2, NULL, 'pending', 'Trade offer submitted', '2026-10-20 12:37:27'),
(2, 1, 1, 'pending', 'completed', 'Both inventory items exchanged', '2026-10-20 12:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_history`
--

DROP TABLE IF EXISTS `transaction_history`;
CREATE TABLE IF NOT EXISTS `transaction_history` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `transaction_type` enum('purchase','wallet_topup','refund','marketplace_purchase','marketplace_sale') NOT NULL DEFAULT 'purchase',
  `payment_method` varchar(40) NOT NULL DEFAULT 'wallet',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'completed',
  `reference_code` varchar(64) NOT NULL,
  `items_json` json DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transaction_reference` (`reference_code`),
  KEY `idx_transaction_user_created` (`user_id`,`created_at`),
  KEY `idx_transaction_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaction_history`
--

INSERT INTO `transaction_history` (`id`, `user_id`, `transaction_type`, `payment_method`, `amount`, `status`, `reference_code`, `items_json`, `notes`, `created_at`) VALUES
(1, 2, 'wallet_topup', 'sandbox_card', 1000.00, 'completed', 'TOPUP-3E62218EB12B', NULL, 'Simulated sandbox card top-up; no real payment was processed', '2026-10-20 12:20:01'),
(2, 2, 'wallet_topup', 'sandbox_card', 500.00, 'completed', 'TOPUP-4E06BA782B88', NULL, 'Simulated sandbox card top-up; no real payment was processed', '2026-10-20 12:29:12'),
(3, 2, 'purchase', 'sandbox_card', 2490.00, 'completed', 'LOCO-EBBB91C35054E62E', '[\"Nebula Drift\"]', 'Simulated gcash payment; no real payment was processed', '2026-10-20 14:37:29'),
(4, 2, 'wallet_topup', 'sandbox_card', 250.00, 'completed', 'TOPUP-CA771981AD3F', NULL, 'Simulated paypal wallet top-up; no real payment processed', '2026-10-20 15:16:57'),
(5, 2, 'wallet_topup', 'sandbox_card', 1000.00, 'completed', 'TOPUP-A033B16A3B64', NULL, 'Simulated gcash wallet top-up; no real payment processed', '2026-10-20 15:17:37'),
(6, 2, 'purchase', 'wallet', 1399.00, 'completed', 'LOCO-B1CBC8ADE13CC6E6', '[\"Neon Velocity\"]', 'Paid using LOCO wallet', '2026-10-20 15:20:39'),
(7, 2, 'purchase', 'sandbox_card', 699.00, 'completed', 'LOCO-ED0CE2990A3A2909', '[\"Moonroot Hollow\"]', 'Simulated gcash payment; no real payment was processed', '2026-10-20 15:21:13'),
(8, 2, 'purchase', 'sandbox_card', 1150.00, 'completed', 'LOCO-D1C11B247C6AC895', '[\"Stormbanner Tactics\"]', 'Simulated paypal payment; no real payment was processed', '2026-10-20 15:22:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birthday` date NOT NULL,
  `wallet_balance` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `password` (`password`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `birthday`, `wallet_balance`, `created_at`, `role`) VALUES
(1, 'John Halo', 'wadelossantos0004val@student.fatima.edu.ph', '$2y$10$TIP5WXTK.sOJXnrB9/OmG.U/Zx6hg73QiIxmUcKGkyfG5ERle4blW', '1980-11-15', 1074.00, '2026-10-19 16:58:10', 'admin'),
(2, 'Kiryu Kazuma', 'Kiryukazuma@gmail.com', '$2y$10$hmUw8dS.ydMWC0VL8mJPXun7CZNNk4Vzus/lnaaS6vpaqxWzASyiy', '2007-11-13', 277.00, '2026-10-20 12:18:02', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT 'uploads/avatars/default.png',
  `bio` text,
  `favorite_games` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `display_name`, `avatar_url`, `bio`, `favorite_games`) VALUES
(1, 1, 'John Halo', 'uploads/avatars/061c1ece8fa8dd3a31b269fe6b3ed134.jpg', '', ''),
(2, 2, 'hee hee', 'uploads/avatars/c6d938bd5c10346d86f15fd9ee903e1d.jpg', 'Wat da dog doin', 'les go deadlock vro');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `game_id` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workshop_creations`
--

DROP TABLE IF EXISTS `workshop_creations`;
CREATE TABLE IF NOT EXISTS `workshop_creations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `creator_id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `preview_image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archive_filename` varchar(100) DEFAULT NULL,
  `original_filename` varchar(180) DEFAULT NULL,
  `cover_filename` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workshop_creations`
--

INSERT INTO `workshop_creations` (`id`, `game_id`, `creator_id`, `title`, `description`, `preview_image_url`, `created_at`, `archive_filename`, `original_filename`, `cover_filename`) VALUES
(2, 18, 1, 'Somethingsomething on the wall', 'idk tbh', NULL, '2026-10-20 13:02:27', '437737cfe2ccf23913f8aface44db92c.zip', '[Guru3D]-DDU.zip', '981d72523e7a29e3df968eb980b91ef1.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `workshop_subscriptions`
--

DROP TABLE IF EXISTS `workshop_subscriptions`;
CREATE TABLE IF NOT EXISTS `workshop_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `creation_id` int NOT NULL,
  `subscribed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `creation_id` (`creation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workshop_subscriptions`
--

INSERT INTO `workshop_subscriptions` (`id`, `user_id`, `creation_id`, `subscribed_at`) VALUES
(2, 2, 2, '2026-10-20 13:03:59');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  ADD CONSTRAINT `fk_admin_audit_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `discussions`
--
ALTER TABLE `discussions`
  ADD CONSTRAINT `discussions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  ADD CONSTRAINT `fk_discussion_replies_discussion` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_discussion_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_items`
--
ALTER TABLE `game_items`
  ADD CONSTRAINT `game_items_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_tags`
--
ALTER TABLE `game_tags`
  ADD CONSTRAINT `game_tags_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventories`
--
ALTER TABLE `inventories`
  ADD CONSTRAINT `inventories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventories_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `game_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `library`
--
ALTER TABLE `library`
  ADD CONSTRAINT `library_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_listings`
--
ALTER TABLE `marketplace_listings`
  ADD CONSTRAINT `fk_marketplace_listing_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marketplace_listings_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_listings_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `game_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_reports`
--
ALTER TABLE `marketplace_reports`
  ADD CONSTRAINT `fk_marketplace_reports_listing` FOREIGN KEY (`listing_id`) REFERENCES `marketplace_listings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_marketplace_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_wishlists`
--
ALTER TABLE `marketplace_wishlists`
  ADD CONSTRAINT `fk_marketplace_wishlist_listing` FOREIGN KEY (`listing_id`) REFERENCES `marketplace_listings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_marketplace_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trade_requests`
--
ALTER TABLE `trade_requests`
  ADD CONSTRAINT `fk_trade_requests_listing` FOREIGN KEY (`listing_id`) REFERENCES `marketplace_listings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trade_requests_offer_inventory` FOREIGN KEY (`offered_inventory_id`) REFERENCES `inventories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trade_requests_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trade_requests_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trade_request_history`
--
ALTER TABLE `trade_request_history`
  ADD CONSTRAINT `fk_trade_history_request` FOREIGN KEY (`trade_request_id`) REFERENCES `trade_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_trade_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transaction_history`
--
ALTER TABLE `transaction_history`
  ADD CONSTRAINT `fk_transaction_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workshop_creations`
--
ALTER TABLE `workshop_creations`
  ADD CONSTRAINT `workshop_creations_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workshop_creations_ibfk_2` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workshop_subscriptions`
--
ALTER TABLE `workshop_subscriptions`
  ADD CONSTRAINT `workshop_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workshop_subscriptions_ibfk_2` FOREIGN KEY (`creation_id`) REFERENCES `workshop_creations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
