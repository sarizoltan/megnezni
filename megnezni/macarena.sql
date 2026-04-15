-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gép: mysql.omega:3306
-- Létrehozás ideje: 2026. Ápr 15. 10:10
-- Kiszolgáló verziója: 10.11.14-MariaDB-0+deb12u2
-- PHP verzió: 7.2.34-61+0~20260213.113+debian12~1.gbp7055a0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `macarena`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin') DEFAULT 'admin',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Sári Zoltán', 'hello@foglalasi-rendszer.hu', '$2y$12$p4EDWJcBPAv0O3RIO0iVg.QD7YtNi1Dhmci14yAy8z8kvApN.kvsu', 'superadmin', '2026-04-12 11:27:43');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-calendar',
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#c8a96e',
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`, `color`, `sort_order`, `active`) VALUES
(1, 'Fogorvos', 'fogorvos', 'fas fa-tooth', NULL, '#3b82f6', 1, 1),
(2, 'Nőgyógyász', 'nogyogyasz', 'fas fa-heartbeat', NULL, '#ec4899', 2, 1),
(3, 'Étterem', 'etterem', 'fas fa-utensils', NULL, '#f59e0b', 3, 1),
(4, 'Szállás', 'szallas', 'fas fa-bed', NULL, '#8b5cf6', 4, 1),
(5, 'Borbély', 'barbely', 'fas fa-cut', NULL, '#c8a96e', 5, 1),
(6, 'Szépségszalon', 'szepsegszalon', 'fas fa-spa', NULL, '#ec4899', 6, 1),
(7, 'Autószerelő', 'autoszerelo', 'fas fa-car', NULL, '#6b7280', 7, 1),
(8, 'Egyéb', 'egyeb', 'fas fa-calendar-check', NULL, '#10b981', 8, 1);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `system_id` int(11) DEFAULT NULL COMMENT 'NULL = általános FAQ',
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `system_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `inquiries`
--

INSERT INTO `inquiries` (`id`, `system_id`, `name`, `email`, `phone`, `company`, `message`, `status`, `created_at`) VALUES
(1, 1, 'Sári Zoltán', 'sari.zoltan@cukorbetegreceptek.hu', '06206267127', 'Sári Zoltán', 'hdthdfhdfhdfh', 'read', '2026-04-12 13:07:30'),
(2, 1, 'Sári Zoltán', 'sari.zoltan@cukorbetegreceptek.hu', '06206267127', 'Sári Zoltán', 'vavshsfhfhdfhdffdfbfg', 'new', '2026-04-12 15:01:02'),
(3, 1, 'Sári Zoltán', 'sari.zoltan@cukorbetegreceptek.hu', '+36206267127', 'Sári Zoltán', 'hjtdjdjdg jjgjgjg jgfjgfjgf', 'new', '2026-04-13 09:48:13'),
(4, 1, 'Sári Zoltán', 'weboldalajanlatok@gmail.com', '06 20 626 71 27', 'Sári Zoltán', 'sgsgsgsd dgshss', 'new', '2026-04-14 12:18:02');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `pricing_plans`
--

CREATE TABLE `pricing_plans` (
  `id` int(11) NOT NULL,
  `system_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `period` enum('one_time','monthly','yearly') DEFAULT 'monthly',
  `description` varchar(255) DEFAULT NULL,
  `features` text DEFAULT NULL COMMENT 'JSON tömb',
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `settings`
--

CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `settings`
--

INSERT INTO `settings` (`key`, `value`) VALUES
('google_analytics', ''),
('hero_cta_text', 'Rendszerek megtekintése'),
('hero_cta_url', '#systems'),
('hero_subtitle', 'Egyszerű, gyors, megbízható. Válaszd ki az iparágadnak megfelelő foglalási rendszert!'),
('hero_title', 'Okos foglalási rendszer\r\nvállalkozásodnak'),
('meta_description', 'Professzionális foglalási rendszerek fogorvosoknak, éttermeknek, szállásoknak és más vállalkozásoknak.'),
('schema_org_address', ''),
('schema_org_logo', ''),
('schema_org_name', 'Foglalas.hu'),
('schema_org_phone', ''),
('schema_org_type', 'Organization'),
('seo_canonical_url', 'https://foglalasi-rendszer.hu'),
('seo_og_image', ''),
('seo_title_suffix', ' – Foglalas.hu'),
('seo_twitter_card', 'summary_large_image'),
('seo_twitter_site', ''),
('site_address', '7252 Attala Vörösmarty u 6'),
('site_email', 'weboldalajanlatok@gmail.com'),
('site_name', 'Foglalási rendszer'),
('site_phone', '+36 20 626 71 27'),
('site_tagline', 'Okos foglalási rendszerek minden vállalkozásnak'),
('smtp_from_email', 'hello@foglalasi-rendszer.hu'),
('smtp_from_name', 'Foglalási Rendszer'),
('smtp_host', 'smtp.gmail.com'),
('smtp_pass', ''),
('smtp_port', '587'),
('smtp_user', ''),
('social_facebook', ''),
('social_instagram', ''),
('social_linkedin', '');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `stats`
--

CREATE TABLE `stats` (
  `id` int(11) NOT NULL,
  `system_id` int(11) DEFAULT NULL,
  `event` varchar(50) NOT NULL COMMENT 'view, demo_click, inquiry',
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `stats`
--

INSERT INTO `stats` (`id`, `system_id`, `event`, `ip`, `created_at`) VALUES
(1, NULL, 'view', '::1', '2026-04-12 12:12:56'),
(2, NULL, 'view', '::1', '2026-04-12 12:17:52'),
(3, NULL, 'view', '::1', '2026-04-12 12:21:24'),
(4, NULL, 'view', '::1', '2026-04-12 12:22:05'),
(5, NULL, 'view', '::1', '2026-04-12 12:46:10'),
(6, NULL, 'view', '::1', '2026-04-12 12:46:40'),
(7, NULL, 'view', '::1', '2026-04-12 12:54:00'),
(8, 1, 'demo_click', '::1', '2026-04-12 12:54:06'),
(9, NULL, 'view', '::1', '2026-04-12 12:54:54'),
(10, NULL, 'view', '::1', '2026-04-12 12:54:58'),
(11, NULL, 'view', '::1', '2026-04-12 12:58:50'),
(12, NULL, 'view', '::1', '2026-04-12 13:01:33'),
(13, 1, 'inquiry', '::1', '2026-04-12 13:07:30'),
(14, NULL, 'view', '::1', '2026-04-12 13:08:31'),
(15, 1, 'view', '::1', '2026-04-12 13:26:27'),
(16, NULL, 'view', '::1', '2026-04-12 13:26:53'),
(17, NULL, 'view', '::1', '2026-04-12 13:28:19'),
(18, NULL, 'view', '::1', '2026-04-12 13:32:58'),
(19, NULL, 'view', '::1', '2026-04-12 13:34:18'),
(20, NULL, 'view', '::1', '2026-04-12 13:34:22'),
(21, NULL, 'view', '::1', '2026-04-12 13:36:18'),
(22, 1, 'view', '::1', '2026-04-12 13:36:25'),
(23, NULL, 'view', '::1', '2026-04-12 13:36:47'),
(24, 1, 'view', '::1', '2026-04-12 13:36:56'),
(25, NULL, 'view', '::1', '2026-04-12 13:37:43'),
(26, NULL, 'view', '::1', '2026-04-12 13:37:47'),
(27, 1, 'view', '::1', '2026-04-12 13:38:16'),
(28, 1, 'view', '::1', '2026-04-12 13:38:20'),
(29, NULL, 'view', '::1', '2026-04-12 13:39:27'),
(30, 1, 'view', '::1', '2026-04-12 13:39:34'),
(31, NULL, 'view', '::1', '2026-04-12 13:39:52'),
(32, 1, 'view', '::1', '2026-04-12 13:39:56'),
(33, NULL, 'view', '::1', '2026-04-12 13:41:14'),
(34, 1, 'view', '::1', '2026-04-12 13:41:19'),
(35, NULL, 'view', '::1', '2026-04-12 13:44:02'),
(36, 1, 'view', '::1', '2026-04-12 13:44:06'),
(37, NULL, 'view', '::1', '2026-04-12 13:48:24'),
(38, 1, 'view', '::1', '2026-04-12 13:48:29'),
(39, NULL, 'view', '::1', '2026-04-12 13:50:07'),
(40, 1, 'view', '::1', '2026-04-12 13:50:11'),
(41, NULL, 'view', '::1', '2026-04-12 14:14:26'),
(42, 1, 'view', '::1', '2026-04-12 14:14:34'),
(43, NULL, 'view', '::1', '2026-04-12 14:16:46'),
(44, NULL, 'view', '::1', '2026-04-12 14:17:46'),
(45, 1, 'view', '::1', '2026-04-12 14:17:48'),
(46, NULL, 'view', '::1', '2026-04-12 14:21:05'),
(47, NULL, 'view', '::1', '2026-04-12 14:21:30'),
(48, 1, 'demo_click', '::1', '2026-04-12 14:21:46'),
(49, 1, 'view', '::1', '2026-04-12 14:24:43'),
(50, NULL, 'view', '::1', '2026-04-12 14:24:46'),
(51, 1, 'view', '::1', '2026-04-12 14:24:50'),
(52, NULL, 'view', '91.146.141.204', '2026-04-12 14:54:40'),
(53, 1, 'view', '91.146.141.204', '2026-04-12 14:56:17'),
(54, NULL, 'view', '91.146.141.204', '2026-04-12 14:59:03'),
(55, NULL, 'view', '91.146.141.204', '2026-04-12 15:00:41'),
(56, 1, 'inquiry', '91.146.141.204', '2026-04-12 15:01:02'),
(57, NULL, 'view', '91.146.141.204', '2026-04-12 15:02:09'),
(58, NULL, 'view', '66.249.73.165', '2026-04-12 15:05:04'),
(59, 1, 'view', '91.146.141.204', '2026-04-12 15:05:49'),
(60, 1, 'view', '66.249.73.165', '2026-04-12 15:06:09'),
(61, NULL, 'view', '91.146.141.204', '2026-04-12 15:07:14'),
(62, NULL, 'view', '91.146.141.204', '2026-04-12 15:13:38'),
(63, 1, 'view', '91.146.141.204', '2026-04-12 15:14:36'),
(64, NULL, 'view', '154.28.229.59', '2026-04-12 15:15:51'),
(65, NULL, 'view', '107.172.195.47', '2026-04-12 15:15:51'),
(66, NULL, 'view', '107.172.195.47', '2026-04-12 15:15:51'),
(67, 1, 'view', '66.249.73.163', '2026-04-12 15:18:38'),
(68, NULL, 'view', '66.249.73.165', '2026-04-12 15:21:37'),
(69, 1, 'view', '66.249.73.164', '2026-04-12 15:22:03'),
(70, NULL, 'view', '66.249.73.163', '2026-04-12 15:22:09'),
(71, NULL, 'view', '66.249.73.164', '2026-04-12 15:22:09'),
(72, 1, 'view', '91.146.141.204', '2026-04-12 15:28:56'),
(73, 1, 'view', '66.249.73.165', '2026-04-12 15:29:03'),
(74, 1, 'view', '66.249.73.164', '2026-04-12 15:33:07'),
(75, 1, 'view', '91.146.141.204', '2026-04-12 15:34:26'),
(76, NULL, 'view', '91.146.141.204', '2026-04-12 15:34:41'),
(77, NULL, 'view', '142.250.32.37', '2026-04-12 15:34:55'),
(78, NULL, 'view', '142.250.32.38', '2026-04-12 15:34:55'),
(79, NULL, 'view', '66.102.9.4', '2026-04-12 15:34:55'),
(80, NULL, 'view', '142.250.32.37', '2026-04-12 15:34:56'),
(81, NULL, 'view', '142.250.32.39', '2026-04-12 15:35:23'),
(82, NULL, 'view', '136.113.242.250', '2026-04-12 15:35:35'),
(83, 1, 'view', '91.146.141.204', '2026-04-12 15:37:05'),
(84, NULL, 'view', '91.146.141.204', '2026-04-12 15:47:08'),
(85, NULL, 'view', '34.60.54.68', '2026-04-12 16:10:22'),
(86, NULL, 'view', '91.146.141.204', '2026-04-12 16:22:14'),
(87, NULL, 'view', '142.250.32.38', '2026-04-12 16:22:46'),
(88, NULL, 'view', '142.250.32.38', '2026-04-12 16:22:46'),
(89, NULL, 'view', '66.102.9.7', '2026-04-12 16:22:49'),
(90, NULL, 'view', '74.125.208.229', '2026-04-12 16:22:49'),
(91, NULL, 'view', '66.102.9.6', '2026-04-12 16:22:53'),
(92, NULL, 'view', '74.125.208.229', '2026-04-12 16:22:56'),
(93, NULL, 'view', '91.146.141.204', '2026-04-12 16:23:43'),
(94, NULL, 'view', '91.146.141.204', '2026-04-12 16:24:29'),
(95, NULL, 'view', '142.250.32.40', '2026-04-12 16:30:08'),
(96, NULL, 'view', '142.250.32.38', '2026-04-12 16:30:08'),
(97, NULL, 'view', '74.125.208.230', '2026-04-12 16:30:12'),
(98, NULL, 'view', '74.125.208.231', '2026-04-12 16:30:13'),
(99, NULL, 'view', '74.125.208.230', '2026-04-12 16:30:16'),
(100, NULL, 'view', '74.125.208.231', '2026-04-12 16:30:18'),
(101, NULL, 'view', '91.146.141.204', '2026-04-12 16:38:22'),
(102, NULL, 'view', '142.250.32.38', '2026-04-12 16:38:29'),
(103, NULL, 'view', '142.250.32.40', '2026-04-12 16:38:29'),
(104, NULL, 'view', '74.125.208.231', '2026-04-12 16:38:32'),
(105, NULL, 'view', '74.125.208.230', '2026-04-12 16:38:33'),
(106, NULL, 'view', '74.125.208.229', '2026-04-12 16:38:36'),
(107, NULL, 'view', '74.125.208.230', '2026-04-12 16:38:41'),
(108, NULL, 'view', '142.250.32.39', '2026-04-12 16:41:58'),
(109, NULL, 'view', '142.250.32.39', '2026-04-12 16:41:58'),
(110, NULL, 'view', '142.250.32.39', '2026-04-12 16:42:01'),
(111, NULL, 'view', '142.250.32.38', '2026-04-12 16:42:01'),
(112, NULL, 'view', '142.250.32.37', '2026-04-12 16:42:05'),
(113, NULL, 'view', '142.250.32.38', '2026-04-12 16:42:05'),
(114, NULL, 'view', '142.250.32.38', '2026-04-12 16:46:48'),
(115, NULL, 'view', '142.250.32.38', '2026-04-12 16:46:48'),
(116, NULL, 'view', '74.125.208.231', '2026-04-12 16:46:52'),
(117, NULL, 'view', '74.125.208.229', '2026-04-12 16:46:52'),
(118, NULL, 'view', '74.125.208.230', '2026-04-12 16:46:56'),
(119, NULL, 'view', '74.125.208.231', '2026-04-12 16:46:57'),
(120, NULL, 'view', '91.146.141.204', '2026-04-12 16:53:08'),
(121, NULL, 'view', '142.250.32.39', '2026-04-12 16:57:53'),
(122, NULL, 'view', '142.250.32.38', '2026-04-12 16:57:53'),
(123, NULL, 'view', '142.250.32.37', '2026-04-12 16:57:56'),
(124, NULL, 'view', '74.125.208.230', '2026-04-12 16:57:57'),
(125, NULL, 'view', '142.250.32.39', '2026-04-12 16:58:00'),
(126, NULL, 'view', '74.125.208.230', '2026-04-12 16:58:01'),
(127, NULL, 'view', '3.125.115.145', '2026-04-12 16:58:56'),
(128, NULL, 'view', '142.250.32.39', '2026-04-12 17:08:25'),
(129, NULL, 'view', '142.250.32.40', '2026-04-12 17:08:25'),
(130, NULL, 'view', '66.102.9.5', '2026-04-12 17:08:29'),
(131, NULL, 'view', '142.250.32.39', '2026-04-12 17:08:29'),
(132, NULL, 'view', '66.102.9.5', '2026-04-12 17:08:33'),
(133, NULL, 'view', '142.250.32.39', '2026-04-12 17:08:34'),
(134, NULL, 'view', '142.250.32.40', '2026-04-12 17:09:07'),
(135, NULL, 'view', '142.250.32.39', '2026-04-12 17:09:07'),
(136, NULL, 'view', '74.125.208.231', '2026-04-12 17:09:10'),
(137, NULL, 'view', '74.125.208.230', '2026-04-12 17:09:15'),
(138, NULL, 'view', '91.146.141.204', '2026-04-12 17:09:27'),
(139, NULL, 'view', '91.146.141.204', '2026-04-12 17:10:26'),
(140, NULL, 'view', '91.146.141.204', '2026-04-12 17:12:43'),
(141, 1, 'demo_click', '91.146.141.204', '2026-04-12 17:13:10'),
(142, NULL, 'view', '91.146.141.204', '2026-04-12 17:14:25'),
(143, NULL, 'view', '91.146.141.204', '2026-04-12 17:21:21'),
(144, NULL, 'view', '142.250.32.39', '2026-04-12 17:24:17'),
(145, NULL, 'view', '142.250.32.40', '2026-04-12 17:24:17'),
(146, NULL, 'view', '142.250.32.39', '2026-04-12 17:24:20'),
(147, NULL, 'view', '74.125.208.230', '2026-04-12 17:24:20'),
(148, NULL, 'view', '18.192.123.66', '2026-04-12 17:24:21'),
(149, NULL, 'view', '142.250.32.37', '2026-04-12 17:24:24'),
(150, NULL, 'view', '74.125.208.230', '2026-04-12 17:24:25'),
(151, NULL, 'view', '91.146.141.204', '2026-04-12 17:24:38'),
(152, NULL, 'view', '91.146.141.204', '2026-04-12 17:24:46'),
(153, NULL, 'view', '35.177.115.219', '2026-04-12 17:25:46'),
(154, NULL, 'view', '91.146.141.204', '2026-04-12 17:26:36'),
(155, NULL, 'view', '91.146.141.204', '2026-04-12 17:28:05'),
(156, NULL, 'view', '91.146.141.204', '2026-04-12 17:37:13'),
(157, NULL, 'view', '91.146.141.204', '2026-04-12 17:38:26'),
(158, NULL, 'view', '91.231.89.32', '2026-04-12 18:43:55'),
(159, NULL, 'view', '91.196.152.226', '2026-04-12 18:48:23'),
(160, NULL, 'view', '51.254.49.97', '2026-04-12 20:14:57'),
(161, NULL, 'view', '54.184.29.108', '2026-04-12 20:58:50'),
(162, NULL, 'view', '15.204.183.221', '2026-04-12 22:25:32'),
(163, NULL, 'view', '15.204.183.221', '2026-04-12 23:11:11'),
(164, NULL, 'view', '104.236.42.192', '2026-04-13 00:10:59'),
(165, NULL, 'view', '144.217.135.237', '2026-04-13 00:44:45'),
(166, NULL, 'view', '144.217.135.237', '2026-04-13 00:44:47'),
(167, 1, 'view', '144.217.135.237', '2026-04-13 00:44:47'),
(168, NULL, 'view', '144.217.135.237', '2026-04-13 00:44:49'),
(169, NULL, 'view', '149.56.150.85', '2026-04-13 00:44:58'),
(170, NULL, 'view', '149.56.150.85', '2026-04-13 00:45:02'),
(171, NULL, 'view', '149.56.150.85', '2026-04-13 00:45:05'),
(172, NULL, 'view', '204.101.161.15', '2026-04-13 01:24:10'),
(173, NULL, 'view', '45.154.98.78', '2026-04-13 01:51:03'),
(174, NULL, 'view', '45.154.98.78', '2026-04-13 01:51:03'),
(175, NULL, 'view', '134.122.47.173', '2026-04-13 01:57:33'),
(176, NULL, 'view', '134.122.47.173', '2026-04-13 01:57:33'),
(177, NULL, 'view', '94.247.172.129', '2026-04-13 02:45:54'),
(178, NULL, 'view', '220.96.15.106', '2026-04-13 07:11:30'),
(179, NULL, 'view', '118.19.204.121', '2026-04-13 08:19:04'),
(180, NULL, 'view', '126.116.160.185', '2026-04-13 08:19:14'),
(181, NULL, 'view', '91.146.141.204', '2026-04-13 09:47:56'),
(182, 1, 'inquiry', '91.146.141.204', '2026-04-13 09:48:13'),
(183, NULL, 'view', '91.146.141.204', '2026-04-13 09:50:53'),
(184, 1, 'view', '91.146.141.204', '2026-04-13 09:50:59'),
(185, NULL, 'view', '91.146.141.204', '2026-04-13 10:52:48'),
(186, NULL, 'view', '84.225.173.226', '2026-04-13 12:41:08'),
(187, NULL, 'view', '84.225.173.226', '2026-04-13 13:06:38'),
(188, NULL, 'view', '45.154.98.78', '2026-04-13 13:09:39'),
(189, NULL, 'view', '45.154.98.78', '2026-04-13 13:09:39'),
(190, NULL, 'view', '91.146.141.204', '2026-04-13 16:57:50'),
(191, NULL, 'view', '34.91.45.114', '2026-04-13 20:04:42'),
(192, NULL, 'view', '88.218.172.237', '2026-04-13 21:41:30'),
(193, NULL, 'view', '94.21.138.0', '2026-04-13 22:14:11'),
(194, NULL, 'view', '192.104.34.34', '2026-04-13 23:00:09'),
(195, 1, 'view', '192.104.34.34', '2026-04-13 23:00:12'),
(196, NULL, 'view', '103.115.186.154', '2026-04-13 23:45:14'),
(197, NULL, 'view', '190.99.80.156', '2026-04-13 23:57:28'),
(198, NULL, 'view', '142.111.99.188', '2026-04-13 23:57:35'),
(199, NULL, 'view', '146.112.163.35', '2026-04-14 07:13:09'),
(200, NULL, 'view', '46.228.199.158', '2026-04-14 09:38:00'),
(201, NULL, 'view', '91.146.141.204', '2026-04-14 09:39:57'),
(202, NULL, 'view', '155.2.226.162', '2026-04-14 10:05:50'),
(203, NULL, 'view', '91.146.141.204', '2026-04-14 10:48:27'),
(204, NULL, 'view', '23.27.145.159', '2026-04-14 11:07:49'),
(205, NULL, 'view', '23.27.145.196', '2026-04-14 11:08:30'),
(206, NULL, 'view', '91.146.141.204', '2026-04-14 11:35:29'),
(207, 1, 'demo_click', '91.146.141.204', '2026-04-14 11:35:34'),
(208, NULL, 'view', '91.146.141.204', '2026-04-14 12:17:48'),
(209, 1, 'inquiry', '91.146.141.204', '2026-04-14 12:18:02'),
(210, NULL, 'view', '91.146.141.204', '2026-04-14 12:19:01'),
(211, NULL, 'view', '91.146.141.204', '2026-04-14 12:29:11'),
(212, NULL, 'view', '149.57.180.7', '2026-04-14 12:46:59'),
(213, NULL, 'view', '23.27.145.240', '2026-04-14 12:48:21'),
(214, NULL, 'view', '23.27.145.181', '2026-04-14 13:38:15'),
(215, NULL, 'view', '149.57.180.182', '2026-04-14 13:55:21'),
(216, NULL, 'view', '170.62.100.233', '2026-04-14 15:38:52'),
(217, NULL, 'view', '176.125.229.29', '2026-04-14 19:24:18'),
(218, NULL, 'view', '130.89.144.166', '2026-04-14 21:40:01'),
(219, NULL, 'view', '86.111.225.100', '2026-04-14 23:30:45'),
(220, NULL, 'view', '165.232.184.69', '2026-04-14 23:40:11'),
(221, NULL, 'view', '204.101.161.15', '2026-04-15 00:38:34'),
(222, NULL, 'view', '18.210.10.77', '2026-04-15 01:14:25'),
(223, NULL, 'view', '3.84.153.63', '2026-04-15 01:14:30'),
(224, NULL, 'view', '168.119.80.126', '2026-04-15 02:12:49'),
(225, NULL, 'view', '23.88.69.86', '2026-04-15 02:12:51'),
(226, NULL, 'view', '168.119.80.126', '2026-04-15 02:12:51'),
(227, NULL, 'view', '168.119.80.126', '2026-04-15 02:12:53'),
(228, NULL, 'view', '168.119.80.126', '2026-04-15 02:12:55'),
(229, NULL, 'view', '168.119.80.126', '2026-04-15 02:12:58'),
(230, NULL, 'view', '91.146.141.204', '2026-04-15 08:49:42'),
(231, 3, 'view', '91.146.141.204', '2026-04-15 08:49:50'),
(232, 3, 'view', '91.146.141.204', '2026-04-15 09:37:23'),
(233, 3, 'demo_click', '91.146.141.204', '2026-04-15 09:37:43'),
(234, NULL, 'view', '3.229.138.143', '2026-04-15 09:40:13'),
(235, NULL, 'view', '3.229.138.143', '2026-04-15 09:41:07'),
(236, 3, 'view', '91.146.141.204', '2026-04-15 09:49:03'),
(237, 3, 'view', '91.146.141.204', '2026-04-15 09:51:14'),
(238, 3, 'view', '91.146.141.204', '2026-04-15 09:52:09'),
(239, NULL, 'view', '98.92.26.173', '2026-04-15 09:52:51'),
(240, NULL, 'view', '98.92.26.173', '2026-04-15 09:53:42'),
(241, NULL, 'view', '91.146.141.204', '2026-04-15 09:55:28'),
(242, 3, 'view', '91.146.141.204', '2026-04-15 09:55:34'),
(243, 3, 'view', '91.146.141.204', '2026-04-15 09:57:13'),
(244, 3, 'view', '91.146.141.204', '2026-04-15 10:01:36'),
(245, NULL, 'view', '91.146.141.204', '2026-04-15 10:04:39'),
(246, 3, 'view', '91.146.141.204', '2026-04-15 10:07:09');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `systems`
--

CREATE TABLE `systems` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL COMMENT 'JSON tömb',
  `demo_url` varchar(255) DEFAULT NULL,
  `preview_images` text DEFAULT NULL COMMENT 'JSON tömb',
  `thumbnail` varchar(255) DEFAULT NULL,
  `price_one_time` decimal(10,2) DEFAULT NULL,
  `price_monthly` decimal(10,2) DEFAULT NULL,
  `price_yearly` decimal(10,2) DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL COMMENT 'pl. Népszerű, Új, Akció',
  `badge_color` varchar(20) DEFAULT '#c8a96e',
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `systems`
--

INSERT INTO `systems` (`id`, `category_id`, `name`, `slug`, `tagline`, `description`, `features`, `demo_url`, `preview_images`, `thumbnail`, `price_one_time`, `price_monthly`, `price_yearly`, `badge`, `badge_color`, `sort_order`, `active`, `created_at`) VALUES
(1, 5, 'Barber Shop Foglalási Rendszer', 'barber-shop-foglalasi-rendszer', 'Professzionális online időpontfoglalás borbélyoknak és fodrászoknak', 'Teljesen automatizált foglalási rendszer borbély szalonok és fodrászatok számára. \r\nÜgyfeleid 0-24 órában foglalhatnak időpontot, te pedig mindent egy helyen kezelhetsz \r\naz átlátható admin felületen keresztül. Nincs több telefonos egyeztetés – \r\na rendszer elvégzi helyetted!', '[\"Online időpontfoglalás 0-24 órában\",\"Automata email értesítők ügyfeleknek\",\"Admin felület foglalások kezeléséhez\",\"Szolgáltatások és árak kezelése\",\"Több fodrász \\/ alkalmazott kezelése\",\"Naptár nézet és időpontok áttekintése\",\"Foglalás visszaigazolás és lemondás\",\"Email emlékeztetők\",\"Mobilbarát, reszponzív dizájn\",\"Egyedi arculat és színvilág\",\"Statisztikák és kimutatások\",\"GDPR megfelelő adatkezelés\"]', 'http://localhost/barber', NULL, 'sys_1775991236_69db79c47d9a0.jpg', 100000.00, 0.00, 0.00, 'Új rendszer', '#b4cbcf', 0, 1, '2026-04-12 12:22:03'),
(3, 1, 'Fogászati foglalási rendszer', 'fogaszati-foglalasi-rendszer', 'Fogászati foglalási rendszer', 'A Mintadent egy modern, reszponzív weboldal és admin felület, amely egy fogászati rendelő online időpontfoglalását támogatja. A látogatói felületen a páciensek egy több lépéses (wizard) foglalási folyamaton keresztül tudnak időpontot kérni: először kezelést választanak, majd fogorvost, ezután egy naptár/időpont listából kiválasztják a szabad időpontot, végül megadják személyes adataikat és megjegyzést írhatnak. A rendszer automatikusan kezeli a foglalások ütközését, az időtartamokat (kezelés hossza), és a szabad időpontokat a munkaidő + kivételek + meglévő foglalások alapján számolja.\r\n\r\nAz admin felületen az üzemeltető a foglalásokat listában és napi nézetben is tudja kezelni, státuszt állítani (függőben/megerősítve/teljesítve/lemondva), új foglalást kézzel felvenni, illetve törölni. A rendszer e-mail értesítéseket is kezel: a páciens foglalásakor visszaigazolást kap, az admin új foglalásról értesítést kap, valamint státuszváltozáskor a páciens tájékoztató e-mailt kap.\r\n\r\nAz oldal SEO-barát útvonalakat használ (.htaccess rewrite szabályok), külön útvonalon kezeli az API végpontokat és az admin részt, és támogat blog/lista + blog bejegyzés oldalakat is.', '[\"1) Látogatói (frontend) funkciók\",\"Online időpontfoglalás (wizard)\",\"Kezelés kiválasztása (kategóriák szerint csoportosítva)\",\"Fogorvos kiválasztása (előválasztható paraméterből, pl. főoldalról érkezve)\",\"Dátum választás (min\\/max határokkal)\",\"Szabad időpontok automatikus betöltése API-ból\",\"Személyes adatok megadása (név, e-mail, telefon opcionális)\",\"Megjegyzés mező\",\"Adatvédelmi checkbox (GDPR)\",\"Siker oldal foglalási azonosítóval\",\"Dinamikus oldalak slug alapján (page.php?slug=...)\",\"Blog\",\"Blog lista (\\/blog)\",\"Blog bejegyzés slug alapján (\\/blog\\/{slug})\",\"Kapcsolatfelvétel\",\"Kapcsolati űrlap beküldés API-ra\",\"Üzenet mentése adatbázisba\",\"Admin értesítő e-mail (ha be van állítva)\",\"2) Foglalási logika \\/ időpont-kezelés\",\"Szabad időpontok számítása:\",\"Munkaidő (working_hours)\",\"Napi kivételek \\/ zárások (schedule_exceptions)\",\"Meglévő foglalásokkal ütközés kizárása\",\"Kezelés időtartama alapján slotok képzése\",\"Ütközésvédelem: nem enged foglalni már foglalt időre\",\"Rate limit: emailenként limitált foglalási próbálkozás (pl. 5\\/óra)\",\"Foglalási azonosító generálás (booking_ref)\",\"3) E-mail értesítések\",\"Páciens visszaigazolás foglalás létrehozásakor\",\"Admin értesítés új foglalás érkezésekor\",\"Státusz e-mail státuszváltoztatáskor (pl. confirmed\\/cancelled\\/completed)\",\"HTML formátumú, designolt e-mailek (Mintadent stílus)\",\"4) Admin funkciók\",\"Bejelentkezés-védelem (require_login)\",\"Foglalások kezelése\",\"Lista nézet (szűrés: fogorvos, státusz, keresés)\",\"Napi nézet (fogorvos oszlopokkal)\",\"Státusz módosítás (CSRF védelemmel)\",\"Törlés (CSRF védelemmel)\",\"Új foglalás kézi rögzítése adminból\"]', 'https://demo2.foglalasi-rendszer.hu/', NULL, 'sys_1776238638_69df402ed8df7.jpg', 100000.00, NULL, NULL, 'Új rendszer', '#c8a96e', 0, 1, '2026-04-15 08:44:57');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `system_pages`
--

CREATE TABLE `system_pages` (
  `id` int(11) NOT NULL,
  `system_id` int(11) NOT NULL,
  `hero_title` varchar(255) DEFAULT NULL,
  `hero_subtitle` text DEFAULT NULL,
  `content_blocks` longtext DEFAULT NULL COMMENT 'JSON tömbök',
  `gallery` text DEFAULT NULL COMMENT 'JSON képtömb',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `schema_type` varchar(50) DEFAULT 'SoftwareApplication',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `system_pages`
--

INSERT INTO `system_pages` (`id`, `system_id`, `hero_title`, `hero_subtitle`, `content_blocks`, `gallery`, `meta_title`, `meta_description`, `meta_keywords`, `og_image`, `schema_type`, `updated_at`) VALUES
(1, 1, 'Barber Shop Foglalási Rendszer', 'Professzionális online időpontfoglalás borbélyoknak és fodrászoknak', '[]', '[]', '', '', '', NULL, 'SoftwareApplication', '2026-04-12 13:50:35');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `system_id` int(11) DEFAULT NULL COMMENT 'NULL = általános vélemény',
  `name` varchar(100) NOT NULL,
  `position` varchar(150) DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `rating` tinyint(1) DEFAULT 5,
  `content` text NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- A tábla indexei `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- A tábla indexei `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `system_id` (`system_id`);

--
-- A tábla indexei `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `system_id` (`system_id`);

--
-- A tábla indexei `pricing_plans`
--
ALTER TABLE `pricing_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `system_id` (`system_id`);

--
-- A tábla indexei `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- A tábla indexei `stats`
--
ALTER TABLE `stats`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `systems`
--
ALTER TABLE `systems`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- A tábla indexei `system_pages`
--
ALTER TABLE `system_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `system_id` (`system_id`);

--
-- A tábla indexei `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `system_id` (`system_id`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT a táblához `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT a táblához `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT a táblához `pricing_plans`
--
ALTER TABLE `pricing_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `stats`
--
ALTER TABLE `stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=247;

--
-- AUTO_INCREMENT a táblához `systems`
--
ALTER TABLE `systems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `system_pages`
--
ALTER TABLE `system_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT a táblához `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `faqs`
--
ALTER TABLE `faqs`
  ADD CONSTRAINT `faqs_ibfk_1` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE SET NULL;

--
-- Megkötések a táblához `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE SET NULL;

--
-- Megkötések a táblához `pricing_plans`
--
ALTER TABLE `pricing_plans`
  ADD CONSTRAINT `pricing_plans_ibfk_1` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `systems`
--
ALTER TABLE `systems`
  ADD CONSTRAINT `systems_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Megkötések a táblához `system_pages`
--
ALTER TABLE `system_pages`
  ADD CONSTRAINT `system_pages_ibfk_1` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `testimonials`
--
ALTER TABLE `testimonials`
  ADD CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
