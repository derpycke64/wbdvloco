-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 20, 2026 at 04:14 AM
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
-- Table structure for table `discussions`
--

DROP TABLE IF EXISTS `discussions`;
CREATE TABLE IF NOT EXISTS `discussions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_listings`
--

DROP TABLE IF EXISTS `marketplace_listings`;
CREATE TABLE IF NOT EXISTS `marketplace_listings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `item_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `password` (`password`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `birthday`, `wallet_balance`, `created_at`) VALUES
(1, 'John Halo', 'wadelossantos0004val@student.fatima.edu.ph', '$2y$10$TIP5WXTK.sOJXnrB9/OmG.U/Zx6hg73QiIxmUcKGkyfG5ERle4blW', '1980-11-15', 0.00, '2026-10-19 16:58:10');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `display_name`, `avatar_url`, `bio`, `favorite_games`) VALUES
(1, 1, 'John Halo', 'images/default_avatar.png', NULL, NULL);

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
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `discussions`
--
ALTER TABLE `discussions`
  ADD CONSTRAINT `discussions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `marketplace_listings_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_listings_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `game_items` (`id`) ON DELETE CASCADE;

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
