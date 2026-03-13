-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2026 at 04:36 PM
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
-- Database: `event_booking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `created_at`) VALUES
(2, 'admin', '$2y$10$eAXG00wBG.Kkebn2rubwOORDJuazVAQ0vUozUv9CA.tbjIUeaKN5G', 'Mossx Admin', '2026-03-02 09:09:05');

-- --------------------------------------------------------

--
-- Table structure for table `booths`
--

CREATE TABLE `booths` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `booth_number` varchar(50) DEFAULT NULL,
  `status` enum('available','booked','pending') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booth_activities`
--

CREATE TABLE `booth_activities` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slots_limit` int(11) DEFAULT 10,
  `max_slots` int(11) DEFAULT 10,
  `status` enum('pending','calling','finished','cancelled') DEFAULT 'pending',
  `completion_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booth_activities`
--

INSERT INTO `booth_activities` (`id`, `booking_id`, `start_time`, `end_time`, `slots_limit`, `max_slots`, `status`, `completion_note`) VALUES
(15, 18, '23:09:00', '00:09:00', 10, 10, 'pending', NULL),
(16, 18, '01:09:00', '02:09:00', 10, 8, 'pending', NULL),
(17, 18, '10:10:00', '11:10:00', 10, 10, 'pending', NULL),
(19, 16, '17:20:00', '17:24:00', 10, 10, 'finished', NULL),
(20, 16, '18:24:00', '18:27:00', 10, 10, 'pending', NULL),
(22, 19, '18:02:00', '19:07:00', 10, 10, 'finished', 'ขอบคุณที่เข้ามาร่วมสนุกครับ'),
(24, 19, '20:05:00', '20:09:00', 10, 2, 'pending', NULL),
(25, 19, '16:31:00', '16:33:00', 10, 10, 'cancelled', 'ติดปัญหาอุปกรณ์เครื่องเล่นครับ'),
(26, 19, '16:39:00', '17:39:00', 10, 1, 'pending', NULL),
(27, 19, '08:14:00', '08:20:00', 10, 2, 'finished', 'End');

-- --------------------------------------------------------

--
-- Table structure for table `booth_owners`
--

CREATE TABLE `booth_owners` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booth_owners`
--

INSERT INTO `booth_owners` (`id`, `username`, `password`, `shop_name`, `owner_name`, `phone`, `status`, `created_at`) VALUES
(1, 'moss', '$2y$10$MAk3bBFb2Ja9ZWEM79I5MeHVPV8Waxcf8f7HARSwXwuUbwk2zr72W', 'Apple', 'Moss narak', '0100040081', 'approved', '2026-03-02 15:00:45'),
(3, 'odew', '$2y$10$GikKGk8XT6SXHfzBqSs.8eXCiRDIx1UpC7YBy3x8ZilD2qoYY8JnK', 'commart', 'To ch', '022554466', 'approved', '2026-03-08 12:54:24'),
(6, 'odew1', '$2y$10$9bm2SeT3S/EKxUUUpx63O.EIW1dIF/bWK1qvVOWW0fS2KSL1VNf7m', 'Odewจำกัด', 'TOkdA Chokamnuay', '0872121567', 'approved', '2026-03-11 13:08:07');

-- --------------------------------------------------------

--
-- Table structure for table `booth_types`
--

CREATE TABLE `booth_types` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `type_name` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `total_slots` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booth_types`
--

INSERT INTO `booth_types` (`id`, `event_id`, `type_name`, `price`, `total_slots`) VALUES
(3, 2, 'ทั่วไป', 0.00, 10),
(4, 3, 'ข้างราง', 0.00, 20),
(5, 3, 'กลางทาง', 500.00, 10),
(6, 4, 'ทั่วไป', 0.00, 1),
(7, 4, 'กิจกรรม', 1000.00, 5),
(8, 4, 'อาหาร', 0.00, 10);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `event_date`, `location`, `created_at`) VALUES
(2, 'งาน Easy Luv', '2026-03-11', 'หอประชุม 2', '2026-03-02 09:44:03'),
(3, 'MRT', '2026-03-16', 'MRT', '2026-03-11 12:37:18'),
(4, 'งาน Long Luv', '2026-03-11', 'หอประชุม 2', '2026-03-12 09:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `event_bookings`
--

CREATE TABLE `event_bookings` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `payment_slip` varchar(255) DEFAULT NULL,
  `booking_status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `has_activity` enum('yes','no') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activity_detail` text DEFAULT NULL,
  `category_list` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_bookings`
--

INSERT INTO `event_bookings` (`id`, `event_id`, `type_id`, `owner_id`, `customer_name`, `customer_phone`, `payment_slip`, `booking_status`, `has_activity`, `created_at`, `activity_detail`, `category_list`) VALUES
(16, 2, 3, 1, 'Apple', '000-000-0000', NULL, 'confirmed', 'yes', '2026-03-11 11:19:51', 'แจกของรางวัลเครื่องใช้ไฟฟ้า', NULL),
(17, 3, 5, 1, 'Apple', '000-000-0000', 'slip_1773232930_1.png', 'confirmed', 'no', '2026-03-11 12:42:10', 'อาหารแจกฟรี', NULL),
(18, 3, 4, 6, 'Odewจำกัด', '000-000-0000', NULL, 'confirmed', 'yes', '2026-03-11 13:08:50', 'asdwasdw', NULL),
(19, 3, 5, 1, 'Apple', '000-000-0000', 'slip_1773305939_1.jpg', 'confirmed', 'yes', '2026-03-12 08:58:59', '', NULL),
(20, 4, 7, 1, 'Apple', '000-000-0000', 'slip_1773338700_1.png', 'pending', NULL, '2026-03-12 18:05:00', '', NULL),
(21, 4, 7, 1, 'Apple', '000-000-0000', 'slip_1773339906_1.png', 'confirmed', NULL, '2026-03-12 18:25:06', '', 'กิจกรรมเกม'),
(22, 3, 5, 1, 'Apple', '000-000-0000', 'slip_1773340694_1.png', 'confirmed', 'yes', '2026-03-12 18:38:14', '', 'กิจกรรมเกม, ของใช้');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_reservations`
--

CREATE TABLE `user_activity_reservations` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'confirmed',
  `booking_id` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_reservations`
--

INSERT INTO `user_activity_reservations` (`id`, `user_id`, `activity_id`, `status`, `booking_id`, `start_time`, `end_time`, `created_at`) VALUES
(29, '118364688021608616120', 19, 'confirmed', 0, '17:20:00', '17:24:00', '2026-03-12 08:27:19'),
(32, '103071481783770776924', 24, 'confirmed', 0, '20:05:00', '20:09:00', '2026-03-12 09:06:17'),
(33, '108245469794222138527', 24, 'confirmed', 0, '20:05:00', '20:09:00', '2026-03-12 09:06:24'),
(35, '101714256230615298018', 22, 'confirmed', 0, '18:02:00', '19:07:00', '2026-03-12 12:36:53'),
(41, '101714256230615298018', 27, 'cancelled', 0, '08:14:00', '08:20:00', '2026-03-12 13:55:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `booths`
--
ALTER TABLE `booths`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `booth_activities`
--
ALTER TABLE `booth_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `booth_owners`
--
ALTER TABLE `booth_owners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `booth_types`
--
ALTER TABLE `booth_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_bookings`
--
ALTER TABLE `event_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `user_activity_reservations`
--
ALTER TABLE `user_activity_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `booths`
--
ALTER TABLE `booths`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booth_activities`
--
ALTER TABLE `booth_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `booth_owners`
--
ALTER TABLE `booth_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `booth_types`
--
ALTER TABLE `booth_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event_bookings`
--
ALTER TABLE `event_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_activity_reservations`
--
ALTER TABLE `user_activity_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booths`
--
ALTER TABLE `booths`
  ADD CONSTRAINT `booths_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booths_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `booth_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booth_activities`
--
ALTER TABLE `booth_activities`
  ADD CONSTRAINT `booth_activities_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `event_bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booth_types`
--
ALTER TABLE `booth_types`
  ADD CONSTRAINT `booth_types_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_bookings`
--
ALTER TABLE `event_bookings`
  ADD CONSTRAINT `event_bookings_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `event_bookings_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `booth_types` (`id`);

--
-- Constraints for table `user_activity_reservations`
--
ALTER TABLE `user_activity_reservations`
  ADD CONSTRAINT `user_activity_reservations_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `booth_activities` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
