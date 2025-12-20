-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2025 at 10:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tiket kereta`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `booking_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `seats` int(11) DEFAULT 1,
  `status` enum('PENDING','CONFIRMED','PAID','CANCELLED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `schedule_id`, `booking_date`, `total_amount`, `payment_status`, `payment_method`, `seats`, `status`, `created_at`) VALUES
(3, 6, 1, '2025-12-16 13:14:07', 3900000.00, 'pending', NULL, 6, 'PENDING', '2025-12-16 00:14:07'),
(4, 6, 1, '2025-12-16 13:39:18', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:39:18'),
(5, 6, 1, '2025-12-16 07:41:24', 650000.00, 'pending', NULL, 1, 'PENDING', '2025-12-16 06:41:24'),
(6, 6, 1, '2025-12-16 13:55:42', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:55:42'),
(7, 6, 1, '2025-12-16 07:55:45', 650000.00, 'pending', NULL, 1, 'PENDING', '2025-12-16 06:55:45'),
(8, 6, 1, '2025-12-16 07:56:14', 650000.00, 'pending', NULL, 1, 'PENDING', '2025-12-16 06:56:14'),
(9, 6, 1, '2025-12-16 13:58:40', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:58:40'),
(10, 6, 1, '2025-12-16 13:58:44', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:58:44'),
(11, 6, 1, '2025-12-16 13:58:46', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:58:46'),
(12, 6, 1, '2025-12-16 13:58:49', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:58:49'),
(13, 6, 1, '2025-12-16 13:58:52', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:58:52'),
(14, 6, 1, '2025-12-16 13:58:57', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:58:57'),
(15, 6, 1, '2025-12-16 13:59:01', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 00:59:01'),
(16, 6, 1, '2025-12-16 14:02:46', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 01:02:46'),
(17, 6, 1, '2025-12-16 14:02:48', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 01:02:48'),
(18, 6, 1, '2025-12-16 14:02:59', 1300000.00, 'pending', NULL, 2, 'PENDING', '2025-12-16 01:02:59'),
(19, 6, 1, '2025-12-16 14:05:55', 1300000.00, 'pending', NULL, 2, 'PAID', '2025-12-16 01:05:55'),
(20, 6, 1, '2025-12-16 08:13:29', 650000.00, 'pending', NULL, 1, 'PENDING', '2025-12-16 07:13:29'),
(21, 6, 1, '2025-12-16 08:13:41', 650000.00, 'pending', NULL, 1, 'PENDING', '2025-12-16 07:13:41'),
(22, 6, 1, '2025-12-16 14:14:11', 1300000.00, 'pending', NULL, 2, 'PAID', '2025-12-16 01:14:11'),
(23, 6, 1, '2025-12-16 08:14:47', 650000.00, 'pending', NULL, 1, 'PENDING', '2025-12-16 07:14:47'),
(24, 6, 1, '2025-12-16 14:43:49', 1300000.00, 'pending', NULL, 2, 'PAID', '2025-12-16 01:43:49'),
(25, 6, 1, '2025-12-16 15:48:04', 650000.00, 'pending', NULL, 1, 'PAID', '2025-12-16 02:48:04'),
(26, 6, 2, '2025-12-16 15:50:59', 1200000.00, 'pending', NULL, 2, 'PAID', '2025-12-16 02:50:59'),
(27, 6, 2, '2025-12-16 15:51:29', 600000.00, 'pending', NULL, 1, 'PAID', '2025-12-16 02:51:29');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `train_id` int(11) NOT NULL,
  `origin_station_id` int(11) NOT NULL,
  `destination_station_id` int(11) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `train_id`, `origin_station_id`, `destination_station_id`, `departure_time`, `arrival_time`, `price`) VALUES
(1, 7, 5, 1, '2025-12-20 08:00:00', '2025-12-20 14:00:00', 650000.00),
(2, 8, 6, 1, '2025-12-21 09:00:00', '2025-12-21 15:00:00', 600000.00),
(3, 9, 7, 1, '2025-12-22 07:30:00', '2025-12-22 12:30:00', 550000.00);

-- --------------------------------------------------------

--
-- Table structure for table `stations`
--

CREATE TABLE `stations` (
  `station_id` int(11) NOT NULL,
  `station_name` varchar(100) NOT NULL,
  `city` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stations`
--

INSERT INTO `stations` (`station_id`, `station_name`, `city`, `code`) VALUES
(1, 'Gambir', 'Jakarta', 'GMR'),
(5, 'Pasar Turi', 'Surabaya', 'SBY'),
(6, 'Bandung Hall', 'Bandung', 'BD'),
(7, 'Yogyakarta', 'Yogyakarta', 'YK'),
(8, 'Semarang Tawang', 'Semarang', 'SMG'),
(9, 'Jakarta', 'DKI Jakarta', 'JKT'),
(11, 'Bandung', 'Jawa Barat', 'BDG'),
(12, 'Yogyakarta', 'DI Yogyakarta', 'YOG');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `passenger_name` varchar(100) NOT NULL,
  `passenger_nik` varchar(16) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`ticket_id`, `booking_id`, `seat_number`, `passenger_name`, `passenger_nik`, `phone_number`) VALUES
(1, 19, '8', 'Penumpang 1', '', ''),
(2, 19, '9', 'Penumpang 2', '', ''),
(3, 22, '10', 'Penumpang 1', '', ''),
(4, 22, '11', 'Penumpang 2', '', ''),
(5, 24, '20', 'Frensen', '111111', '088888888'),
(6, 24, '21', 'Clevan', '262626', '43567890'),
(7, 25, '14', 'afdsfrs', '63789', '92374678'),
(8, 26, '28', 'hewbnfois', '6723890', '7683290'),
(9, 26, '29', 'hbankml', '398475', '7534890'),
(10, 27, '4', 'wadfers', '378290', '536256');

-- --------------------------------------------------------

--
-- Table structure for table `trains`
--

CREATE TABLE `trains` (
  `train_id` int(11) NOT NULL,
  `train_name` varchar(100) NOT NULL,
  `total_carriages` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trains`
--

INSERT INTO `trains` (`train_id`, `train_name`, `total_carriages`) VALUES
(7, 'Argo Bromo Anggrek', 40),
(8, 'Argo Parahyangan', 40),
(9, 'Taksaka', 40);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `alamat` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `password`, `nik`, `role`, `created_at`, `email`, `phone`, `alamat`) VALUES
(3, 'Stanislaus Clevantio', '$2y$10$IcQjFEnvQmCzt5oWqvPQx.hFdnIAIeZzLgw8uOFvxWEe..Gkx4gF.', '666666666', 'user', '2025-12-04 08:46:42', 'clevan@gmail.com', '087777666', ''),
(6, 'Kevin William', '$2y$10$T3pwch4V25BlB7NGf6hjnOAXo9tnAaLp62cJVHcnTAIm9H/CffY3e', '111111', 'user', '2025-12-04 08:56:56', 'kevinwy06@gmail.com', '087703147002', 'Jl Raya Lingkar Timur'),
(7, 'mimin', '$2y$10$j7cr7q7rT0ym6zUyA5PR2./cVlsDmaiXXoML3mE9zWcGhiLMyT7De', '123456', 'admin', '2025-12-16 06:29:09', 'mimin@gmail.com', '088777666', 'Raya Kintil');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `train_id` (`train_id`),
  ADD KEY `origin_station_id` (`origin_station_id`),
  ADD KEY `destination_station_id` (`destination_station_id`);

--
-- Indexes for table `stations`
--
ALTER TABLE `stations`
  ADD PRIMARY KEY (`station_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `trains`
--
ALTER TABLE `trains`
  ADD PRIMARY KEY (`train_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stations`
--
ALTER TABLE `stations`
  MODIFY `station_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `trains`
--
ALTER TABLE `trains`
  MODIFY `train_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`train_id`) REFERENCES `trains` (`train_id`),
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`origin_station_id`) REFERENCES `stations` (`station_id`),
  ADD CONSTRAINT `schedules_ibfk_3` FOREIGN KEY (`destination_station_id`) REFERENCES `stations` (`station_id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
