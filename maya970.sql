-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost:3306
-- 生成日期： 2025-06-22 13:07:00
-- 服务器版本： 5.6.51
-- PHP 版本： 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `maya970`
--

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_building_masters`
--

CREATE TABLE `autorpg_building_masters` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `layer` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `settings` text COLLATE utf8mb4_unicode_ci,
  `appointed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_combats`
--

CREATE TABLE `autorpg_combats` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `attacker_id` int(11) DEFAULT NULL,
  `attacker_monster_id` int(11) DEFAULT NULL,
  `defender_id` int(11) NOT NULL,
  `distance` int(11) NOT NULL DEFAULT '16',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_dialogues`
--

CREATE TABLE `autorpg_dialogues` (
  `id` int(11) NOT NULL,
  `npc_id` int(11) NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_dialogue_options`
--

CREATE TABLE `autorpg_dialogue_options` (
  `id` int(11) NOT NULL,
  `dialogue_id` int(11) NOT NULL,
  `option_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `next_dialogue_id` int(11) DEFAULT NULL,
  `action_type` enum('task','shop','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `action_data` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_games`
--

CREATE TABLE `autorpg_games` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creator_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `autorpg_games`
--

INSERT INTO `autorpg_games` (`id`, `name`, `creator_id`, `created_at`) VALUES
(1, 'å†’é™©è€…å…¬ä¼š', 1, '2025-06-13 10:55:36');

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_ground_items`
--

CREATE TABLE `autorpg_ground_items` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `layer` int(11) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `placed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_items`
--

CREATE TABLE `autorpg_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('consumable','equipment','ground') COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `attributes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_layers`
--

CREATE TABLE `autorpg_layers` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `layer` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `autorpg_layers`
--

INSERT INTO `autorpg_layers` (`id`, `game_id`, `layer`, `created_at`) VALUES
(1, 1, 1, '2025-06-13 10:55:36'),
(2, 1, 2, '2025-06-19 12:25:31'),
(3, 1, 3, '2025-06-19 12:25:40');

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_map_tiles`
--

CREATE TABLE `autorpg_map_tiles` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `layer` int(11) NOT NULL DEFAULT '1',
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `img_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `terrain_type` enum('grass','door','city','safety_zone','monster') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'grass',
  `passable` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `autorpg_map_tiles`
--

INSERT INTO `autorpg_map_tiles` (`id`, `game_id`, `layer`, `x`, `y`, `img_url`, `terrain_type`, `passable`) VALUES
(1, 1, 1, 0, 0, '/assets/tiles/door.png', 'door', 1),
(2, 1, 1, 0, 1, '/assets/tiles/grass.png', 'grass', 1),
(3, 1, 1, 0, 2, '/assets/tiles/grass.png', 'grass', 1),
(4, 1, 1, 0, 3, '/assets/tiles/grass.png', 'grass', 1),
(5, 1, 1, 0, 4, '/assets/tiles/grass.png', 'grass', 1),
(6, 1, 1, 0, 5, '/assets/tiles/grass.png', 'grass', 1),
(7, 1, 1, 0, 6, '/assets/tiles/grass.png', 'grass', 1),
(8, 1, 1, 0, 7, '/assets/tiles/grass.png', 'grass', 1),
(9, 1, 1, 0, 8, '/assets/tiles/grass.png', 'grass', 1),
(10, 1, 1, 0, 9, '/assets/tiles/door.png', 'door', 1),
(11, 1, 1, 1, 0, '/assets/tiles/grass.png', 'grass', 1),
(12, 1, 1, 1, 1, '/assets/tiles/grass.png', 'grass', 1),
(13, 1, 1, 1, 2, '/assets/tiles/grass.png', 'grass', 1),
(14, 1, 1, 1, 3, '/assets/tiles/grass.png', 'grass', 1),
(15, 1, 1, 1, 4, '/assets/tiles/grass.png', 'grass', 1),
(16, 1, 1, 1, 5, '/assets/tiles/grass.png', 'grass', 1),
(17, 1, 1, 1, 6, '/assets/tiles/grass.png', 'grass', 1),
(18, 1, 1, 1, 7, '/assets/tiles/grass.png', 'grass', 1),
(19, 1, 1, 1, 8, '/assets/tiles/grass.png', 'grass', 1),
(20, 1, 1, 1, 9, '/assets/tiles/grass.png', 'grass', 1),
(21, 1, 1, 2, 0, '/assets/tiles/grass.png', 'grass', 1),
(22, 1, 1, 2, 1, '/assets/tiles/grass.png', 'grass', 1),
(23, 1, 1, 2, 2, '/assets/tiles/grass.png', 'grass', 1),
(24, 1, 1, 2, 3, '/assets/tiles/grass.png', 'grass', 1),
(25, 1, 1, 2, 4, '/assets/tiles/grass.png', 'grass', 1),
(26, 1, 1, 2, 5, '/assets/tiles/grass.png', 'grass', 1),
(27, 1, 1, 2, 6, '/assets/tiles/grass.png', 'grass', 1),
(28, 1, 1, 2, 7, '/assets/tiles/grass.png', 'grass', 1),
(29, 1, 1, 2, 8, '/assets/tiles/grass.png', 'grass', 1),
(30, 1, 1, 2, 9, '/assets/tiles/grass.png', 'grass', 1),
(31, 1, 1, 3, 0, '/assets/tiles/grass.png', 'grass', 1),
(32, 1, 1, 3, 1, '/assets/tiles/grass.png', 'grass', 1),
(33, 1, 1, 3, 2, '/assets/tiles/grass.png', 'grass', 1),
(34, 1, 1, 3, 3, '/assets/tiles/grass.png', 'grass', 1),
(35, 1, 1, 3, 4, '/assets/tiles/grass.png', 'grass', 1),
(36, 1, 1, 3, 5, '/assets/tiles/grass.png', 'grass', 1),
(37, 1, 1, 3, 6, '/assets/tiles/grass.png', 'grass', 1),
(38, 1, 1, 3, 7, '/assets/tiles/grass.png', 'grass', 1),
(39, 1, 1, 3, 8, '/assets/tiles/grass.png', 'grass', 1),
(40, 1, 1, 3, 9, '/assets/tiles/grass.png', 'grass', 1),
(41, 1, 1, 4, 0, '/assets/tiles/grass.png', 'grass', 1),
(42, 1, 1, 4, 1, '/assets/tiles/grass.png', 'grass', 1),
(43, 1, 1, 4, 2, '/assets/tiles/grass.png', 'grass', 1),
(44, 1, 1, 4, 3, '/assets/tiles/grass.png', 'grass', 1),
(45, 1, 1, 4, 4, '/assets/tiles/grass.png', 'grass', 1),
(46, 1, 1, 4, 5, '/assets/tiles/grass.png', 'grass', 1),
(47, 1, 1, 4, 6, '/assets/tiles/grass.png', 'grass', 1),
(48, 1, 1, 4, 7, '/assets/tiles/grass.png', 'grass', 1),
(49, 1, 1, 4, 8, '/assets/tiles/grass.png', 'grass', 1),
(50, 1, 1, 4, 9, '/assets/tiles/grass.png', 'grass', 1),
(51, 1, 1, 5, 0, '/assets/tiles/grass.png', 'grass', 1),
(52, 1, 1, 5, 1, '/assets/tiles/grass.png', 'grass', 1),
(53, 1, 1, 5, 2, '/assets/tiles/grass.png', 'grass', 1),
(54, 1, 1, 5, 3, '/assets/tiles/grass.png', 'grass', 1),
(55, 1, 1, 5, 4, '/assets/tiles/grass.png', 'grass', 1),
(56, 1, 1, 5, 5, '/assets/tiles/grass.png', 'grass', 1),
(57, 1, 1, 5, 6, '/assets/tiles/grass.png', 'grass', 1),
(58, 1, 1, 5, 7, '/assets/tiles/grass.png', 'grass', 1),
(59, 1, 1, 5, 8, '/assets/tiles/grass.png', 'grass', 1),
(60, 1, 1, 5, 9, '/assets/tiles/grass.png', 'grass', 1),
(61, 1, 1, 6, 0, '/assets/tiles/grass.png', 'grass', 1),
(62, 1, 1, 6, 1, '/assets/tiles/grass.png', 'grass', 1),
(63, 1, 1, 6, 2, '/assets/tiles/grass.png', 'grass', 1),
(64, 1, 1, 6, 3, '/assets/tiles/grass.png', 'grass', 1),
(65, 1, 1, 6, 4, '/assets/tiles/grass.png', 'grass', 1),
(66, 1, 1, 6, 5, '/assets/tiles/grass.png', 'grass', 1),
(67, 1, 1, 6, 6, '/assets/tiles/grass.png', 'grass', 1),
(68, 1, 1, 6, 7, '/assets/tiles/grass.png', 'grass', 1),
(69, 1, 1, 6, 8, '/assets/tiles/grass.png', 'grass', 1),
(70, 1, 1, 6, 9, '/assets/tiles/grass.png', 'grass', 1),
(71, 1, 1, 7, 0, '/assets/tiles/grass.png', 'grass', 1),
(72, 1, 1, 7, 1, '/assets/tiles/grass.png', 'grass', 1),
(73, 1, 1, 7, 2, '/assets/tiles/grass.png', 'grass', 1),
(74, 1, 1, 7, 3, '/assets/tiles/grass.png', 'grass', 1),
(75, 1, 1, 7, 4, '/assets/tiles/grass.png', 'grass', 1),
(76, 1, 1, 7, 5, '/assets/tiles/grass.png', 'grass', 1),
(77, 1, 1, 7, 6, '/assets/tiles/grass.png', 'grass', 1),
(78, 1, 1, 7, 7, '/assets/tiles/grass.png', 'grass', 1),
(79, 1, 1, 7, 8, '/assets/tiles/grass.png', 'grass', 1),
(80, 1, 1, 7, 9, '/assets/tiles/grass.png', 'grass', 1),
(81, 1, 1, 8, 0, '/assets/tiles/grass.png', 'grass', 1),
(82, 1, 1, 8, 1, '/assets/tiles/grass.png', 'grass', 1),
(83, 1, 1, 8, 2, '/assets/tiles/grass.png', 'grass', 1),
(84, 1, 1, 8, 3, '/assets/tiles/grass.png', 'grass', 1),
(85, 1, 1, 8, 4, '/assets/tiles/grass.png', 'grass', 1),
(86, 1, 1, 8, 5, '/assets/tiles/grass.png', 'grass', 1),
(87, 1, 1, 8, 6, '/assets/tiles/grass.png', 'grass', 1),
(88, 1, 1, 8, 7, '/assets/tiles/grass.png', 'grass', 1),
(89, 1, 1, 8, 8, '/assets/tiles/grass.png', 'grass', 1),
(90, 1, 1, 8, 9, '/assets/tiles/grass.png', 'grass', 1),
(91, 1, 1, 9, 0, '/assets/tiles/door.png', 'door', 1),
(92, 1, 1, 9, 1, '/assets/tiles/grass.png', 'grass', 1),
(93, 1, 1, 9, 2, '/assets/tiles/grass.png', 'grass', 1),
(94, 1, 1, 9, 3, '/assets/tiles/grass.png', 'grass', 1),
(95, 1, 1, 9, 4, '/assets/tiles/grass.png', 'grass', 1),
(96, 1, 1, 9, 5, '/assets/tiles/grass.png', 'grass', 1),
(97, 1, 1, 9, 6, '/assets/tiles/grass.png', 'grass', 1),
(98, 1, 1, 9, 7, '/assets/tiles/grass.png', 'grass', 1),
(99, 1, 1, 9, 8, '/assets/tiles/grass.png', 'grass', 1),
(100, 1, 1, 9, 9, '/assets/tiles/door.png', 'door', 1),
(101, 1, 2, 0, 0, '/assets/tiles/door.png', 'door', 1),
(102, 1, 2, 0, 1, '/assets/tiles/grass.png', 'grass', 1),
(103, 1, 2, 0, 2, '/assets/tiles/grass.png', 'grass', 1),
(104, 1, 2, 0, 3, '/assets/tiles/grass.png', 'grass', 1),
(105, 1, 2, 0, 4, '/assets/tiles/grass.png', 'grass', 1),
(106, 1, 2, 0, 5, '/assets/tiles/grass.png', 'grass', 1),
(107, 1, 2, 0, 6, '/assets/tiles/grass.png', 'grass', 1),
(108, 1, 2, 0, 7, '/assets/tiles/grass.png', 'grass', 1),
(109, 1, 2, 0, 8, '/assets/tiles/grass.png', 'grass', 1),
(110, 1, 2, 0, 9, '/assets/tiles/door.png', 'door', 1),
(111, 1, 2, 1, 0, '/assets/tiles/grass.png', 'grass', 1),
(112, 1, 2, 1, 1, '/assets/tiles/grass.png', 'grass', 1),
(113, 1, 2, 1, 2, '/assets/tiles/grass.png', 'grass', 1),
(114, 1, 2, 1, 3, '/assets/tiles/grass.png', 'grass', 1),
(115, 1, 2, 1, 4, '/assets/tiles/grass.png', 'grass', 1),
(116, 1, 2, 1, 5, '/assets/tiles/grass.png', 'grass', 1),
(117, 1, 2, 1, 6, '/assets/tiles/grass.png', 'grass', 1),
(118, 1, 2, 1, 7, '/assets/tiles/grass.png', 'grass', 1),
(119, 1, 2, 1, 8, '/assets/tiles/grass.png', 'grass', 1),
(120, 1, 2, 1, 9, '/assets/tiles/grass.png', 'grass', 1),
(121, 1, 2, 2, 0, '/assets/tiles/grass.png', 'grass', 1),
(122, 1, 2, 2, 1, '/assets/tiles/grass.png', 'grass', 1),
(123, 1, 2, 2, 2, '/assets/tiles/grass.png', 'grass', 1),
(124, 1, 2, 2, 3, '/assets/tiles/grass.png', 'grass', 1),
(125, 1, 2, 2, 4, '/assets/tiles/grass.png', 'grass', 1),
(126, 1, 2, 2, 5, '/assets/tiles/grass.png', 'grass', 1),
(127, 1, 2, 2, 6, '/assets/tiles/grass.png', 'grass', 1),
(128, 1, 2, 2, 7, '/assets/tiles/grass.png', 'grass', 1),
(129, 1, 2, 2, 8, '/assets/tiles/grass.png', 'grass', 1),
(130, 1, 2, 2, 9, '/assets/tiles/grass.png', 'grass', 1),
(131, 1, 2, 3, 0, '/assets/tiles/grass.png', 'grass', 1),
(132, 1, 2, 3, 1, '/assets/tiles/grass.png', 'grass', 1),
(133, 1, 2, 3, 2, '/assets/tiles/grass.png', 'grass', 1),
(134, 1, 2, 3, 3, '/assets/tiles/grass.png', 'grass', 1),
(135, 1, 2, 3, 4, '/assets/tiles/grass.png', 'grass', 1),
(136, 1, 2, 3, 5, '/assets/tiles/grass.png', 'grass', 1),
(137, 1, 2, 3, 6, '/assets/tiles/grass.png', 'grass', 1),
(138, 1, 2, 3, 7, '/assets/tiles/grass.png', 'grass', 1),
(139, 1, 2, 3, 8, '/assets/tiles/grass.png', 'grass', 1),
(140, 1, 2, 3, 9, '/assets/tiles/grass.png', 'grass', 1),
(141, 1, 2, 4, 0, '/assets/tiles/grass.png', 'grass', 1),
(142, 1, 2, 4, 1, '/assets/tiles/grass.png', 'grass', 1),
(143, 1, 2, 4, 2, '/assets/tiles/grass.png', 'grass', 1),
(144, 1, 2, 4, 3, '/assets/tiles/grass.png', 'grass', 1),
(145, 1, 2, 4, 4, '/assets/tiles/grass.png', 'grass', 1),
(146, 1, 2, 4, 5, '/assets/tiles/grass.png', 'grass', 1),
(147, 1, 2, 4, 6, '/assets/tiles/grass.png', 'grass', 1),
(148, 1, 2, 4, 7, '/assets/tiles/grass.png', 'grass', 1),
(149, 1, 2, 4, 8, '/assets/tiles/grass.png', 'grass', 1),
(150, 1, 2, 4, 9, '/assets/tiles/grass.png', 'grass', 1),
(151, 1, 2, 5, 0, '/assets/tiles/grass.png', 'grass', 1),
(152, 1, 2, 5, 1, '/assets/tiles/grass.png', 'grass', 1),
(153, 1, 2, 5, 2, '/assets/tiles/grass.png', 'grass', 1),
(154, 1, 2, 5, 3, '/assets/tiles/grass.png', 'grass', 1),
(155, 1, 2, 5, 4, '/assets/tiles/grass.png', 'grass', 1),
(156, 1, 2, 5, 5, '/assets/tiles/grass.png', 'grass', 1),
(157, 1, 2, 5, 6, '/assets/tiles/grass.png', 'grass', 1),
(158, 1, 2, 5, 7, '/assets/tiles/grass.png', 'grass', 1),
(159, 1, 2, 5, 8, '/assets/tiles/grass.png', 'grass', 1),
(160, 1, 2, 5, 9, '/assets/tiles/grass.png', 'grass', 1),
(161, 1, 2, 6, 0, '/assets/tiles/grass.png', 'grass', 1),
(162, 1, 2, 6, 1, '/assets/tiles/grass.png', 'grass', 1),
(163, 1, 2, 6, 2, '/assets/tiles/grass.png', 'grass', 1),
(164, 1, 2, 6, 3, '/assets/tiles/grass.png', 'grass', 1),
(165, 1, 2, 6, 4, '/assets/tiles/grass.png', 'grass', 1),
(166, 1, 2, 6, 5, '/assets/tiles/grass.png', 'grass', 1),
(167, 1, 2, 6, 6, '/assets/tiles/grass.png', 'grass', 1),
(168, 1, 2, 6, 7, '/assets/tiles/grass.png', 'grass', 1),
(169, 1, 2, 6, 8, '/assets/tiles/grass.png', 'grass', 1),
(170, 1, 2, 6, 9, '/assets/tiles/grass.png', 'grass', 1),
(171, 1, 2, 7, 0, '/assets/tiles/grass.png', 'grass', 1),
(172, 1, 2, 7, 1, '/assets/tiles/grass.png', 'grass', 1),
(173, 1, 2, 7, 2, '/assets/tiles/grass.png', 'grass', 1),
(174, 1, 2, 7, 3, '/assets/tiles/grass.png', 'grass', 1),
(175, 1, 2, 7, 4, '/assets/tiles/grass.png', 'grass', 1),
(176, 1, 2, 7, 5, '/assets/tiles/grass.png', 'grass', 1),
(177, 1, 2, 7, 6, '/assets/tiles/grass.png', 'grass', 1),
(178, 1, 2, 7, 7, '/assets/tiles/grass.png', 'grass', 1),
(179, 1, 2, 7, 8, '/assets/tiles/grass.png', 'grass', 1),
(180, 1, 2, 7, 9, '/assets/tiles/grass.png', 'grass', 1),
(181, 1, 2, 8, 0, '/assets/tiles/grass.png', 'grass', 1),
(182, 1, 2, 8, 1, '/assets/tiles/grass.png', 'grass', 1),
(183, 1, 2, 8, 2, '/assets/tiles/grass.png', 'grass', 1),
(184, 1, 2, 8, 3, '/assets/tiles/grass.png', 'grass', 1),
(185, 1, 2, 8, 4, '/assets/tiles/grass.png', 'grass', 1),
(186, 1, 2, 8, 5, '/assets/tiles/grass.png', 'grass', 1),
(187, 1, 2, 8, 6, '/assets/tiles/grass.png', 'grass', 1),
(188, 1, 2, 8, 7, '/assets/tiles/grass.png', 'grass', 1),
(189, 1, 2, 8, 8, '/assets/tiles/grass.png', 'grass', 1),
(190, 1, 2, 8, 9, '/assets/tiles/grass.png', 'grass', 1),
(191, 1, 2, 9, 0, '/assets/tiles/door.png', 'door', 1),
(192, 1, 2, 9, 1, '/assets/tiles/grass.png', 'grass', 1),
(193, 1, 2, 9, 2, '/assets/tiles/grass.png', 'grass', 1),
(194, 1, 2, 9, 3, '/assets/tiles/grass.png', 'grass', 1),
(195, 1, 2, 9, 4, '/assets/tiles/grass.png', 'grass', 1),
(196, 1, 2, 9, 5, '/assets/tiles/grass.png', 'grass', 1),
(197, 1, 2, 9, 6, '/assets/tiles/grass.png', 'grass', 1),
(198, 1, 2, 9, 7, '/assets/tiles/grass.png', 'grass', 1),
(199, 1, 2, 9, 8, '/assets/tiles/grass.png', 'grass', 1),
(200, 1, 2, 9, 9, '/assets/tiles/door.png', 'door', 1),
(201, 1, 3, 0, 0, '/assets/tiles/door.png', 'door', 1),
(202, 1, 3, 0, 1, '/assets/tiles/grass.png', 'grass', 1),
(203, 1, 3, 0, 2, '/assets/tiles/grass.png', 'grass', 1),
(204, 1, 3, 0, 3, '/assets/tiles/grass.png', 'grass', 1),
(205, 1, 3, 0, 4, '/assets/tiles/grass.png', 'grass', 1),
(206, 1, 3, 0, 5, '/assets/tiles/grass.png', 'grass', 1),
(207, 1, 3, 0, 6, '/assets/tiles/grass.png', 'grass', 1),
(208, 1, 3, 0, 7, '/assets/tiles/grass.png', 'grass', 1),
(209, 1, 3, 0, 8, '/assets/tiles/grass.png', 'grass', 1),
(210, 1, 3, 0, 9, '/assets/tiles/door.png', 'door', 1),
(211, 1, 3, 1, 0, '/assets/tiles/grass.png', 'grass', 1),
(212, 1, 3, 1, 1, '/assets/tiles/grass.png', 'grass', 1),
(213, 1, 3, 1, 2, '/assets/tiles/grass.png', 'grass', 1),
(214, 1, 3, 1, 3, '/assets/tiles/grass.png', 'grass', 1),
(215, 1, 3, 1, 4, '/assets/tiles/grass.png', 'grass', 1),
(216, 1, 3, 1, 5, '/assets/tiles/grass.png', 'grass', 1),
(217, 1, 3, 1, 6, '/assets/tiles/grass.png', 'grass', 1),
(218, 1, 3, 1, 7, '/assets/tiles/grass.png', 'grass', 1),
(219, 1, 3, 1, 8, '/assets/tiles/grass.png', 'grass', 1),
(220, 1, 3, 1, 9, '/assets/tiles/grass.png', 'grass', 1),
(221, 1, 3, 2, 0, '/assets/tiles/grass.png', 'grass', 1),
(222, 1, 3, 2, 1, '/assets/tiles/grass.png', 'grass', 1),
(223, 1, 3, 2, 2, '/assets/tiles/grass.png', 'grass', 1),
(224, 1, 3, 2, 3, '/assets/tiles/grass.png', 'grass', 1),
(225, 1, 3, 2, 4, '/assets/tiles/grass.png', 'grass', 1),
(226, 1, 3, 2, 5, '/assets/tiles/grass.png', 'grass', 1),
(227, 1, 3, 2, 6, '/assets/tiles/grass.png', 'grass', 1),
(228, 1, 3, 2, 7, '/assets/tiles/grass.png', 'grass', 1),
(229, 1, 3, 2, 8, '/assets/tiles/grass.png', 'grass', 1),
(230, 1, 3, 2, 9, '/assets/tiles/grass.png', 'grass', 1),
(231, 1, 3, 3, 0, '/assets/tiles/grass.png', 'grass', 1),
(232, 1, 3, 3, 1, '/assets/tiles/grass.png', 'grass', 1),
(233, 1, 3, 3, 2, '/assets/tiles/grass.png', 'grass', 1),
(234, 1, 3, 3, 3, '/assets/tiles/grass.png', 'grass', 1),
(235, 1, 3, 3, 4, '/assets/tiles/grass.png', 'grass', 1),
(236, 1, 3, 3, 5, '/assets/tiles/grass.png', 'grass', 1),
(237, 1, 3, 3, 6, '/assets/tiles/grass.png', 'grass', 1),
(238, 1, 3, 3, 7, '/assets/tiles/grass.png', 'grass', 1),
(239, 1, 3, 3, 8, '/assets/tiles/grass.png', 'grass', 1),
(240, 1, 3, 3, 9, '/assets/tiles/grass.png', 'grass', 1),
(241, 1, 3, 4, 0, '/assets/tiles/grass.png', 'grass', 1),
(242, 1, 3, 4, 1, '/assets/tiles/grass.png', 'grass', 1),
(243, 1, 3, 4, 2, '/assets/tiles/grass.png', 'grass', 1),
(244, 1, 3, 4, 3, '/assets/tiles/grass.png', 'grass', 1),
(245, 1, 3, 4, 4, '/assets/tiles/grass.png', 'grass', 1),
(246, 1, 3, 4, 5, '/assets/tiles/grass.png', 'grass', 1),
(247, 1, 3, 4, 6, '/assets/tiles/grass.png', 'grass', 1),
(248, 1, 3, 4, 7, '/assets/tiles/grass.png', 'grass', 1),
(249, 1, 3, 4, 8, '/assets/tiles/grass.png', 'grass', 1),
(250, 1, 3, 4, 9, '/assets/tiles/grass.png', 'grass', 1),
(251, 1, 3, 5, 0, '/assets/tiles/grass.png', 'grass', 1),
(252, 1, 3, 5, 1, '/assets/tiles/grass.png', 'grass', 1),
(253, 1, 3, 5, 2, '/assets/tiles/grass.png', 'grass', 1),
(254, 1, 3, 5, 3, '/assets/tiles/grass.png', 'grass', 1),
(255, 1, 3, 5, 4, '/assets/tiles/grass.png', 'grass', 1),
(256, 1, 3, 5, 5, '/assets/tiles/grass.png', 'grass', 1),
(257, 1, 3, 5, 6, '/assets/tiles/grass.png', 'grass', 1),
(258, 1, 3, 5, 7, '/assets/tiles/grass.png', 'grass', 1),
(259, 1, 3, 5, 8, '/assets/tiles/grass.png', 'grass', 1),
(260, 1, 3, 5, 9, '/assets/tiles/grass.png', 'grass', 1),
(261, 1, 3, 6, 0, '/assets/tiles/grass.png', 'grass', 1),
(262, 1, 3, 6, 1, '/assets/tiles/grass.png', 'grass', 1),
(263, 1, 3, 6, 2, '/assets/tiles/grass.png', 'grass', 1),
(264, 1, 3, 6, 3, '/assets/tiles/grass.png', 'grass', 1),
(265, 1, 3, 6, 4, '/assets/tiles/grass.png', 'grass', 1),
(266, 1, 3, 6, 5, '/assets/tiles/grass.png', 'grass', 1),
(267, 1, 3, 6, 6, '/assets/tiles/grass.png', 'grass', 1),
(268, 1, 3, 6, 7, '/assets/tiles/grass.png', 'grass', 1),
(269, 1, 3, 6, 8, '/assets/tiles/grass.png', 'grass', 1),
(270, 1, 3, 6, 9, '/assets/tiles/grass.png', 'grass', 1),
(271, 1, 3, 7, 0, '/assets/tiles/grass.png', 'grass', 1),
(272, 1, 3, 7, 1, '/assets/tiles/grass.png', 'grass', 1),
(273, 1, 3, 7, 2, '/assets/tiles/grass.png', 'grass', 1),
(274, 1, 3, 7, 3, '/assets/tiles/grass.png', 'grass', 1),
(275, 1, 3, 7, 4, '/assets/tiles/grass.png', 'grass', 1),
(276, 1, 3, 7, 5, '/assets/tiles/grass.png', 'grass', 1),
(277, 1, 3, 7, 6, '/assets/tiles/grass.png', 'grass', 1),
(278, 1, 3, 7, 7, '/assets/tiles/grass.png', 'grass', 1),
(279, 1, 3, 7, 8, '/assets/tiles/grass.png', 'grass', 1),
(280, 1, 3, 7, 9, '/assets/tiles/grass.png', 'grass', 1),
(281, 1, 3, 8, 0, '/assets/tiles/grass.png', 'grass', 1),
(282, 1, 3, 8, 1, '/assets/tiles/grass.png', 'grass', 1),
(283, 1, 3, 8, 2, '/assets/tiles/grass.png', 'grass', 1),
(284, 1, 3, 8, 3, '/assets/tiles/grass.png', 'grass', 1),
(285, 1, 3, 8, 4, '/assets/tiles/grass.png', 'grass', 1),
(286, 1, 3, 8, 5, '/assets/tiles/grass.png', 'grass', 1),
(287, 1, 3, 8, 6, '/assets/tiles/grass.png', 'grass', 1),
(288, 1, 3, 8, 7, '/assets/tiles/grass.png', 'grass', 1),
(289, 1, 3, 8, 8, '/assets/tiles/grass.png', 'grass', 1),
(290, 1, 3, 8, 9, '/assets/tiles/grass.png', 'grass', 1),
(291, 1, 3, 9, 0, '/assets/tiles/door.png', 'door', 1),
(292, 1, 3, 9, 1, '/assets/tiles/grass.png', 'grass', 1),
(293, 1, 3, 9, 2, '/assets/tiles/grass.png', 'grass', 1),
(294, 1, 3, 9, 3, '/assets/tiles/grass.png', 'grass', 1),
(295, 1, 3, 9, 4, '/assets/tiles/grass.png', 'grass', 1),
(296, 1, 3, 9, 5, '/assets/tiles/grass.png', 'grass', 1),
(297, 1, 3, 9, 6, '/assets/tiles/grass.png', 'grass', 1),
(298, 1, 3, 9, 7, '/assets/tiles/grass.png', 'grass', 1),
(299, 1, 3, 9, 8, '/assets/tiles/grass.png', 'grass', 1),
(300, 1, 3, 9, 9, '/assets/tiles/door.png', 'door', 1);

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_monsters`
--

CREATE TABLE `autorpg_monsters` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `health` int(11) NOT NULL,
  `speed` int(11) NOT NULL,
  `attack_range` int(11) NOT NULL DEFAULT '0',
  `terrain_type` enum('monster') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_monster_drops`
--

CREATE TABLE `autorpg_monster_drops` (
  `id` int(11) NOT NULL,
  `monster_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `drop_chance` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_monster_suppression`
--

CREATE TABLE `autorpg_monster_suppression` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `layer` int(11) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_players`
--

CREATE TABLE `autorpg_players` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `layer` int(11) NOT NULL DEFAULT '1',
  `moves_left` int(11) NOT NULL DEFAULT '3',
  `health` int(11) NOT NULL DEFAULT '100',
  `stamina` int(11) NOT NULL DEFAULT '100',
  `move_count` int(11) NOT NULL DEFAULT '0',
  `last_move_hour` timestamp NULL DEFAULT NULL,
  `action_count` int(11) NOT NULL DEFAULT '0',
  `speed` int(11) NOT NULL DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `autorpg_players`
--

INSERT INTO `autorpg_players` (`id`, `game_id`, `user_id`, `x`, `y`, `layer`, `moves_left`, `health`, `stamina`, `move_count`, `last_move_hour`, `action_count`, `speed`) VALUES
(1, 1, 1, 2, 4, 1, 3, 100, 1, 75, '0000-00-00 00:00:00', 75, 10),
(2, 1, 13, 7, 2, 1, 3, 100, 100, 0, NULL, 0, 10),
(3, 1, 15, 5, 7, 1, 3, 100, 100, 0, NULL, 0, 10),
(4, 1, 16, 5, 5, 3, 3, 100, 1, 75, '0000-00-00 00:00:00', 75, 10),
(5, 1, 17, 8, 6, 1, 3, 100, 100, 0, NULL, 0, 10);

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_player_inventory`
--

CREATE TABLE `autorpg_player_inventory` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `slot` int(11) NOT NULL,
  `is_equipped` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_player_skills`
--

CREATE TABLE `autorpg_player_skills` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `slot` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_skills`
--

CREATE TABLE `autorpg_skills` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `attack_range` int(11) NOT NULL,
  `damage` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `autorpg_synthesis_recipes`
--

CREATE TABLE `autorpg_synthesis_recipes` (
  `id` int(11) NOT NULL,
  `item1_id` int(11) NOT NULL,
  `item2_id` int(11) NOT NULL,
  `result_item_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `x` int(11) DEFAULT NULL,
  `y` int(11) DEFAULT NULL,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#0000FF',
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `population` int(11) DEFAULT NULL,
  `resources` int(11) DEFAULT NULL,
  `growth_rate` float DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `city_display_type` enum('circle','image','text','none') COLLATE utf8mb4_unicode_ci DEFAULT 'circle',
  `city_display_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `economy` int(11) DEFAULT '0',
  `military` int(11) DEFAULT '0',
  `military_growth` decimal(5,2) DEFAULT '0.00',
  `culture` int(11) DEFAULT '0',
  `culture_growth` decimal(5,2) DEFAULT '0.00',
  `science` int(11) DEFAULT '0',
  `science_growth` decimal(5,2) DEFAULT '0.00',
  `infrastructure` int(11) DEFAULT '0',
  `infrastructure_growth` decimal(5,2) DEFAULT '0.00',
  `health` int(11) DEFAULT '0',
  `health_growth` decimal(5,2) DEFAULT '0.00',
  `education` int(11) DEFAULT '0',
  `education_growth` decimal(5,2) DEFAULT '0.00',
  `stability` int(11) DEFAULT '0',
  `stability_growth` decimal(5,2) DEFAULT '0.00',
  `value9` int(11) DEFAULT '0',
  `growth_rate9` decimal(5,2) DEFAULT '0.00',
  `show_name` tinyint(1) DEFAULT '1',
  `type` enum('city','mountain','forest','ocean') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'city',
  `food_consumption` decimal(10,2) DEFAULT '0.00',
  `money_consumption` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `cities`
--

INSERT INTO `cities` (`id`, `game_id`, `name`, `x`, `y`, `color`, `description`, `population`, `resources`, `growth_rate`, `updated_at`, `city_display_type`, `city_display_value`, `economy`, `military`, `military_growth`, `culture`, `culture_growth`, `science`, `science_growth`, `infrastructure`, `infrastructure_growth`, `health`, `health_growth`, `education`, `education_growth`, `stability`, `stability_growth`, `value9`, `growth_rate9`, `show_name`, `type`, `food_consumption`, `money_consumption`) VALUES
(1, 1, 'éƒ½æŸæž—', 255, 380, '#00FFFF', 'éƒ½æŸæž—çš„åŽ†å²å¯ä»¥è¿½æº¯åˆ°å…¬å…ƒ140å¹´ï¼Œå½“æ—¶çš„å¸Œè…Šå¤©æ–‡å­¦å®¶æ‰˜å‹’å¯†å°†å…¶ç§°ä¸ºEblana Civitasã€‚åŸŽå¸‚çš„åå­—â€œDublinâ€æ¥æºäºŽçˆ±å°”å…°è¯­â€œDubh Linnâ€ï¼Œæ„ä¸ºâ€œé»‘æ± â€ã€‚', 30997, 0, 8.51, '2025-06-05 13:21:39', 'text', 'â–§éƒ½æŸæž—', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 'city', 0.00, 0.00),
(7, 1, 'æ¢…æ–¯å›¾', 235, 405, '#000000', '424', 15698, 0, 17.25, '2025-05-28 13:00:48', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(706, 1, 'å‡¯å¸Œäºš', 185, 405, '#000000', '', 40834, 0, 17.25, '2025-05-28 12:52:19', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(707, 1, 'å¡å®', 185, 435, '#000000', '', 30899, 0, 8.54, '2025-05-28 12:56:02', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(708, 1, 'ç§‘å…‹', 195, 475, '#000000', '', 36811, 0, 12.43, '2025-06-06 00:14:36', 'text', 'â–§ç§‘å…‹', 1440, 0, 0.00, 4286, 0.00, 8669, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(709, 1, 'ææ¢…å°”', 185, 365, '#000000', '', 15698, 0, 17.25, '2025-05-28 13:02:16', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(710, 1, 'æœèµ›ç‰¹å°”', 255, 300, '#00FFFF', '', 21457, 0, 12.5, '2025-05-28 13:03:48', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(711, 1, 'åŸƒé‡Œå¥‡', 175, 280, '#A52A2A', '', 14221, 0, 20.58, '2025-05-28 13:07:05', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(712, 1, 'å…‹é²äº¨', 120, 335, '#A52A2A', '', 32801, 0, 18.3, '2025-05-28 13:06:41', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(713, 1, 'ä¼¦æ•¦', 500, 500, '#0000FF', '', 21000, 0, 0, '2025-06-05 07:04:00', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(714, 1, 'ç‰›æ´¥', 475, 485, '#FFA500', '', 11561, 0, 0, '2025-06-05 07:04:22', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(715, 1, 'åŸƒå¡žå…‹æ–¯', 525, 472, '#0000FF', '', 11487, 0, 0, '2025-06-05 07:04:51', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(716, 1, 'ç½—æ›¼è¦å¡ž', 500, 525, '#0000FF', '', 14789, 0, 0, '2025-06-05 07:07:18', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(717, 1, 'åŽç‰¹ä¼¯é›·', 550, 525, '#0000FF', '', 22671, 0, 0, '2025-06-05 07:07:47', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(719, 1, 'è¨å¡žåˆ©æ–¯', 529, 542, '#0000FF', '', 13114, 0, 0, '2025-06-05 07:08:04', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(720, 1, 'æ‰˜è—¤', 400, 400, '#FF0000', '', 1478, 0, 0, '2025-06-05 07:08:31', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(721, 1, 'å¤šæ©è¦å¡ž', 448, 364, '#FF0000', 'å¤šæ©è¦å¡žå»ºç­‘åœ¨ç¾¤å±±ä¹‹é—´ï¼Œå¤§å†›å¦‚æžœæƒ³ç©¿è¿‡ç¾¤å±±ï¼Œå°±æ— æ³•è·³è¿‡æ­¤è¦å¡žã€‚', 11459, 0, 0, '2025-06-05 07:09:09', 'text', 'â–§å¤šæ©è¦å¡ž', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(722, 1, 'è¯ºä¸æ±‰', 425, 450, '#0000FF', '', 5704, 0, 0, '2025-06-05 07:18:38', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(723, 1, 'æ²¼æ³½', 488, 384, '#FF0000', 'æ­¤å¤„æ˜¯æ²¼æ³½ï¼Œé™†å†›ä¸å¯é€šè¡Œæ­¤å¤„ï¼Œä¹Ÿä¸å¯åœ¨æ­¤è¿›è¡Œæ»©æ¶‚ç™»é™†è¡ŒåŠ¨', NULL, 1, NULL, '2025-05-19 07:59:41', 'text', 'Ã—Ã—Ã—', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, 1, 'ocean', 0.00, 0.00),
(724, 1, 'æœå¨', 555, 425, '#0000FF', '', 5157, 0, 53.42, '2025-06-05 07:19:29', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(725, 1, 'çº¦å…‹', 438, 324, '#FF0000', '', 3012, 0, 0, '2025-06-05 07:10:07', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(726, 1, 'ç±³éƒ½æ–¯å ¡', 468, 308, '#FF0000', '', 26615, 0, 0, '2025-06-05 07:10:54', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(727, 1, 'è´ç­å ¡', 438, 278, '#FF0000', '', 8756, 0, 0, '2025-06-05 07:11:10', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(728, 1, 'å“ˆå¾·è‰¯é•¿åŸŽ', 408, 268, '#FF0000', 'å“ˆå¾·è‰¯é•¿åŸŽæ— æ³•é€¾è¶Šï¼Œå¿…é¡»æ”»å…‹è´ç­å ¡æˆ–è€…èŽ±æ¡‚å®å ¡ï¼Œæ‰èƒ½é€šè¿‡', NULL, NULL, NULL, '2025-05-19 08:58:49', 'text', 'â”Œâ”â”Œâ”â”Œâ”â”Œâ”', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, 1, 'ocean', 0.00, 0.00),
(729, 1, 'è‰¾å°”èŠ¬', 415, 228, '#FF0000', '', 14798, 0, 0, '2025-06-05 07:11:35', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(730, 1, 'å®‰ä¸œå°¼é•¿åŸŽ', 310, 198, '#00ff00', '', NULL, NULL, NULL, '2025-05-19 08:59:12', 'text', 'â”Œâ”â”Œâ”â”Œâ”', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, 1, 'ocean', 0.00, 0.00),
(731, 1, 'ç»´å¦è¦å¡ž', 450, 542, '#FFA500', '', 15776, 0, 0, '2025-06-05 07:12:20', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(732, 1, 'åˆ‡æœ¬å“ˆæ ¹', 400, 525, '#FFA500', '', 18987, 0, 0, '2025-06-05 07:12:45', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(733, 1, 'é”¡ä¼¦è¦å¡ž', 410, 500, '#FFA500', '', 14789, 0, 0, '2025-06-05 07:13:13', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(734, 1, 'æˆ´å¤«å ¡', 350, 500, '#FFA500', '', 10069, 0, 0, '2025-06-05 07:13:47', 'text', 'â˜ ', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(735, 1, 'åšå¾·æ˜Ž', 350, 545, '#FFA500', '', 21006, 0, 0, '2025-06-05 07:14:23', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(736, 1, 'å›¾å®‰è¦å¡ž', 310, 570, '#FFA500', '', 6446, 0, 0, '2025-06-05 07:14:37', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(737, 1, 'å®‰æ ¼å°”è¥¿', 325, 405, '#FFA500', '', 4589, 0, 0, '2025-06-05 07:14:49', 'text', 'â˜ ', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(738, 1, 'æ‹œå¡çµ', 350, 470, '#FFA500', '', 10985, 0, 0, '2025-06-05 07:15:02', 'text', 'â—‘', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(739, 1, 'é©¬å¨å ¡', 350, 430, '#FFA500', '', 9898, 0, 0, '2025-06-05 07:15:16', 'text', 'â—‘', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(740, 1, 'æ¢…è€¶è¦å¡ž', 390, 324, '#FF0000', '', 5479, 0, 0, '2025-06-05 23:55:05', 'text', 'â¤', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(741, 1, 'èŽ±æ¡‚å®å ¡', 375, 278, '#FF0000', '', 14788, 0, 0, '2025-06-05 23:54:52', 'text', 'â¤', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(742, 1, 'é›·å¾·è¦å¡ž', 300, 268, '#FF0000', '', 11125, 0, 0, '2025-06-05 23:54:34', 'text', 'â¤', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(743, 1, 'å¡æ‹‰é“å ¡', 320, 228, '#FF0000', '', 6114, 0, 0, '2025-06-05 23:54:16', 'text', 'â¤', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(744, 1, 'çˆ±ä¸å ¡', 342, 170, '#00ff00', '', 8685, 0, 0, '2025-06-05 07:17:04', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(745, 1, 'æ•¦å·´é¡¿', 280, 170, '#00ff00', '', 8592, 0, 0, '2025-06-05 07:17:17', 'circle', '', 0, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(746, 1, 'æ–¯æ˜†', 310, 140, '#00ff00', '', 6987, NULL, NULL, '2025-06-21 15:07:57', 'circle', '', NULL, NULL, 0.00, NULL, 0.00, NULL, 0.00, NULL, 0.00, NULL, 0.00, NULL, 0.00, NULL, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(747, 1, 'ä¼¦æ•¦æ¡¥', 500, 515, '#0000FF', '', NULL, NULL, NULL, '2025-05-19 14:29:43', 'text', 'â€–', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, 1, 'ocean', 0.00, 0.00),
(752, 2, 'æµ‹è¯•', 200, 200, '#0000FF', '', 112, 112, 5, '2025-06-15 15:15:02', 'circle', '', 517, 1, 2.00, 1, 2.00, 1, 2.00, 48, 700.00, 1, 8.00, 1, 2.00, 1, 2.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(763, 15, 'åŸŽå¸‚ A', 100, 100, '#0000FF', 'ç¹è£çš„åŸŽå¸‚', 100000, 500, 2.5, '2025-06-21 15:15:21', 'circle', '', 500, 100, 0.00, 100, 0.00, 100, 0.00, 100, 0.00, 100, 0.00, 100, 0.00, 100, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00),
(764, 15, 'åŸŽå¸‚ B', 300, 200, '#0000FF', 'æ²¿æµ·å°é•‡', 50000, 300, 1.8, '2025-06-21 15:15:21', 'text', 'æž—', 300, 50, 0.00, 50, 0.00, 50, 0.00, 50, 0.00, 50, 0.00, 50, 0.00, 50, 0.00, 0, 0.00, 1, 'city', 0.00, 0.00);

-- --------------------------------------------------------

--
-- 表的结构 `city_players`
--

CREATE TABLE `city_players` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `player_tag` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- 转存表中的数据 `city_players`
--

INSERT INTO `city_players` (`id`, `game_id`, `city_id`, `player_tag`) VALUES
(3, 1, 1, 'éƒ½æŸæž—'),
(11, 1, 7, 'æ³¢ç‰¹é›·'),
(9, 1, 706, 'æ³¢ç‰¹é›·'),
(7, 1, 707, 'æ³¢ç‰¹é›·'),
(10, 1, 708, 'æ³¢ç‰¹é›·'),
(8, 1, 709, 'æ³¢ç‰¹é›·'),
(4, 1, 710, 'éƒ½æŸæž—'),
(5, 1, 711, 'åŒ—æ–¹é…‹é•¿åŒç›Ÿ'),
(6, 1, 712, 'åŒ—æ–¹é…‹é•¿åŒç›Ÿ');

-- --------------------------------------------------------

--
-- 表的结构 `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `rules` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `show_city_names` tinyint(1) DEFAULT '1',
  `order_points_cost` int(11) DEFAULT '1',
  `background_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `games`
--

INSERT INTO `games` (`id`, `name`, `creator_id`, `rules`, `created_at`, `show_city_names`, `order_points_cost`, `background_image`) VALUES
(1, 'ä¸åˆ—é¢ çš„ç»Ÿæ²»è€…', 1, 'ä¸ƒå›½æ—¶ä»£çš„äº‰éœ¸', '2025-05-16 07:33:40', 1, 1, '/uploads/map_backgrounds/game_1_1747467648.png'),
(2, 'èŽ«æ–¯ç§‘', 1, 'æ’’å‘é¡ºä¸°', '2025-05-16 08:15:37', 1, 1, NULL),
(15, 'çŽ‹è€…è£è€€', 19, 'çŽ‹è€…è£è€€', '2025-06-21 15:15:21', 1, 0, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `game_field_names`
--

CREATE TABLE `game_field_names` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `field_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `game_field_names`
--

INSERT INTO `game_field_names` (`id`, `game_id`, `field_name`, `display_name`) VALUES
(1, 1, 'population', 'äººå£'),
(2, 1, 'resources', 'ç²®é£Ÿå‚¨å¤‡'),
(3, 1, 'economy', 'è´¢å¯Œå‚¨å¤‡'),
(4, 1, 'military', 'æ°‘å…µå®ˆåŸŽåŠ¨å‘˜æŒ‡æ•°'),
(5, 1, 'culture', 'åŸŽå¸‚åŒ–æŒ‡æ•°'),
(6, 1, 'science', 'ç¨Žæ”¶æŒ‡æ•°'),
(7, 1, 'infrastructure', 'å†œä¸šæŒ‡æ•°'),
(8, 1, 'health', 'åœŸåœ°æ‰¿è½½æžé™'),
(9, 1, 'education', 'æ–‡æ˜Žç­‰çº§'),
(10, 1, 'stability', 'æ²»å®‰æŒ‡æ•°'),
(11, 1, 'food_consumption', 'ç²®è‰æ¶ˆè€—'),
(12, 1, 'money_consumption', 'ç»´æŠ¤è´¹');

-- --------------------------------------------------------

--
-- 表的结构 `game_formulas`
--

CREATE TABLE `game_formulas` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `field_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `formula` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `game_formulas`
--

INSERT INTO `game_formulas` (`id`, `game_id`, `field_name`, `formula`) VALUES
(1, 1, 'economy', 'population * 0.05 + culture * 1 +science  * 1'),
(2, 1, 'resources', 'infrastructure * 1'),
(3, 1, 'population_growth', '0.02 * ((stability *1 - 100)/100) * 1'),
(4, 1, 'military_growth', '((culture * 1 - military * 1)/ culture * 1) * stability * 1 / 100 * 0.2'),
(5, 1, 'culture_growth', '((population * 1 - culture * 1)/population * 1) * ( (200 - science * 1)/200 ) * ( culture * 1 / population * 1 )'),
(6, 1, 'science_growth', 'science * 0'),
(7, 1, 'infrastructure_growth', '(health * 1 - infrastructure * 1 / infrastructure * 1) * (population * 1 - culture * 10)/population * 1)'),
(8, 1, 'health_growth', 'health * 0'),
(9, 1, 'education_growth', 'education * 1 / 100 * 0.1 * ( culture * 1 / population * 1)'),
(10, 1, 'stability_growth', '(health * 1 - infrastructure * 1 / infrastructure * 1) * education * 1 / 100 * 0.1'),
(31, 2, 'economy', 'population * 1'),
(32, 2, 'resources', 'infrastructure * 1'),
(33, 2, 'population_growth', '3'),
(34, 2, 'military_growth', 'military * 1'),
(35, 2, 'culture_growth', 'education * 1'),
(36, 2, 'science_growth', 'education * 1'),
(37, 2, 'infrastructure_growth', '500'),
(38, 2, 'health_growth', 'infrastructure * 1'),
(39, 2, 'education_growth', 'science * 1'),
(40, 2, 'stability_growth', 'health * 1'),
(51, 1, 'food_consumption', 'population * 0'),
(52, 1, 'money_consumption', 'economy * 0');

-- --------------------------------------------------------

--
-- 表的结构 `game_players`
--

CREATE TABLE `game_players` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- 转存表中的数据 `game_players`
--

INSERT INTO `game_players` (`id`, `game_id`, `user_id`, `joined_at`) VALUES
(1, 1, 1, '2025-05-16 07:33:40'),
(2, 2, 1, '2025-05-16 08:15:37'),
(4, 1, 2, '2025-05-16 14:38:01'),
(5, 1, 3, '2025-05-17 08:22:05'),
(6, 1, 4, '2025-05-17 11:13:33'),
(7, 2, 5, '2025-05-18 12:14:16'),
(8, 1, 5, '2025-05-18 12:14:25'),
(9, 1, 6, '2025-05-19 03:04:32'),
(10, 1, 8, '2025-05-19 13:57:48'),
(11, 2, 9, '2025-05-21 16:17:22'),
(12, 1, 9, '2025-05-21 16:17:57'),
(13, 1, 10, '2025-05-28 12:37:35'),
(14, 1, 11, '2025-05-29 04:17:02'),
(18, 1, 15, '2025-06-19 09:29:01'),
(19, 2, 15, '2025-06-19 09:29:51'),
(21, 1, 16, '2025-06-19 12:26:34'),
(23, 2, 17, '2025-06-20 11:40:34'),
(30, 2, 19, '2025-06-21 15:15:00'),
(31, 15, 19, '2025-06-21 15:15:21'),
(32, 1, 19, '2025-06-21 15:16:23');

-- --------------------------------------------------------

--
-- 表的结构 `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `type` enum('order','response','announcement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `player_tag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `round_number` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `city_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `history`
--

INSERT INTO `history` (`id`, `game_id`, `type`, `content`, `player_tag`, `round_number`, `created_at`, `city_id`) VALUES
(1, 1, 'response', 'æŒ‡ä»¤: 5443\nå›žå¤: 565656 (ç§¯åˆ†: 1)', '', 1, '2025-05-16 22:26:29', NULL),
(2, 1, 'response', 'æŒ‡ä»¤: 564\nå›žå¤: 455454 (ç§¯åˆ†: 1)', '', 1, '2025-05-16 22:28:55', NULL),
(3, 1, 'announcement', 'å†¬ç“œè±†è…', NULL, 1, '2025-05-16 22:37:26', NULL),
(4, 1, 'response', 'æŒ‡ä»¤: 45545454\nå›žå¤: 45343 (ç§¯åˆ†: 1)', '', 2, '2025-05-16 23:09:23', NULL),
(5, 1, 'response', 'æŒ‡ä»¤: 56656\nå›žå¤: 54545 (ç§¯åˆ†: 5)', '', 2, '2025-05-17 15:44:09', NULL),
(6, 1, 'response', 'æŒ‡ä»¤: äº”æ¯’\nå›žå¤: æ–¯è’‚èŠ¬æ£® (ç§¯åˆ†: 2)', '', 6, '2025-05-19 18:12:52', NULL),
(7, 1, 'response', 'æŒ‡ä»¤: 232121\nå›žå¤: 454545 (ç§¯åˆ†: 1)', '', 6, '2025-05-19 18:15:24', NULL),
(8, 1, 'response', 'æŒ‡ä»¤: 454534\nå›žå¤: 4545 (ç§¯åˆ†: 1)', '', 6, '2025-05-19 18:16:51', NULL),
(9, 1, 'response', 'æŒ‡ä»¤: è¯ºæ£®å¸ƒé‡ŒäºšæŒ‡ä»¤\r\n1.èŠ±ä¸€ç¬”é’±ï¼Œå¸¦ç€æ–°å©šè”ç»Ÿçš„å¥³çŽ‹å’Œå«é˜Ÿæ¯ä¸ªå¢ƒå†…åŸŽå¸‚ã€åŸŽé•‡ã€ä¹¡æ‘éƒ½æ¸¸è¡Œä¸€éï¼Œè®©è”ç»Ÿåœ°åŒºçš„äººä»¬çŸ¥é“ä»–ä»¬çš„å¥³çŽ‹æ˜¯è¢«å°Šæ•¬çš„ï¼Œå¢žåŠ å¯¹è”ç»Ÿçš„è®¤åŒåº¦ã€‚\r\nå¹¶ä¸”åœ¨æœ€åŽå¸¦ç€å¥³çŽ‹å‚è§‚æˆ‘ç»è¥è¿™ä¹ˆä¹…çš„æ—©æœŸå›½å®¶åŸŽå¸‚ç±³éƒ½æ–¯å ¡ï¼Œçœ‹çœ‹è¿™ç§å«ç”Ÿæƒ…å†µå’Œæ–‡æ˜Žç¨‹åº¦èƒ½ä¸èƒ½ç»™å¥³çŽ‹ç•™ä¸‹æ·±åˆ»å°è±¡ï¼Œä»Žè€Œè®©å¥¹è‡ªå‘çš„åœ¨è”é€šé¢†åœ°æ–½åŠ å½±å“åŠ›ï¼Œå‘è¯ºæ£®å¸ƒé‡Œäºšçš„æ–‡åŒ–é æ‹¢ã€‚\r\n2.åšæŒè¯ºæ£®å¸ƒé‡Œäºšçš„å¤šå…ƒåŒ–å®—æ•™é“è·¯ä¸åŠ¨æ‘‡ï¼Œä¹‹å‰ä¿¡å¤©ä¸»æ•™æ˜¯è¢«è¿·æƒ‘äº†ï¼Œå…¬å¼€è®²è¯å°Šé‡è”ç»Ÿåœ°åŒºçš„å¤šç¥žæ•™ï¼Œä½†æ˜¯ä¹Ÿé¼“åŠ±æ¬¢è¿Žä»–ä»¬è·Ÿè¯ºæ£®å¸ƒé‡Œäºšçš„å¤šå…ƒåŒ–æ–‡åŒ–äº¤æµï¼ŒæŽ¨è¿›ä¸¤åœ°åŒºçš„æ–‡åŒ–ã€è®¤åŒèžåˆã€‚\nå›žå¤: å…³äºŽæŒ‡ä»¤1\r\né™›ä¸‹ï¼Œæ¸¸è¡Œè®¡åˆ’å·²å¼€å§‹ç­¹å¤‡ã€‚æˆ‘ä»¬ä¼šå®‰æŽ’å¥³çŽ‹å’Œå«é˜Ÿæ¸¸åŽ†è¯ºæ£®å¸ƒé‡Œäºšå…¨å¢ƒï¼Œæ²¿é€”å±•ç¤ºå¥³çŽ‹çš„å°Šè´µä¸Žäº²æ°‘ï¼Œå¢žå¼ºè”ç»Ÿåœ°åŒºäººæ°‘çš„è®¤åŒæ„Ÿã€‚æœ€åŽå‚è§‚ç±³éƒ½æ–¯å ¡ï¼Œå±•ç¤ºè¯ºæ£®å¸ƒé‡Œäºšçš„æ–‡æ˜Žæˆæžœï¼Œç›¸ä¿¡èƒ½è®©å¥³çŽ‹ç•™ä¸‹æ·±åˆ»å°è±¡ï¼ŒæŽ¨åŠ¨è”ç»Ÿåœ°åŒºå‘è¯ºæ£®å¸ƒé‡Œäºšæ–‡åŒ–é æ‹¢ã€‚\r\nå…³äºŽæŒ‡ä»¤2\r\né™›ä¸‹ï¼ŒåšæŒå¤šå…ƒåŒ–å®—æ•™é“è·¯æ˜¯æ˜Žæ™ºä¹‹ä¸¾ã€‚æˆ‘ä»¬å°†å…¬å¼€å°Šé‡è”ç»Ÿåœ°åŒºçš„å¤šç¥žæ•™ï¼Œå¹¶é¼“åŠ±æ–‡åŒ–äº¤æµã€‚è¿™å°†æ¶ˆé™¤å®—æ•™éš”é˜‚ï¼Œä¿ƒè¿›ä¸¤åœ°æ–‡åŒ–èžåˆï¼Œå¢žå¼ºäººæ°‘å‡èšåŠ›ï¼Œä¸ºè¯ºæ£®å¸ƒé‡Œäºšçš„ç¨³å®šä¸Žç¹è£å¥ å®šåŸºç¡€ã€‚ (ç§¯åˆ†: 1)', '', 6, '2025-05-19 22:35:13', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `type` enum('public','secret') DEFAULT NULL,
  `content` text,
  `round` int(11) DEFAULT NULL,
  `admin_reply` text,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- 转存表中的数据 `orders`
--

INSERT INTO `orders` (`id`, `game_id`, `user_id`, `city_id`, `type`, `content`, `round`, `admin_reply`, `submitted_at`) VALUES
(8, 1, 8, 726, 'public', 'è¯ºæ£®å¸ƒé‡ŒäºšæŒ‡ä»¤\r\n1.èŠ±ä¸€ç¬”é’±ï¼Œå¸¦ç€æ–°å©šè”ç»Ÿçš„å¥³çŽ‹å’Œå«é˜Ÿæ¯ä¸ªå¢ƒå†…åŸŽå¸‚ã€åŸŽé•‡ã€ä¹¡æ‘éƒ½æ¸¸è¡Œä¸€éï¼Œè®©è”ç»Ÿåœ°åŒºçš„äººä»¬çŸ¥é“ä»–ä»¬çš„å¥³çŽ‹æ˜¯è¢«å°Šæ•¬çš„ï¼Œå¢žåŠ å¯¹è”ç»Ÿçš„è®¤åŒåº¦ã€‚\r\nå¹¶ä¸”åœ¨æœ€åŽå¸¦ç€å¥³çŽ‹å‚è§‚æˆ‘ç»è¥è¿™ä¹ˆä¹…çš„æ—©æœŸå›½å®¶åŸŽå¸‚ç±³éƒ½æ–¯å ¡ï¼Œçœ‹çœ‹è¿™ç§å«ç”Ÿæƒ…å†µå’Œæ–‡æ˜Žç¨‹åº¦èƒ½ä¸èƒ½ç»™å¥³çŽ‹ç•™ä¸‹æ·±åˆ»å°è±¡ï¼Œä»Žè€Œè®©å¥¹è‡ªå‘çš„åœ¨è”é€šé¢†åœ°æ–½åŠ å½±å“åŠ›ï¼Œå‘è¯ºæ£®å¸ƒé‡Œäºšçš„æ–‡åŒ–é æ‹¢ã€‚\r\n2.åšæŒè¯ºæ£®å¸ƒé‡Œäºšçš„å¤šå…ƒåŒ–å®—æ•™é“è·¯ä¸åŠ¨æ‘‡ï¼Œä¹‹å‰ä¿¡å¤©ä¸»æ•™æ˜¯è¢«è¿·æƒ‘äº†ï¼Œå…¬å¼€è®²è¯å°Šé‡è”ç»Ÿåœ°åŒºçš„å¤šç¥žæ•™ï¼Œä½†æ˜¯ä¹Ÿé¼“åŠ±æ¬¢è¿Žä»–ä»¬è·Ÿè¯ºæ£®å¸ƒé‡Œäºšçš„å¤šå…ƒåŒ–æ–‡åŒ–äº¤æµï¼ŒæŽ¨è¿›ä¸¤åœ°åŒºçš„æ–‡åŒ–ã€è®¤åŒèžåˆã€‚', 6, 'å…³äºŽæŒ‡ä»¤1\r\né™›ä¸‹ï¼Œæ¸¸è¡Œè®¡åˆ’å·²å¼€å§‹ç­¹å¤‡ã€‚æˆ‘ä»¬ä¼šå®‰æŽ’å¥³çŽ‹å’Œå«é˜Ÿæ¸¸åŽ†è¯ºæ£®å¸ƒé‡Œäºšå…¨å¢ƒï¼Œæ²¿é€”å±•ç¤ºå¥³çŽ‹çš„å°Šè´µä¸Žäº²æ°‘ï¼Œå¢žå¼ºè”ç»Ÿåœ°åŒºäººæ°‘çš„è®¤åŒæ„Ÿã€‚æœ€åŽå‚è§‚ç±³éƒ½æ–¯å ¡ï¼Œå±•ç¤ºè¯ºæ£®å¸ƒé‡Œäºšçš„æ–‡æ˜Žæˆæžœï¼Œç›¸ä¿¡èƒ½è®©å¥³çŽ‹ç•™ä¸‹æ·±åˆ»å°è±¡ï¼ŒæŽ¨åŠ¨è”ç»Ÿåœ°åŒºå‘è¯ºæ£®å¸ƒé‡Œäºšæ–‡åŒ–é æ‹¢ã€‚\r\nå…³äºŽæŒ‡ä»¤2\r\né™›ä¸‹ï¼ŒåšæŒå¤šå…ƒåŒ–å®—æ•™é“è·¯æ˜¯æ˜Žæ™ºä¹‹ä¸¾ã€‚æˆ‘ä»¬å°†å…¬å¼€å°Šé‡è”ç»Ÿåœ°åŒºçš„å¤šç¥žæ•™ï¼Œå¹¶é¼“åŠ±æ–‡åŒ–äº¤æµã€‚è¿™å°†æ¶ˆé™¤å®—æ•™éš”é˜‚ï¼Œä¿ƒè¿›ä¸¤åœ°æ–‡åŒ–èžåˆï¼Œå¢žå¼ºäººæ°‘å‡èšåŠ›ï¼Œä¸ºè¯ºæ£®å¸ƒé‡Œäºšçš„ç¨³å®šä¸Žç¹è£å¥ å®šåŸºç¡€ã€‚', '2025-05-19 14:21:20'),
(9, 1, 8, 726, 'public', 'è¯ºæ£®å¸ƒé‡Œäºšå›½ç­–ç¬¬äºŒéƒ¨åˆ†ï¼š\r\n3.è·Ÿå¥³çŽ‹å•†é‡ä¸€ä¸‹ï¼Œä¸¤å›½æ²¿ç€å“ˆå¾·è‰¯é•¿åŸŽâ€”â€”å„è‡ªæµ·å²¸çº¿â€”â€”å¤šæ©è¦å¡žè¿™ä¸€åœˆï¼Œå»ºè®¾çž­æœ›å“¨æˆ˜å’Œçƒ½ç«å°ï¼Œè¿™æ ·å¦‚æžœæœ‰æµ·ç›—ä»¥åŠå…¥ä¾µï¼Œèƒ½å¤ŸåŠæ—¶ç‚¹ç‡ƒçƒ½ç«é¢„è­¦ï¼ŒåŒæ—¶éƒ¨ç½²è½»éª‘å…µåœ¨çƒ½ç«å°ï¼Œèƒ½å¤Ÿä¼ é€’å…·ä½“æƒ…å†µä¿¡æ¯\r\n4.å‡ºé‡é‡‘ä¿®ç¼®åŽŸæœ‰çš„ç½—é©¬åŸŽå¢™ï¼Œå¹¶ä¸”åœ¨æ­¤åŸºç¡€ä¸Šï¼Œç”¨æœ¨å¤´å’ŒçŸ³å¤´ç»™é¢†åœ°å†…çš„æ‘åº„ã€åŸŽé•‡å’ŒåŸŽå¸‚çš„å›´å¢™è¿›è¡ŒåŠ å›ºï¼Œå¦‚æžœæ²¡æœ‰å›´å¢™ï¼Œå°±å»ºè®¾å›´å¢™ï¼Œç¡®ä¿æ‘åº„åŸŽé•‡å’ŒåŸŽå¸‚éƒ½æ˜¯è¢«é˜²å¾¡è®¾æ–½ä¿æŠ¤çš„ã€‚å›´å¢™åŽé¢è¿˜è¦æ­é«˜å°ï¼Œå¯ä»¥è®©å¼“ç®­æ‰‹ä¸ŠåŽ»ï¼Œåœ¨å›´å¢™çš„ä¿æŠ¤ä¸‹å°„ç®­ã€‚\r\n5.ä»Žæ³•å…°å…‹çŽ‹å›½å¼•è¿›é‡åž‹çŠï¼Œç»„å»ºè€•ç‰›åˆä½œç¤¾å…±äº«ç•œåŠ›ã€‚å¼•å…¥è±Œè±†/èš•è±†ç§æ¤ã€‚\r\n6.æ´¾ä½¿è€…å‰å¾€æ‹œå åº­æ‹›å‹Ÿæµäº¡å·¥ç¨‹å¸ˆ/å­¦ä¹ å¸Œè…Šç«æŠ•å°„å™¨ã€‚é€šè¿‡çŠ¹å¤ªå•†äººè´­ä¹°ä¹¦ç±ï¼ŒèŽ·å–é˜¿æ‹‰ä¼¯æ•°å­¦ä¸Žæ°´åˆ©æŠ€æœ¯ã€‚', 6, NULL, '2025-05-20 00:51:11'),
(10, 1, 8, 727, 'public', 'è¯ºæ£®å¸ƒé‡Œäºšå›½ç­–ç¬¬ä¸‰éƒ¨åˆ†\r\n7.åœ¨è´ç­å ¡å¾å‹Ÿ50é‡æ­¥å…µã€95å¼“ç®­æ‰‹ã€80é•¿æžªå…µ', 6, NULL, '2025-05-20 07:02:52');

-- --------------------------------------------------------

--
-- 表的结构 `pending_orders`
--

CREATE TABLE `pending_orders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `game_id` int(11) DEFAULT NULL,
  `points_cost` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- 转存表中的数据 `pending_orders`
--

INSERT INTO `pending_orders` (`id`, `order_id`, `user_id`, `game_id`, `points_cost`) VALUES
(1, 1, 1, 1, 1),
(3, 2, 1, 1, 1),
(4, 3, 1, 1, 1),
(5, 4, 1, 1, 5),
(6, 5, 1, 1, 2),
(7, 6, 1, 1, 1),
(8, 7, 2, 1, 1),
(9, 8, 8, 1, 1),
(10, 9, 8, 1, 1),
(11, 10, 8, 1, 1),
(12, 11, 19, 13, 1);

-- --------------------------------------------------------

--
-- 表的结构 `rounds`
--

CREATE TABLE `rounds` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `round_number` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `end_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','ended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- 转存表中的数据 `rounds`
--

INSERT INTO `rounds` (`id`, `game_id`, `round_number`, `is_active`, `end_time`, `status`) VALUES
(1, 1, 1, 0, '2025-05-16 14:38:28', 'active'),
(2, 2, 1, 0, '2025-06-15 15:02:28', 'active'),
(5, 1, 2, 0, '2025-05-18 13:33:50', 'active'),
(6, 1, 3, 0, '2025-05-18 13:34:04', 'active'),
(7, 1, 4, 0, '2025-05-18 13:34:33', 'active'),
(8, 1, 5, 0, '2025-05-18 14:09:36', 'active'),
(9, 1, 6, 1, '2025-05-19 14:09:36', 'active'),
(16, 2, 2, 0, '2025-06-15 15:03:20', 'active'),
(17, 2, 3, 0, '2025-06-15 15:04:45', 'active'),
(18, 2, 4, 0, '2025-06-15 15:07:26', 'active'),
(19, 2, 5, 0, '2025-06-15 15:15:02', 'active'),
(20, 2, 6, 1, '2025-06-16 15:15:02', 'active'),
(26, 15, 1, 1, '2025-06-22 15:15:21', 'active');

-- --------------------------------------------------------

--
-- 表的结构 `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'F',
  `task_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `opening_price` decimal(10,2) NOT NULL,
  `total_shares` int(11) NOT NULL,
  `own_shares` int(11) NOT NULL,
  `form` enum('Sole','Crowdfunding','Hybrid') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `task_state` enum('Approval','Open','Completed','Perpetual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `latest_price` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `tasks`
--

INSERT INTO `tasks` (`id`, `code`, `rating`, `task_name`, `opening_price`, `total_shares`, `own_shares`, `form`, `description`, `creator_id`, `task_state`, `created_at`, `latest_price`) VALUES
(3, 'B00001-C', 'B', 'ä¸åˆ—é¢ ç»Ÿæ²»è€…', 1.00, 6000, 1000, 'Crowdfunding', 'å›½ç­–åˆ¶ä½œ', 1, 'Open', '2025-06-08 11:35:34', 1.00),
(4, 'A00002-C', 'A', 'å†’é™©è€…å…¬ä¼š', 1.00, 10000, 1000, 'Crowdfunding', 'RPGæ¸¸æˆåˆ¶ä½œ', 1, 'Open', '2025-06-08 11:42:02', 1.00);

-- --------------------------------------------------------

--
-- 表的结构 `task_acceptances`
--

CREATE TABLE `task_acceptances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `accepted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creator_intent` enum('Not Selected','Selected') COLLATE utf8mb4_unicode_ci DEFAULT 'Not Selected',
  `admin_status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `completion_status` enum('Pending','Completed') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `completion_period` int(11) DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `task_equity`
--

CREATE TABLE `task_equity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `shares_owned` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `task_equity`
--

INSERT INTO `task_equity` (`id`, `user_id`, `task_id`, `shares_owned`, `created_at`) VALUES
(1, 1, 3, 1000, '2025-06-08 11:35:34'),
(2, 1, 4, 1000, '2025-06-08 11:42:02');

-- --------------------------------------------------------

--
-- 表的结构 `task_history`
--

CREATE TABLE `task_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `points_spent` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `task_history`
--

INSERT INTO `task_history` (`id`, `user_id`, `task_id`, `points_spent`, `created_at`) VALUES
(1, 1, 3, 1000.00, '2025-06-08 11:35:34'),
(2, 1, 4, 1000.00, '2025-06-08 11:42:02');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('super_admin','game_admin','player') COLLATE utf8mb4_unicode_ci DEFAULT 'player',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `points` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `points`) VALUES
(1, 'maya970', '$2y$10$AgP7TamoCqti4/75eNuhNOuWesWUhpS.S1idcUrhiSiWzLvm3n7Mq', 'super_admin', '2025-05-16 07:32:23', 940),
(2, 'marco99168', '$2y$10$BPPwPjxOKoVNNxGAM/orC.0BcXmtGMrhQ5nk./r2ipOBgyl9b8n5e', 'game_admin', '2025-05-16 09:21:32', 49),
(3, 'GOD-6', '$2y$10$8eNg5w2GcoSXco7hm07w7uyDKL4GI.wFS9pNSEtmsTTXJrnYY18FC', 'player', '2025-05-17 08:21:42', 50),
(4, 'hunterlx', '$2y$10$LEQxamd.SfZr3Q1nBw3ZwOi1P.o7N73qOOgVgoPTRzMtA1BEZrhte', 'player', '2025-05-17 11:13:13', 50),
(5, '18121457229', '$2y$10$Sh7t7DpJHsMRXbCJWFeVXeL/xMeU1H0QAa61RRCIPVUj1Oaa6LRgS', 'player', '2025-05-18 12:13:50', 50),
(6, 'JacobMorris', '$2y$10$7lhnI0bmX6tKcUmZ5/ON2u3ZqEepSlKgiJlMMU5Rhu7wzn7L3bGi.', 'player', '2025-05-19 03:04:13', 50),
(7, 'pla425', '$2y$10$s6cuMGQV834u/hmHRUYx1e/NS/Y0hmTd540lGss8PVAfbhLd.a8kC', 'player', '2025-05-19 13:56:08', 50),
(8, 'NuoSen', '$2y$10$TA1.LkqeklwPW0.tG9b9R.XHAHSBZltdwh5frBEXUGOQ8hnZRlJCm', 'player', '2025-05-19 13:57:33', 49),
(9, 'Kosmonaut', '$2y$10$Xd0CNgVK3yS2AxELljlLY.ihyRf658nQsbIYnbrgPkyFMPbhYue/6', 'player', '2025-05-21 16:16:53', 0),
(10, 'logo99168', '$2y$10$XDOdIzHMm3iAJS/X.fVgvOmSiP8m2POW51W/77BE4QLnSP5W7lAxm', 'game_admin', '2025-05-28 12:37:26', 0),
(11, 'å¤©çŽ‰233', '$2y$10$L8mrJlG.6yRP1YyWkbXX2.yT0Kn6yn87aoGwQFdT1dQgewyF/sWtO', 'player', '2025-05-29 04:16:47', 0),
(12, 'kontol', '$2y$10$aEI2MaTrisdicZEHBTVirunUiimJMqP1q/XDk8G4DqELzIMwHvVZm', 'game_admin', '2025-06-11 13:02:53', 0),
(13, 'é˜¿å·´é˜¿å·´', '$2y$10$ntgM.CBnOOuwLfHMI.R4aekV7yBnLVefzSFvISiiVUBRzMFT58o.W', 'game_admin', '2025-06-15 07:49:29', 0),
(14, 'FISH', '$2y$10$fiQdOrYvlDMAGwgpoxFWNezqOFxNfJnu3f1JtpsBHRhNpxH3uocWu', 'player', '2025-06-18 08:53:37', 0),
(15, 'å¸Œå°”è–‡', '$2y$10$31PMLZDOGLhGsXV85daLbee9Lc3WRM79wKOE9uvXKWLdwP7dmlGHm', 'player', '2025-06-19 09:26:09', 0),
(16, 'chenxin', '$2y$10$6.LHGkjtDvHh0hcppFc2YOY4.VU.4xfsDIh3yUJEu8ga8TppBZ3em', 'player', '2025-06-19 12:23:12', 0),
(17, 'zaq870081590', '$2y$10$HcEzJe5FIXzuF2W7xIQVgelInb94LdzBMfajheGJLmxwTlTpmixm2', 'game_admin', '2025-06-20 11:30:22', 0),
(18, 'Tianyi29', '$2y$10$GbuOC8Pfd3g3GU4Il2aTDOAjJ6ImwBnz2RELFT81briuOIXtGnOta', 'game_admin', '2025-06-20 12:55:00', 0),
(19, 'qwerty123', '$2y$10$oNFh2l0MzlW6zlulW6OZSOj6hTflowiXBvgC6I/vqcPG/Cvp2F12W', 'game_admin', '2025-06-21 13:42:01', 0);

--
-- 转储表的索引
--

--
-- 表的索引 `autorpg_building_masters`
--
ALTER TABLE `autorpg_building_masters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`layer`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `autorpg_combats`
--
ALTER TABLE `autorpg_combats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `attacker_id` (`attacker_id`),
  ADD KEY `attacker_monster_id` (`attacker_monster_id`),
  ADD KEY `defender_id` (`defender_id`);

--
-- 表的索引 `autorpg_dialogues`
--
ALTER TABLE `autorpg_dialogues`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `autorpg_dialogue_options`
--
ALTER TABLE `autorpg_dialogue_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dialogue_id` (`dialogue_id`),
  ADD KEY `next_dialogue_id` (`next_dialogue_id`);

--
-- 表的索引 `autorpg_games`
--
ALTER TABLE `autorpg_games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- 表的索引 `autorpg_ground_items`
--
ALTER TABLE `autorpg_ground_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`layer`,`x`,`y`),
  ADD KEY `item_id` (`item_id`);

--
-- 表的索引 `autorpg_items`
--
ALTER TABLE `autorpg_items`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `autorpg_layers`
--
ALTER TABLE `autorpg_layers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`layer`);

--
-- 表的索引 `autorpg_map_tiles`
--
ALTER TABLE `autorpg_map_tiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`layer`,`x`,`y`);

--
-- 表的索引 `autorpg_monsters`
--
ALTER TABLE `autorpg_monsters`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `autorpg_monster_drops`
--
ALTER TABLE `autorpg_monster_drops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `monster_id` (`monster_id`),
  ADD KEY `item_id` (`item_id`);

--
-- 表的索引 `autorpg_monster_suppression`
--
ALTER TABLE `autorpg_monster_suppression`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`layer`,`x`,`y`);

--
-- 表的索引 `autorpg_players`
--
ALTER TABLE `autorpg_players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `autorpg_player_inventory`
--
ALTER TABLE `autorpg_player_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`game_id`,`slot`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `item_id` (`item_id`);

--
-- 表的索引 `autorpg_player_skills`
--
ALTER TABLE `autorpg_player_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`game_id`,`slot`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- 表的索引 `autorpg_skills`
--
ALTER TABLE `autorpg_skills`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `autorpg_synthesis_recipes`
--
ALTER TABLE `autorpg_synthesis_recipes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item1_id` (`item1_id`,`item2_id`),
  ADD KEY `item2_id` (`item2_id`),
  ADD KEY `result_item_id` (`result_item_id`);

--
-- 表的索引 `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- 表的索引 `city_players`
--
ALTER TABLE `city_players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`game_id`,`city_id`,`player_tag`),
  ADD KEY `city_id` (`city_id`);

--
-- 表的索引 `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- 表的索引 `game_field_names`
--
ALTER TABLE `game_field_names`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`field_name`);

--
-- 表的索引 `game_formulas`
--
ALTER TABLE `game_formulas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`field_name`);

--
-- 表的索引 `game_players`
--
ALTER TABLE `game_players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `fk_history_city_id` (`city_id`);

--
-- 表的索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `city_id` (`city_id`);

--
-- 表的索引 `pending_orders`
--
ALTER TABLE `pending_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order` (`order_id`);

--
-- 表的索引 `rounds`
--
ALTER TABLE `rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- 表的索引 `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `creator_id` (`creator_id`);

--
-- 表的索引 `task_acceptances`
--
ALTER TABLE `task_acceptances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_task` (`user_id`,`task_id`),
  ADD KEY `task_id` (`task_id`);

--
-- 表的索引 `task_equity`
--
ALTER TABLE `task_equity`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_task` (`user_id`,`task_id`),
  ADD KEY `task_id` (`task_id`);

--
-- 表的索引 `task_history`
--
ALTER TABLE `task_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `autorpg_building_masters`
--
ALTER TABLE `autorpg_building_masters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_combats`
--
ALTER TABLE `autorpg_combats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_dialogues`
--
ALTER TABLE `autorpg_dialogues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_dialogue_options`
--
ALTER TABLE `autorpg_dialogue_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_games`
--
ALTER TABLE `autorpg_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `autorpg_ground_items`
--
ALTER TABLE `autorpg_ground_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_items`
--
ALTER TABLE `autorpg_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_layers`
--
ALTER TABLE `autorpg_layers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `autorpg_map_tiles`
--
ALTER TABLE `autorpg_map_tiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=301;

--
-- 使用表AUTO_INCREMENT `autorpg_monsters`
--
ALTER TABLE `autorpg_monsters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_monster_drops`
--
ALTER TABLE `autorpg_monster_drops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_monster_suppression`
--
ALTER TABLE `autorpg_monster_suppression`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_players`
--
ALTER TABLE `autorpg_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `autorpg_player_inventory`
--
ALTER TABLE `autorpg_player_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_player_skills`
--
ALTER TABLE `autorpg_player_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_skills`
--
ALTER TABLE `autorpg_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `autorpg_synthesis_recipes`
--
ALTER TABLE `autorpg_synthesis_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=765;

--
-- 使用表AUTO_INCREMENT `city_players`
--
ALTER TABLE `city_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用表AUTO_INCREMENT `game_field_names`
--
ALTER TABLE `game_field_names`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用表AUTO_INCREMENT `game_formulas`
--
ALTER TABLE `game_formulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- 使用表AUTO_INCREMENT `game_players`
--
ALTER TABLE `game_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- 使用表AUTO_INCREMENT `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用表AUTO_INCREMENT `pending_orders`
--
ALTER TABLE `pending_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用表AUTO_INCREMENT `rounds`
--
ALTER TABLE `rounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- 使用表AUTO_INCREMENT `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `task_acceptances`
--
ALTER TABLE `task_acceptances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `task_equity`
--
ALTER TABLE `task_equity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `task_history`
--
ALTER TABLE `task_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- 限制导出的表
--

--
-- 限制表 `autorpg_building_masters`
--
ALTER TABLE `autorpg_building_masters`
  ADD CONSTRAINT `autorpg_building_masters_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`),
  ADD CONSTRAINT `autorpg_building_masters_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 限制表 `autorpg_combats`
--
ALTER TABLE `autorpg_combats`
  ADD CONSTRAINT `autorpg_combats_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`),
  ADD CONSTRAINT `autorpg_combats_ibfk_2` FOREIGN KEY (`attacker_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `autorpg_combats_ibfk_3` FOREIGN KEY (`attacker_monster_id`) REFERENCES `autorpg_monsters` (`id`),
  ADD CONSTRAINT `autorpg_combats_ibfk_4` FOREIGN KEY (`defender_id`) REFERENCES `users` (`id`);

--
-- 限制表 `autorpg_dialogue_options`
--
ALTER TABLE `autorpg_dialogue_options`
  ADD CONSTRAINT `autorpg_dialogue_options_ibfk_1` FOREIGN KEY (`dialogue_id`) REFERENCES `autorpg_dialogues` (`id`),
  ADD CONSTRAINT `autorpg_dialogue_options_ibfk_2` FOREIGN KEY (`next_dialogue_id`) REFERENCES `autorpg_dialogues` (`id`);

--
-- 限制表 `autorpg_games`
--
ALTER TABLE `autorpg_games`
  ADD CONSTRAINT `autorpg_games_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`);

--
-- 限制表 `autorpg_ground_items`
--
ALTER TABLE `autorpg_ground_items`
  ADD CONSTRAINT `autorpg_ground_items_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`),
  ADD CONSTRAINT `autorpg_ground_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `autorpg_items` (`id`);

--
-- 限制表 `autorpg_layers`
--
ALTER TABLE `autorpg_layers`
  ADD CONSTRAINT `autorpg_layers_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`);

--
-- 限制表 `autorpg_map_tiles`
--
ALTER TABLE `autorpg_map_tiles`
  ADD CONSTRAINT `autorpg_map_tiles_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`);

--
-- 限制表 `autorpg_monster_drops`
--
ALTER TABLE `autorpg_monster_drops`
  ADD CONSTRAINT `autorpg_monster_drops_ibfk_1` FOREIGN KEY (`monster_id`) REFERENCES `autorpg_monsters` (`id`),
  ADD CONSTRAINT `autorpg_monster_drops_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `autorpg_items` (`id`);

--
-- 限制表 `autorpg_monster_suppression`
--
ALTER TABLE `autorpg_monster_suppression`
  ADD CONSTRAINT `autorpg_monster_suppression_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`);

--
-- 限制表 `autorpg_players`
--
ALTER TABLE `autorpg_players`
  ADD CONSTRAINT `autorpg_players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`),
  ADD CONSTRAINT `autorpg_players_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 限制表 `autorpg_player_inventory`
--
ALTER TABLE `autorpg_player_inventory`
  ADD CONSTRAINT `autorpg_player_inventory_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `autorpg_player_inventory_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`),
  ADD CONSTRAINT `autorpg_player_inventory_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `autorpg_items` (`id`);

--
-- 限制表 `autorpg_player_skills`
--
ALTER TABLE `autorpg_player_skills`
  ADD CONSTRAINT `autorpg_player_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `autorpg_player_skills_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `autorpg_games` (`id`),
  ADD CONSTRAINT `autorpg_player_skills_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `autorpg_skills` (`id`);

--
-- 限制表 `autorpg_synthesis_recipes`
--
ALTER TABLE `autorpg_synthesis_recipes`
  ADD CONSTRAINT `autorpg_synthesis_recipes_ibfk_1` FOREIGN KEY (`item1_id`) REFERENCES `autorpg_items` (`id`),
  ADD CONSTRAINT `autorpg_synthesis_recipes_ibfk_2` FOREIGN KEY (`item2_id`) REFERENCES `autorpg_items` (`id`),
  ADD CONSTRAINT `autorpg_synthesis_recipes_ibfk_3` FOREIGN KEY (`result_item_id`) REFERENCES `autorpg_items` (`id`);

--
-- 限制表 `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);

--
-- 限制表 `city_players`
--
ALTER TABLE `city_players`
  ADD CONSTRAINT `city_players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  ADD CONSTRAINT `city_players_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`);

--
-- 限制表 `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `games_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`);

--
-- 限制表 `game_field_names`
--
ALTER TABLE `game_field_names`
  ADD CONSTRAINT `game_field_names_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- 限制表 `game_formulas`
--
ALTER TABLE `game_formulas`
  ADD CONSTRAINT `game_formulas_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- 限制表 `game_players`
--
ALTER TABLE `game_players`
  ADD CONSTRAINT `game_players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  ADD CONSTRAINT `game_players_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 限制表 `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `fk_history_city_id` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);

--
-- 限制表 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`);

--
-- 限制表 `rounds`
--
ALTER TABLE `rounds`
  ADD CONSTRAINT `rounds_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);

--
-- 限制表 `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`);

--
-- 限制表 `task_acceptances`
--
ALTER TABLE `task_acceptances`
  ADD CONSTRAINT `task_acceptances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `task_acceptances_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`);

--
-- 限制表 `task_equity`
--
ALTER TABLE `task_equity`
  ADD CONSTRAINT `task_equity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `task_equity_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`);

--
-- 限制表 `task_history`
--
ALTER TABLE `task_history`
  ADD CONSTRAINT `task_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `task_history_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
