-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026 at 07:55 AM
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
(1, 'moss', '$2y$10$MAk3bBFb2Ja9ZWEM79I5MeHVPV8Waxcf8f7HARSwXwuUbwk2zr72W', 'Apple', 'Moss narak', '0100040081', 'approved', '2026-03-02 15:00:45');

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
(1, 1, 'กิจกรรม', 10000.00, 5),
(2, 1, 'อาหาร', 0.00, 4),
(3, 2, 'ทั่วไป', 0.00, 10);

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
(1, 'งาน Long Luv', '2026-03-10', 'หอประชุม 1', '2026-03-02 08:24:42'),
(2, 'งาน Easy Luv', '2026-03-11', 'หอประชุม 2', '2026-03-02 09:44:03');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_bookings`
--

INSERT INTO `event_bookings` (`id`, `event_id`, `type_id`, `owner_id`, `customer_name`, `customer_phone`, `payment_slip`, `booking_status`, `created_at`) VALUES
(1, 1, 1, NULL, 'Apple', '00000000000', NULL, 'cancelled', '2026-03-03 10:50:53'),
(2, 1, 1, NULL, 'Dee', '082111222', NULL, 'cancelled', '2026-03-06 09:44:19'),
(3, 1, 1, 1, 'Apple', '000-000-0000', 'slip_1772791777_1.jpg', 'cancelled', '2026-03-06 10:09:37'),
(4, 1, 1, 1, 'Apple', '000-000-0000', 'slip_1772791792_1.jpg', 'cancelled', '2026-03-06 10:09:52'),
(5, 1, 1, 1, 'Apple', '000-000-0000', 'slip_1772791802_1.png', 'cancelled', '2026-03-06 10:10:02'),
(6, 1, 1, 1, 'Apple', '0000000000', 'slip_1772793720_1.png', 'pending', '2026-03-06 10:42:00'),
(7, 1, 1, 1, 'Apple', '<br />\r\n<b>Warning</', 'slip_1772793741_1.png', 'pending', '2026-03-06 10:42:21');

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
-- AUTO_INCREMENT for table `booth_owners`
--
ALTER TABLE `booth_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booth_types`
--
ALTER TABLE `booth_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_bookings`
--
ALTER TABLE `event_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
