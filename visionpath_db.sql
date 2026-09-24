-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 11, 2026 at 11:48 AM
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
-- Database: `visionpath_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `stream` varchar(100) DEFAULT NULL,
  `assigned_teacher_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_class` (`school_id`,`class_name`),
  KEY `idx_classes_school` (`school_id`),
  KEY `idx_classes_teacher` (`assigned_teacher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `school_id`, `class_name`, `stream`, `assigned_teacher_id`, `created_at`) VALUES
(1, 1, 'Grade 8', NULL, 2, '2026-08-29 11:13:59'),
(2, 2, 'Grade 10', 'South', 7, '2026-08-31 10:01:14'),
(3, 4, 'Grade 10', 'Achievers', 15, '2026-09-08 06:08:32');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_profiles`
--

DROP TABLE IF EXISTS `mentor_profiles`;
CREATE TABLE IF NOT EXISTS `mentor_profiles` (
  `user_id` int NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `institution_company` varchar(150) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `cbc_pathway_interest` enum('STEM','Social Sciences','Arts & Sports Science') NOT NULL,
  `linkedin_url` varchar(255) NOT NULL DEFAULT '',
  `mentorship_capacity` int NOT NULL DEFAULT '5',
  `professional_bio` text,
  `is_verified_mentor` tinyint(1) DEFAULT '0',
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  KEY `idx_mentor_profiles_approval_pathway` (`is_approved`,`cbc_pathway_interest`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mentor_profiles`
--

INSERT INTO `mentor_profiles` (`user_id`, `full_name`, `institution_company`, `job_title`, `cbc_pathway_interest`, `linkedin_url`, `mentorship_capacity`, `professional_bio`, `is_verified_mentor`, `is_approved`) VALUES
(3, 'Alex Ochieng\'', 'TUM', 'software', 'STEM', 'https://www.linkedin.com/in/maxwel-odhiambo', 5, 'Working at KPA', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `code_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_email` (`email`),
  KEY `idx_password_resets_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `code_hash`, `expires_at`, `created_at`) VALUES
(3, 'odhimb136@tum.ac', '3cfed37941413e2dca69ea40874c27f71273a20cc8e8886fa027f4cef355da37', '2026-09-07 20:32:12', '2026-09-07 20:17:12'),
(12, 'odhiam136@gmail.com', 'e637cb866aa6d27e6a0054566c83a3eabe9d8eee14d7728cf9b0d9035cbcec1a', '2026-09-10 17:12:05', '2026-09-10 13:57:05'),
(13, 'odhiambom136@gmail.com', '4c25a47f70b7c3192ed1cb0c62de31867e939a1e85691c284082bd938a470fa5', '2026-09-10 20:45:57', '2026-09-10 17:30:57');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `uploaded_by_teacher_id` int NOT NULL,
  `project_title` varchar(150) NOT NULL,
  `project_description` text NOT NULL,
  `cbc_strand` varchar(100) NOT NULL,
  `project_documentation` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_projects_student` (`student_id`),
  KEY `fk_projects_teacher` (`uploaded_by_teacher_id`),
  KEY `idx_projects_cbc_created` (`cbc_strand`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_feedback`
--

DROP TABLE IF EXISTS `project_feedback`;
CREATE TABLE IF NOT EXISTS `project_feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `mentor_id` int NOT NULL,
  `feedback_text` text NOT NULL,
  `is_referee` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mentor_id` (`mentor_id`),
  KEY `idx_project_feedback_project_mentor` (`project_id`,`mentor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

DROP TABLE IF EXISTS `schools`;
CREATE TABLE IF NOT EXISTS `schools` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_name` varchar(255) NOT NULL,
  `county` varchar(100) NOT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_name_county` (`school_name`,`county`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `school_name`, `county`, `sub_county`, `created_at`) VALUES
(1, 'Gendia', 'Unassigned', NULL, '2026-08-29 11:13:59'),
(2, 'TUM', 'Unassigned', NULL, '2026-08-29 11:13:59'),
(3, 'Olwalo Senior school', 'Kisumu', 'Nyakach', '2026-08-31 08:08:56'),
(4, 'Kondity', 'Kisumu', 'Nyakach', '2026-09-07 18:36:57');

-- --------------------------------------------------------

--
-- Table structure for table `student_transfers`
--

DROP TABLE IF EXISTS `student_transfers`;
CREATE TABLE IF NOT EXISTS `student_transfers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `from_school_id` int NOT NULL,
  `to_school_id` int NOT NULL,
  `requested_by_admin_id` int NOT NULL,
  `approved_by_admin_id` int DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `request_reason` text,
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_student_transfers_to_school` (`to_school_id`),
  KEY `fk_student_transfers_request_admin` (`requested_by_admin_id`),
  KEY `fk_student_transfers_approved_admin` (`approved_by_admin_id`),
  KEY `idx_student_transfers_status` (`status`),
  KEY `idx_student_transfers_student` (`student_id`),
  KEY `idx_student_transfers_from_to` (`from_school_id`,`to_school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admission_number` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `school_name` varchar(100) DEFAULT NULL,
  `assigned_class` varchar(100) DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','teacher','parent','mentor','super_admin','junior_admin') NOT NULL,
  `school_id` int DEFAULT NULL,
  `transfer_status` enum('normal','transfer_pending') NOT NULL DEFAULT 'normal',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `admission_number` (`admission_number`),
  KEY `idx_users_role_school_class` (`role`,`school_name`,`assigned_class`),
  KEY `idx_users_school_id` (`school_id`),
  KEY `idx_users_role_school` (`role`,`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `admission_number`, `name`, `school_name`, `assigned_class`, `class_id`, `email`, `password_hash`, `role`, `school_id`, `transfer_status`, `is_verified`, `created_at`) VALUES
(2, NULL, 'MAXWEL ODHIAMBO', 'Gendia', 'Grade 8', NULL, 'odhimb136@tum.ac', '$2y$10$cUbB9olQKiTorTSnei0ieOOHxewsymji2555MU.vrortOkqFzEN8y', 'teacher', 1, 'normal', 1, '2026-08-28 10:12:34'),
(3, NULL, 'Alex Ochieng\'', 'TUM', 'Mentor', NULL, 'bsee706j2023@students.tum.ac.ke', '$2y$10$0B7YXDE9Ydl0Bl1eGId4D.S/z.n99hM./Ja8W5uxDq.TJBRZf9fOS', 'mentor', 2, 'normal', 1, '2026-08-28 10:26:52'),
(5, NULL, 'Noel Kisera', 'Gendia', 'Junior Admin', NULL, 'kiseranoel123@gmail.com', '$2y$10$E4IP38M0tJn9hVnFafWzpOZSukwhd2QaP8xMVgr9aEkp7TXlDFs4G', 'junior_admin', 1, 'normal', 1, '2026-08-31 08:22:14'),
(7, NULL, 'Joseph Mulatia', 'School', 'Grade 10 - South', 2, 'odhiam136@gmail.com', '$2y$10$25PjhTfSRmf0z56q1ZN6quT7ogAD7e/iypVBygVhXgj1KDCCppjNe', '', 2, 'normal', 1, '2026-08-31 10:02:09'),
(8, '9748', 'MAXWEL ODHIAMBO', 'Gendia', 'Grade 8', NULL, 'maxwelokello123@gmail.com', '$2y$10$h9nC5TQ99pCXedAMhbwwp.SeYQFywTTlfiRWXkLoVNWKzlWk6iX8K', 'student', NULL, 'normal', 1, '2026-08-31 14:07:47'),
(9, NULL, 'Oliver Mutety', 'Kondity', 'Junior Admin', NULL, 'ambom136@gmail.com', '$2y$10$knoUajh4Ba6jRcG4DI0pWeaRFkCIv00aGzR434GYVswkXMBckvdkm', 'junior_admin', 4, 'normal', 1, '2026-09-07 18:38:07'),
(10, NULL, 'Oliver Mutety', 'Kondity', 'Junior Admin', NULL, 'odhiambom136@gmail.com', '$2y$10$555IUT7xo0gVVS1AHWa5RuDAJdGmpXOXOlWW684rjCa2G5CW585L6', 'junior_admin', 4, 'normal', 1, '2026-09-07 18:39:15'),
(11, NULL, 'Laura Peurity', 'Olwalo Senior school', 'Junior Admin', NULL, 'om136@gmail.com', '$2y$10$4talY2/ff6uWlAzR25K4xuLJtsdSk5iS4YAAR7tnrGQv4dJpdNLCS', 'junior_admin', 3, 'normal', 1, '2026-09-07 18:46:32'),
(12, NULL, 'Karen Wairimu', 'Kondity', 'Grade 10 - Achievers', 3, 'wairimu123@gmail.com', '$2y$10$dq9XMbLLKaWpTXJAApKuIe12NWG6BfectIJZgUogiuXjuTxhzXLuS', '', 4, 'normal', 1, '2026-09-08 06:09:36'),
(13, NULL, 'Joseph Mulatia', 'Kondity', 'Grade 10 - Achievers', 3, '6@gmail.com', '$2y$10$wKx/Zc0nsNdSETqVTrrsM.T7YTkF5P.1MMeSfxIR71tS/RML77NEO', '', 4, 'normal', 1, '2026-09-08 06:28:02'),
(14, NULL, 'Joseph Mulatia', 'Kondity', 'Grade 10 - Achievers', 3, 'odhiambom16@gmail.com', '$2y$10$9mAuQAN3ribd1Hyd1bLb7ObCxFs7ZY2qfEuK4PoUJbTBaYP6GuBzC', '', 4, 'normal', 1, '2026-09-08 06:47:00'),
(15, NULL, 'Joseph Mulatia', 'Kondity', 'Grade 10 - Achievers', 3, 'odhiambom6@gmail.com', '$2y$10$.1xkeeBcLimpMV5NGK7SjuAVBncOL47HBm39/jiTf2QvNBWhswqKe', '', 4, 'normal', 1, '2026-09-10 13:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `verification_codes`
--

DROP TABLE IF EXISTS `verification_codes`;
CREATE TABLE IF NOT EXISTS `verification_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `code` varchar(6) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_classes_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_classes_teacher` FOREIGN KEY (`assigned_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  ADD CONSTRAINT `mentor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_projects_teacher` FOREIGN KEY (`uploaded_by_teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_feedback`
--
ALTER TABLE `project_feedback`
  ADD CONSTRAINT `project_feedback_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_feedback_ibfk_2` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD CONSTRAINT `fk_student_transfers_approved_admin` FOREIGN KEY (`approved_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_student_transfers_from_school` FOREIGN KEY (`from_school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_student_transfers_request_admin` FOREIGN KEY (`requested_by_admin_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_student_transfers_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_student_transfers_to_school` FOREIGN KEY (`to_school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
