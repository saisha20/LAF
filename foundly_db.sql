SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(10) NOT NULL DEFAULT '',
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','super_admin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin` (`admin_id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `created_at`) VALUES
(1, 'System Admin', 'admin@kathford.edu.np', '9800000000', '$2y$10$vqR2do04riTouJCnTEXd7.3WaHvcFwgoMy9oZuUv007DRFIESZ.nm', 'admin', '2026-08-20 14:52:47');

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(5, 'Accessories'),
(3, 'Bags'),
(7, 'Books & Stationery'),
(4, 'Clothing'),
(2, 'Documents / ID Cards'),
(1, 'Electronics'),
(6, 'Keys'),
(8, 'Other');

CREATE TABLE `matches` (
  `match_id` int(11) NOT NULL,
  `lost_report_id` int(11) NOT NULL,
  `found_report_id` int(11) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('match_approved','potential_match','claim_review','system') NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `link_report_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('lost','found') NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `reward_amount` decimal(10,2) DEFAULT NULL,
  `condition_status` enum('new','good','fair','poor') DEFAULT NULL,
  `location` varchar(150) NOT NULL,
  `notes` text DEFAULT NULL,
  `date_reported` date NOT NULL,
  `status` enum('open','matched','closed') NOT NULL DEFAULT 'open',
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo_data` longblob DEFAULT NULL,
  `photo_type` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reports` (`report_id`, `user_id`, `type`, `item_name`, `category_id`, `description`, `color`, `brand`, `reward_amount`, `condition_status`, `location`, `notes`, `date_reported`, `status`, `is_public`, `created_at`, `photo_data`, `photo_type`) VALUES
(1, 3, 'found', 'Dell Computer', 1, 'Black color dell laptop', NULL, NULL, NULL, 'fair', 'balkumari kathford college', NULL, '2026-08-14', 'open', 1, '2026-08-13 07:03:35', NULL, NULL),
(2, 3, 'lost', 'BOOK', 7, 'NUMERICAL METHOD BOOK OF 4TH SEMESTER', 'GREEN', 'ASMITA', 100.00, NULL, 'Not specified', NULL, '2026-08-19', 'open', 1, '2026-08-19 02:30:18', NULL, NULL);

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('student','staff') NOT NULL DEFAULT 'student',
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password_hash`, `user_type`, `role`, `created_at`) VALUES
(1, 'Pradosh Pokharel', 'pradosh.081@kathford.edu.np', '9812345678', '$2y$10$JxiYaabCXlPGsl1BbjIzc.VLywv9hfZjxzupMG3fx/TZzEqWsdDQC', 'student', 'user', '2026-08-11 03:09:21'),
(2, 'Saisha Budhathoki', 'saishabudathoki.081@kathford.edu.np', '9844641491', '$2y$10$/YD4USMwQNfAoSEWBTBg1ezS6r6bmReyQ4Ej8Qp2WAuf00.AzS5hC', 'student', 'user', '2026-08-11 04:02:19'),
(3, 'Anuja Adhikari', 'anujaadhikari.081@kathford.edu.np', '9802727272', '$2y$10$NL7GrpSYrdj1Luga1CraR.HAeeXrXxXenS9vNdghAmE1uJNk9gHgS', 'student', 'user', '2026-08-11 04:03:58'),
(4, 'Simron Sigdel', 'simronsidgel.081@kathford.edu.np', '9877745324', '$2y$10$lYQofnDFl63yitR9IiCqjO/RRFfWdul5UATAURmOnzX5JnQ8qrJcO', 'student', 'user', '2026-08-11 06:02:58');

ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

ALTER TABLE `matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `fk_matches_lost` (`lost_report_id`),
  ADD KEY `fk_matches_found` (`found_report_id`),
  ADD KEY `fk_matches_admin` (`verified_by`);

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notifications_user` (`user_id`),
  ADD KEY `fk_notifications_report` (`link_report_id`);

ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_reports_user` (`user_id`),
  ADD KEY `fk_reports_category` (`category_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `matches`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

ALTER TABLE `matches`
  ADD CONSTRAINT `fk_matches_admin` FOREIGN KEY (`verified_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_found` FOREIGN KEY (`found_report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_matches_lost` FOREIGN KEY (`lost_report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_report` FOREIGN KEY (`link_report_id`) REFERENCES `reports` (`report_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;
