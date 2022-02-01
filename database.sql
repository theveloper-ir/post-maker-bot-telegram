-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 07, 2021 at 03:50 AM
-- Server version: 10.4.18-MariaDB
-- PHP Version: 7.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bottools_mediashare11bot`
--

-- --------------------------------------------------------

--
-- Table structure for table `complete_step_post`
--

DROP TABLE IF EXISTS `complete_step_post`;
CREATE TABLE `complete_step_post` (
  `id` int(11) NOT NULL,
  `title` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `complete_step_post`
--

INSERT INTO `complete_step_post` (`id`, `title`, `active`) VALUES
(1, 'created', 1),
(2, 'caption_edit', 1);

-- --------------------------------------------------------

--
-- Table structure for table `downloads_tbl`
--

DROP TABLE IF EXISTS `downloads_tbl`;
CREATE TABLE `downloads_tbl` (
  `id` int(11) NOT NULL,
  `users_tbl_id` int(11) NOT NULL,
  `files_tbl_id` int(11) NOT NULL,
  `created_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `options_tbl`
--

DROP TABLE IF EXISTS `options_tbl`;
CREATE TABLE `options_tbl` (
  `id` int(11) NOT NULL,
  `option_name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `option_value` varchar(300) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_520_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `options_tbl`
--

INSERT INTO `options_tbl` (`id`, `option_name`, `option_value`, `data`) VALUES
(1, 'send_broadcast_message', '0', '{\"type\":0,\"message\":null,\"block_count\":0,\"count_all_user\":0,\"is_start\":false,\"is_complete\":false,\"last_message\":\"\"}'),
(2, 'fist_last_messageID_in_channel', '1', NULL),
(3, 'admins_robot', '1', '[000000000]'),
(4, 'forward_channel', '1', NULL),
(5, 'bot_id', '1', 'botID'),
(6, 'channel_lock_robot', '1', NULL),
(7, 'bot_token_code', '1', 'TOKEN');

-- --------------------------------------------------------

--
-- Table structure for table `posts_tbl`
--

DROP TABLE IF EXISTS `posts_tbl`;
CREATE TABLE `posts_tbl` (
  `id` int(11) NOT NULL,
  `post_media_info` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `code` varchar(12) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `caption` text COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `post_type_id` tinyint(4) NOT NULL,
  `post_cover_info` text COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `creator_id` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `message_id_in_bot` text COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `message_id_in_channel` varchar(15) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `complete_step_post_id` tinyint(4) NOT NULL DEFAULT 1,
  `created_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_type_tbl`
--

DROP TABLE IF EXISTS `post_type_tbl`;
CREATE TABLE `post_type_tbl` (
  `id` int(11) NOT NULL,
  `title` varchar(30) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `post_type_tbl`
--

INSERT INTO `post_type_tbl` (`id`, `title`, `active`) VALUES
(1, 'video', 1),
(2, 'photo', 1),
(3, 'animation', 1),
(4, 'audio', 1),
(5, 'document', 1),
(6, 'voice', 1),
(7, 'video_note', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users_tbl`
--

DROP TABLE IF EXISTS `users_tbl`;
CREATE TABLE `users_tbl` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier for this user or bot',
  `user_id` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `is_bot` tinyint(1) DEFAULT 0 COMMENT 'True, if this user is a bot',
  `first_name` char(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '' COMMENT 'User''s or bot''s first name',
  `last_name` char(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'User''s or bot''s last name',
  `username` char(191) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'User''s or bot''s username',
  `language_code` char(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'IETF language tag of the user''s language',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Entry date creation',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Entry date update',
  `location_in_bot` varchar(5) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `complete_step_post`
--
ALTER TABLE `complete_step_post`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `downloads_tbl`
--
ALTER TABLE `downloads_tbl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `options_tbl`
--
ALTER TABLE `options_tbl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts_tbl`
--
ALTER TABLE `posts_tbl`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_id_in_channel` (`message_id_in_channel`);

--
-- Indexes for table `post_type_tbl`
--
ALTER TABLE `post_type_tbl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_tbl`
--
ALTER TABLE `users_tbl`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `complete_step_post`
--
ALTER TABLE `complete_step_post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `downloads_tbl`
--
ALTER TABLE `downloads_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `options_tbl`
--
ALTER TABLE `options_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `posts_tbl`
--
ALTER TABLE `posts_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_type_tbl`
--
ALTER TABLE `post_type_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users_tbl`
--
ALTER TABLE `users_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for this user or bot';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
