-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 03, 2026 at 08:11 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uems_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `created_at`) VALUES
(1, 'sdf', 'fds', '2026-01-24 07:17:40');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `organizer_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `event_type` enum('one_time','recurring') DEFAULT 'one_time',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `capacity` int(11) DEFAULT 0,
  `registration_deadline` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed') DEFAULT 'pending',
  `banner_image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `description`, `organizer_id`, `category_id`, `event_type`, `start_datetime`, `end_datetime`, `capacity`, `registration_deadline`, `status`, `banner_image`, `created_at`, `updated_at`) VALUES
(1, 'Community Cleanup', 'Join us to clean up the local park and riverbanks.', 1, 1, 'one_time', '2026-02-10 09:00:00', '2026-02-10 12:00:00', 50, '2026-02-08 18:00:00', 'approved', 'event.jpg', '2026-01-23 12:02:23', '2026-01-24 10:49:34'),
(2, 'Food Drive', 'Help us collect and distribute food to those in need.', 1, 2, 'one_time', '2026-02-15 10:00:00', '2026-02-15 15:00:00', 100, '2026-02-13 18:00:00', 'approved', 'event.jpg', '2026-01-23 12:02:23', '2026-01-24 10:49:39'),
(3, 'Tree Planting', 'Plant trees in urban areas to improve the environment.', 1, 3, 'one_time', '2026-02-20 08:00:00', '2026-02-20 12:00:00', 30, '2026-02-18 17:00:00', 'approved', 'event.jpg', '2026-01-23 12:02:23', '2026-01-24 10:49:45'),
(4, 'Charity Marathon', 'Run to raise funds for local charities.', 1, 4, 'one_time', '2026-03-05 06:00:00', '2026-03-05 12:00:00', 200, '2026-03-01 18:00:00', 'pending', 'event.jpg', '2026-01-23 12:02:23', '2026-01-23 12:02:23'),
(5, 'Blood Donation Camp', 'Donate blood and save lives.', 1, 5, 'one_time', '2026-03-12 09:00:00', '2026-03-12 17:00:00', 150, '2026-03-10 18:00:00', 'pending', 'event.jpg', '2026-01-23 12:02:23', '2026-01-23 12:02:23'),
(6, 'Recycling Workshop', 'Learn how to recycle effectively at home.', 1, 6, 'one_time', '2026-03-20 14:00:00', '2026-03-20 17:00:00', 40, '2026-03-18 18:00:00', 'pending', 'event.jpg', '2026-01-23 12:02:23', '2026-01-23 12:02:23'),
(7, 'Community Health Check', 'Free health checkup for all community members.', 1, 7, 'one_time', '2026-04-01 08:00:00', '2026-04-01 16:00:00', 100, '2026-01-22 18:00:00', 'approved', 'event.jpg', '2026-01-23 12:02:23', '2026-01-24 11:30:49'),
(8, 'Literacy Campaign', 'Volunteer to teach children and adults to read.', 1, 8, 'recurring', '2026-04-05 10:00:00', '2026-04-05 12:00:00', 25, '2026-04-03 18:00:00', 'approved', 'event.jpg', '2026-01-23 12:02:23', '2026-01-24 10:50:26'),
(9, 'Animal Shelter Support', 'Help at the local animal shelter.', 1, 9, 'recurring', '2026-04-10 09:00:00', '2026-04-10 14:00:00', 20, '2026-04-08 18:00:00', 'pending', 'event.jpg', '2026-01-23 12:02:23', '2026-01-23 12:02:23'),
(10, 'Community Gardening', 'Maintain and grow plants in the neighborhood garden.', 1, 10, 'recurring', '2026-04-15 07:00:00', '2026-04-15 11:00:00', 30, '2026-04-13 18:00:00', 'approved', 'event.jpg', '2026-01-23 12:02:23', '2026-01-23 12:02:38');

-- --------------------------------------------------------

--
-- Table structure for table `event_attendance`
--

CREATE TABLE `event_attendance` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `attended_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `marked_by` enum('student','organizer','faculty') NOT NULL DEFAULT 'organizer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_attendance`
--

INSERT INTO `event_attendance` (`id`, `registration_id`, `attended_at`, `marked_by`) VALUES
(2, 2, '2026-01-24 06:25:46', 'organizer');

-- --------------------------------------------------------

--
-- Table structure for table `event_categories`
--

CREATE TABLE `event_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_categories`
--

INSERT INTO `event_categories` (`id`, `name`) VALUES
(2, 'Art Exhibitions'),
(10, 'Charity Events'),
(6, 'Community Service'),
(7, 'Festivals'),
(1, 'Music Concerts'),
(9, 'Networking Events'),
(5, 'Seminars & Conferences'),
(3, 'Sports Events'),
(8, 'Tech Meetups'),
(4, 'Workshops');

-- --------------------------------------------------------

--
-- Table structure for table `event_feedback`
--

CREATE TABLE `event_feedback` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `feedback_text` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_feedback`
--

INSERT INTO `event_feedback` (`id`, `event_id`, `user_id`, `rating`, `feedback_text`, `submitted_at`) VALUES
(1, 8, 2, 3, 'fdsa', '2026-01-24 06:33:57');

-- --------------------------------------------------------

--
-- Table structure for table `event_media`
--

CREATE TABLE `event_media` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `file_type` enum('pdf','image') NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_media`
--

INSERT INTO `event_media` (`id`, `event_id`, `file`, `file_type`, `uploaded_at`) VALUES
(1, 4, 'event.jpg', 'image', '2026-01-23 07:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `event_messages`
--

CREATE TABLE `event_messages` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_messages`
--

INSERT INTO `event_messages` (`id`, `event_id`, `sender_id`, `receiver_id`, `message`, `is_read`, `sent_at`) VALUES
(1, 7, 2, 1, 'Hy', 0, '2026-01-24 07:26:55'),
(2, 7, 1, 2, 'fsda', 0, '2026-01-24 07:29:54'),
(3, 7, 1, 2, 'fds', 0, '2026-01-24 07:30:14'),
(4, 10, 3, 1, 'fdsa', 0, '2026-01-24 07:33:58'),
(5, 10, 1, 3, 'dsa', 0, '2026-01-24 07:34:08');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','waitlisted','cancelled','attended','no_show') DEFAULT 'pending',
  `qr_code_token` varchar(64) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`id`, `event_id`, `user_id`, `status`, `qr_code_token`, `cancellation_reason`, `registered_at`) VALUES
(2, 8, 2, 'confirmed', '7d89bb2bb05ae8e5c37e24dd1ce5a686c680ee895bc3feb0b6e54c38229d5c47', 'You Are Confirmed', '2026-01-24 05:57:25'),
(4, 8, 3, 'pending', '7e224d35592622072a10b3bef4e9e4afff308f3b8d5b76c6e29e5b919903beeb', NULL, '2026-01-24 07:33:38');

-- --------------------------------------------------------

--
-- Table structure for table `event_resources`
--

CREATE TABLE `event_resources` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `quantity_allocated` int(11) NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_resources`
--

INSERT INTO `event_resources` (`id`, `event_id`, `resource_id`, `quantity_allocated`, `status`) VALUES
(1, 5, 7, 32, 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `registration_forms`
--

CREATE TABLE `registration_forms` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `field_label` varchar(150) NOT NULL,
  `field_type` enum('text','textarea','select','checkbox','radio') NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_forms`
--

INSERT INTO `registration_forms` (`id`, `event_id`, `field_label`, `field_type`, `is_required`, `created_at`) VALUES
(1, 8, 'How are You', 'text', 1, '2026-01-24 05:43:37'),
(2, 8, 'What is Your Profession', 'text', 1, '2026-01-24 05:43:37'),
(3, 8, 'Are You With Any Guests? Explain How Many', 'textarea', 1, '2026-01-24 05:43:37'),
(4, 8, 'What is Your Joining Reason', 'textarea', 1, '2026-01-24 05:43:37');

-- --------------------------------------------------------

--
-- Table structure for table `registration_responses`
--

CREATE TABLE `registration_responses` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `response_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_responses`
--

INSERT INTO `registration_responses` (`id`, `registration_id`, `field_id`, `response_value`) VALUES
(1, 2, 1, 'fsa'),
(2, 2, 2, 'fdsa'),
(3, 2, 3, 'fdsa'),
(4, 2, 4, 'fsa'),
(5, 4, 1, 'fds'),
(6, 4, 2, 'fsa'),
(7, 4, 3, 'fd'),
(8, 4, 4, 'fsa');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `resource_name` varchar(100) NOT NULL,
  `total_quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `resource_name`, `total_quantity`) VALUES
(1, 'Projector', 10),
(2, 'Microphone', 15),
(3, 'Speaker System', 8),
(4, 'Laptop', 20),
(5, 'Whiteboard', 5),
(6, 'Tables', 50),
(7, 'Chairs', 200),
(8, 'Catering Set', 30),
(9, 'Stage Lights', 12),
(10, 'Extension Cables', 25);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('Student','Faculty','Event Organizer') NOT NULL,
  `department` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `system_notifications` tinyint(1) DEFAULT 1,
  `status` enum('pending','active','suspended') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `department`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `profile_image`, `system_notifications`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Event Organizer', '', 'Event', 'Organizer', 'nasiryt.827@gmail.com', '$2y$10$oSePuxoQ5FWZAI4XGNyOE.zc/1vK23xNBDGg/yiDioqYjnpK845Me', '3176526827', 'Doctor.jpg', 0, 'active', '2026-01-23 05:44:02', '2026-01-23 06:05:56'),
(2, 'Student', '', 'Student', 'One', 'saifx280@gmail.com', '$2y$10$GIhN3B.ajgF.eFl3XKvJBePh10AwRO/q2RpyDoxpBTX2Rx6JE0m4C', '+92 3120558090', NULL, 1, 'active', '2026-01-24 05:45:24', '2026-01-24 06:07:49'),
(3, 'Faculty', '', 'Faculty', 'One', 'faculty@gmail.com', '$2y$10$kHqdw46xkuqGZsFxb7jrn.61YcTUApLtMbILlCc8tmTAGqT64J8Oq', '+92 3120558090', NULL, 1, 'active', '2026-01-24 07:32:55', '2026-01-24 07:33:11'),
(4, 'Student', 'Business Administration', 'Shumaila', 'Idrees', 'bc200205006@vu.edu.pk', '$2y$10$G.fQwdPv6wWJ9qhI08HSP.JeiLBj1KgX5UWi9KNVR05WLi86ZmUNS', '03176526434', '69819c6616144.jpg', 1, 'active', '2026-02-03 06:54:44', '2026-02-03 06:57:42');

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`id`, `name`, `location`, `capacity`, `description`) VALUES
(1, 'Grand Hall', 'Downtown City Center', 500, 'A large hall suitable for conferences and concerts.'),
(2, 'Riverside Pavilion', 'Riverside Park', 200, 'Open-air venue with scenic river views.'),
(3, 'Skyline Rooftop', 'City Tower, 12th Floor', 150, 'Rooftop venue with skyline views, perfect for parties.'),
(4, 'Green Meadows', 'Suburban Area', 300, 'Spacious outdoor venue surrounded by greenery.'),
(5, 'Ocean View Auditorium', 'Coastal Blvd', 400, 'Indoor auditorium with panoramic ocean views.'),
(6, 'Heritage Banquet Hall', 'Old Town', 250, 'Elegant banquet hall with vintage decor.'),
(7, 'Tech Convention Center', 'Industrial District', 1000, 'Modern facility for tech events and exhibitions.'),
(8, 'Sunset Garden', 'Lakeside', 180, 'Garden venue ideal for weddings and evening events.'),
(9, 'Mountain Retreat Hall', 'Hillside Resort', 220, 'Indoor hall with mountain views and cozy ambiance.'),
(10, 'City Expo Center', 'Metro Area', 1200, 'Large convention and exhibition center with multiple halls.');

-- --------------------------------------------------------

--
-- Table structure for table `venue_bookings`
--

CREATE TABLE `venue_bookings` (
  `id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venue_bookings`
--

INSERT INTO `venue_bookings` (`id`, `venue_id`, `event_id`, `booking_date`, `start_time`, `end_time`, `status`) VALUES
(1, 1, 10, '2026-01-24', '12:37:00', '16:37:00', 'confirmed');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `organizer_id` (`organizer_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`registration_id`);

--
-- Indexes for table `event_categories`
--
ALTER TABLE `event_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `event_feedback`
--
ALTER TABLE `event_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_feedback` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_media`
--
ALTER TABLE `event_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_messages`
--
ALTER TABLE `event_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_user` (`event_id`,`user_id`),
  ADD UNIQUE KEY `qr_code_token` (`qr_code_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_resources`
--
ALTER TABLE `event_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `registration_forms`
--
ALTER TABLE `registration_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `registration_responses`
--
ALTER TABLE `registration_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `venue_bookings`
--
ALTER TABLE `venue_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `event_attendance`
--
ALTER TABLE `event_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_categories`
--
ALTER TABLE `event_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `event_feedback`
--
ALTER TABLE `event_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_media`
--
ALTER TABLE `event_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_messages`
--
ALTER TABLE `event_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event_resources`
--
ALTER TABLE `event_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `registration_forms`
--
ALTER TABLE `registration_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `registration_responses`
--
ALTER TABLE `registration_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `venue_bookings`
--
ALTER TABLE `venue_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_attendance`
--
ALTER TABLE `event_attendance`
  ADD CONSTRAINT `event_attendance_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_feedback`
--
ALTER TABLE `event_feedback`
  ADD CONSTRAINT `event_feedback_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_feedback_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_media`
--
ALTER TABLE `event_media`
  ADD CONSTRAINT `event_media_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_messages`
--
ALTER TABLE `event_messages`
  ADD CONSTRAINT `event_messages_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_resources`
--
ALTER TABLE `event_resources`
  ADD CONSTRAINT `event_resources_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_resources_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registration_forms`
--
ALTER TABLE `registration_forms`
  ADD CONSTRAINT `registration_forms_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `registration_responses`
--
ALTER TABLE `registration_responses`
  ADD CONSTRAINT `registration_responses_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registration_responses_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `registration_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `venue_bookings`
--
ALTER TABLE `venue_bookings`
  ADD CONSTRAINT `venue_bookings_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`),
  ADD CONSTRAINT `venue_bookings_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
