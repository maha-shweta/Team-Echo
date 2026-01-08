-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 08, 2026 at 07:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `team_echo_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `created_at`) VALUES
(1, 'Data check', '2026-01-08 17:27:29');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `feedback_text` text NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sentiment_score` decimal(5,2) DEFAULT NULL,
  `sentiment_label` varchar(50) DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `priority` enum('low','medium','high','critical') NOT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `feedback_text`, `is_anonymous`, `submitted_at`, `sentiment_score`, `sentiment_label`, `is_resolved`, `priority`, `resolved_by`, `resolved_at`, `user_id`, `category_id`) VALUES
(1, 'hi bros kemon choltese dinkal? arektu check kore nei. please bhai, kaam kor', 1, '2026-01-08 17:28:21', NULL, NULL, 1, 'medium', 1, '2026-01-08 13:12:59', 3, 1),
(2, 'dekhi second time', 1, '2026-01-08 18:24:24', NULL, NULL, 1, 'medium', 2, '2026-01-08 13:24:58', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `feedback_attachments`
--

CREATE TABLE `feedback_attachments` (
  `attachment_id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_attachments`
--

INSERT INTO `feedback_attachments` (`attachment_id`, `feedback_id`, `file_name`, `file_path`, `file_size`, `uploaded_by`, `uploaded_at`) VALUES
(1, 1, 'Member1jpg.jpg', 'uploads/feedback_attachments/695fe9350f78d4.27568324.jpg', 86827, 3, '2026-01-08 17:28:21');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_comments`
--

CREATE TABLE `feedback_comments` (
  `comment_id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_comments`
--

INSERT INTO `feedback_comments` (`comment_id`, `feedback_id`, `user_id`, `comment_text`, `parent_comment_id`, `is_internal`, `created_at`, `edited_at`) VALUES
(1, 1, 3, 'yolo bois', NULL, 0, '2026-01-08 17:30:30', NULL),
(4, 1, 1, 'ayyo, nub', NULL, 1, '2026-01-08 17:40:22', '2026-01-08 17:56:19'),
(5, 1, 3, 'dekhi ektu', 1, 0, '2026-01-08 18:18:45', NULL),
(6, 2, 4, '147258369', NULL, 0, '2026-01-08 18:24:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedback_tags`
--

CREATE TABLE `feedback_tags` (
  `feedback_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_tags`
--

INSERT INTO `feedback_tags` (`feedback_id`, `tag_id`, `added_by`, `added_at`) VALUES
(1, 1, 1, '2026-01-08 17:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_votes`
--

CREATE TABLE `feedback_votes` (
  `vote_id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('upvote','downvote') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_votes`
--

INSERT INTO `feedback_votes` (`vote_id`, `feedback_id`, `user_id`, `vote_type`, `created_at`) VALUES
(1, 1, 1, 'upvote', '2026-01-08 17:42:17');

-- --------------------------------------------------------

--
-- Table structure for table `heatmap_data`
--

CREATE TABLE `heatmap_data` (
  `heatmap_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `heat_intensity` decimal(5,2) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `management_user`
--

CREATE TABLE `management_user` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','hr','user') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `management_user`
--

INSERT INTO `management_user` (`user_id`, `name`, `email`, `password_hash`, `role`, `created_at`, `last_login`) VALUES
(1, 'Rishta', '19@gmail.com', '$2y$10$fgXY92qmvRjMLmJr.hfWmO9qXTC1Uf4FLc3AK9E1bJipvFGJLtDVC', 'admin', '2026-01-08 17:08:52', NULL),
(2, 'Mahashweta', '42@gmail.com', '$2y$10$KvFuPSS1c2MNFKZUEhCcHOyf6CHzXVsLaen3dOH4Sn27dFEdgwMG.', 'hr', '2026-01-08 17:10:10', NULL),
(3, 'Tasmia', '58@gmail.com', '$2y$10$iG44B8Z9zYFUmnRdiSBVW.1I.UxVhvxek5OgMfPUzFhzKRPFRmL82', 'user', '2026-01-08 17:11:11', NULL),
(4, 'Tasnia', '65@gmail.com', '$2y$10$WwV2spNWK5T5b4WlmFxi.OOZXF2F2XT2NZhkNWmccpVclI63X1Rp.', 'user', '2026-01-08 18:23:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_history`
--

CREATE TABLE `report_history` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_type` varchar(255) NOT NULL,
  `report_path` varchar(255) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sentiment_summary`
--

CREATE TABLE `sentiment_summary` (
  `summary_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `positive_count` int(11) DEFAULT 0,
  `neutral_count` int(11) DEFAULT 0,
  `negative_count` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(255) NOT NULL,
  `tag_color` varchar(7) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`tag_id`, `tag_name`, `tag_color`, `created_at`) VALUES
(1, 'Yo bois', '#1adb4a', '2026-01-08 17:31:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `feedback_attachments`
--
ALTER TABLE `feedback_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `feedback_id` (`feedback_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `feedback_comments`
--
ALTER TABLE `feedback_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `feedback_id` (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_parent_comment` (`parent_comment_id`);

--
-- Indexes for table `feedback_tags`
--
ALTER TABLE `feedback_tags`
  ADD PRIMARY KEY (`feedback_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `feedback_votes`
--
ALTER TABLE `feedback_votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD KEY `feedback_id` (`feedback_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `heatmap_data`
--
ALTER TABLE `heatmap_data`
  ADD PRIMARY KEY (`heatmap_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `management_user`
--
ALTER TABLE `management_user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `report_history`
--
ALTER TABLE `report_history`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sentiment_summary`
--
ALTER TABLE `sentiment_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`tag_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feedback_attachments`
--
ALTER TABLE `feedback_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback_comments`
--
ALTER TABLE `feedback_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `feedback_votes`
--
ALTER TABLE `feedback_votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `heatmap_data`
--
ALTER TABLE `heatmap_data`
  MODIFY `heatmap_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `management_user`
--
ALTER TABLE `management_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `report_history`
--
ALTER TABLE `report_history`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sentiment_summary`
--
ALTER TABLE `sentiment_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `management_user` (`user_id`),
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `feedback_attachments`
--
ALTER TABLE `feedback_attachments`
  ADD CONSTRAINT `feedback_attachments_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`feedback_id`),
  ADD CONSTRAINT `feedback_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `management_user` (`user_id`);

--
-- Constraints for table `feedback_comments`
--
ALTER TABLE `feedback_comments`
  ADD CONSTRAINT `feedback_comments_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`feedback_id`),
  ADD CONSTRAINT `feedback_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `management_user` (`user_id`),
  ADD CONSTRAINT `feedback_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `feedback_comments` (`comment_id`),
  ADD CONSTRAINT `fk_parent_comment` FOREIGN KEY (`parent_comment_id`) REFERENCES `feedback_comments` (`comment_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_tags`
--
ALTER TABLE `feedback_tags`
  ADD CONSTRAINT `feedback_tags_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`feedback_id`),
  ADD CONSTRAINT `feedback_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`),
  ADD CONSTRAINT `feedback_tags_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `management_user` (`user_id`);

--
-- Constraints for table `feedback_votes`
--
ALTER TABLE `feedback_votes`
  ADD CONSTRAINT `feedback_votes_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`feedback_id`),
  ADD CONSTRAINT `feedback_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `management_user` (`user_id`);

--
-- Constraints for table `heatmap_data`
--
ALTER TABLE `heatmap_data`
  ADD CONSTRAINT `heatmap_data_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `report_history`
--
ALTER TABLE `report_history`
  ADD CONSTRAINT `report_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `management_user` (`user_id`);

--
-- Constraints for table `sentiment_summary`
--
ALTER TABLE `sentiment_summary`
  ADD CONSTRAINT `sentiment_summary_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
