-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2025 at 05:07 PM
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
-- Database: `dbbus`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `timestamp`, `user_id`, `ip_address`, `action`, `module`, `details`, `created_at`) VALUES
(1, '2025-02-28 23:22:29', 1, '192.168.11.145', 'LOGIN', 'Authentication', 'User logged in successfully', '2025-02-28 15:22:29'),
(2, '2025-03-04 23:24:46', 1, '192.168.11.145', 'LOGIN', 'Authentication', 'User logged in successfully', '2025-03-04 15:24:46'),
(3, '2025-03-05 22:43:06', 1, '192.168.11.145', 'LOGIN', 'Authentication', 'User logged in successfully', '2025-03-05 14:43:06'),
(4, '2025-03-05 22:45:50', 1, '192.168.11.145', 'LOGIN', 'Authentication', 'User logged in successfully', '2025-03-05 14:45:50'),
(5, '2025-03-05 22:46:17', 1, '192.168.11.145', 'LOGIN', 'Authentication', 'User logged in successfully', '2025-03-05 14:46:17'),
(6, '2025-03-09 21:47:05', 1, '192.168.11.145', 'LOGIN', 'Authentication', 'User logged in successfully', '2025-03-09 13:47:05'),
(7, '2025-03-09 22:55:58', 1, '192.168.11.145', 'LOGIN', 'Authentication', 'User logged in successfully', '2025-03-09 14:55:58'),
(9, '2025-03-14 20:31:44', 1, '::1', 'LOGIN', 'Authentication', 'User logged in successfully', '2025-03-14 12:31:44'),
(11, '2025-03-14 21:00:49', 1, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 13:00:49'),
(12, '2025-03-14 21:28:32', 1, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 13:28:32'),
(13, '2025-03-14 21:29:32', 1, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 13:29:32'),
(14, '2025-03-14 22:40:06', 1, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 14:40:06'),
(15, '2025-03-14 23:03:40', 1, '::1', 'CREATE', 'Users', 'New dispatcher account created: chbe@gmail.com', '2025-03-14 15:03:40'),
(16, '2025-03-14 23:03:48', 2, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 15:03:48'),
(17, '2025-03-14 23:20:17', 2, '::1', 'LOGOUT', 'Auth', 'Dispatcher logged out', '2025-03-14 15:20:17'),
(18, '2025-03-14 23:21:10', 1, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 15:21:10'),
(19, '2025-03-14 23:38:33', 2, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 15:38:33'),
(20, '2025-03-14 23:50:21', 1, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 15:50:21'),
(21, '2025-03-14 23:58:39', 2, '::1', 'LOGIN', 'Authentication', 'Staff logged in successfully', '2025-03-14 15:58:39');

-- --------------------------------------------------------

--
-- Table structure for table `donsals`
--

CREATE TABLE `donsals` (
  `id` int(11) NOT NULL,
  `vehicle_number` varchar(50) NOT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 16,
  `current_location` varchar(100) DEFAULT NULL,
  `available_seats` int(11) DEFAULT 16,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `is_conductor` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `license_number`, `contact_number`, `address`, `status`, `is_conductor`, `created_at`, `updated_at`) VALUES
(1, 'Sffafs', '822', '1726', 'Vacacaf', 'active', 0, '2025-03-05 14:54:11', '2025-03-05 14:54:11');

-- --------------------------------------------------------

--
-- Table structure for table `driver_ratings`
--

CREATE TABLE `driver_ratings` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location_updates`
--

CREATE TABLE `location_updates` (
  `id` int(11) NOT NULL,
  `donsal_id` int(11) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_name` varchar(100) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

CREATE TABLE `passengers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL CHECK (`age` >= 0),
  `gender` enum('male','female','other') NOT NULL,
  `address` text NOT NULL,
  `profile_picture` varchar(255) NOT NULL,
  `valid_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`id`, `first_name`, `last_name`, `email`, `age`, `gender`, `address`, `profile_picture`, `valid_id`, `created_at`, `updated_at`, `password`) VALUES
(8, 'asd', 'asdasd', 'a@gmail.com', 20, 'male', 'asdasd', '67d41fab7320d_download (1).jfif', '67d41fab7336d_download (1).jfif', '2025-03-14 12:23:07', '2025-03-14 12:23:07', '$2y$10$D.XMXJ36CkKUr6tMiSg7/uYeGOm6oOKftX0tsVC1EQE0QbfYbC5c2');

-- --------------------------------------------------------

--
-- Table structure for table `passenger_registrations`
--

CREATE TABLE `passenger_registrations` (
  `id` int(11) NOT NULL,
  `passenger_id` int(11) NOT NULL,
  `registration_date` date NOT NULL,
  `registration_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `passenger_registrations`
--

INSERT INTO `passenger_registrations` (`id`, `passenger_id`, `registration_date`, `registration_time`, `created_at`, `updated_at`) VALUES
(2, 8, '2025-03-14', '20:23:07', '2025-03-14 12:23:07', '2025-03-14 12:23:07');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `reservation_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `donsal_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `number_of_seats` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Triggers `reservations`
--
DELIMITER $$
CREATE TRIGGER `after_reservation_insert` AFTER INSERT ON `reservations` FOR EACH ROW BEGIN
    UPDATE donsals 
    SET available_seats = available_seats - NEW.number_of_seats
    WHERE id = NEW.donsal_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_reservation_update` AFTER UPDATE ON `reservations` FOR EACH ROW BEGIN
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        UPDATE donsals 
        SET available_seats = available_seats + NEW.number_of_seats
        WHERE id = NEW.donsal_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `route_name` varchar(100) NOT NULL,
  `start_point` varchar(100) NOT NULL,
  `end_point` varchar(100) NOT NULL,
  `fare_amount` decimal(10,2) NOT NULL,
  `estimated_time` int(11) NOT NULL COMMENT 'in minutes',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `route_name`, `start_point`, `end_point`, `fare_amount`, `estimated_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Route A', 'Terminal 1', 'Market Place', 50.00, 30, 'active', '2025-02-28 15:19:35', '2025-02-28 15:19:35'),
(2, 'Route B', 'Terminal 1', 'Shopping Mall', 45.00, 25, 'active', '2025-02-28 15:19:35', '2025-02-28 15:19:35'),
(3, 'Route C', 'Terminal 2', 'University', 40.00, 20, 'active', '2025-02-28 15:19:35', '2025-02-28 15:19:35');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `date_time` datetime NOT NULL,
  `type` enum('reservation','cancellation','payment','refund') NOT NULL,
  `user_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','online','card') DEFAULT 'cash',
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','driver','conductor','dispatcher') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System Admin', 'admin@donsal.com', '$2y$10$VzrA.lxtL6tqSwehcX1P3u310sZian/ewTUjNcU5mKaNYbuSNFZfi', 'admin', 'active', '2025-02-28 15:19:35', '2025-03-04 15:24:32'),
(2, '', 'chbe@gmail.com', '$2y$10$ZSjtxzDyunZubxP8XyrXduSB5c5L3Iu5VEXcZ5qN1N/LSAYa49Vze', 'dispatcher', 'active', '2025-03-14 15:03:39', '2025-03-14 15:03:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_audit_timestamp` (`timestamp`);

--
-- Indexes for table `donsals`
--
ALTER TABLE `donsals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_number` (`vehicle_number`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `idx_donsal_vehicle` (`vehicle_number`),
  ADD KEY `idx_donsal_status` (`status`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `idx_driver_license` (`license_number`);

--
-- Indexes for table `driver_ratings`
--
ALTER TABLE `driver_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `location_updates`
--
ALTER TABLE `location_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donsal_id` (`donsal_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `passenger_registrations`
--
ALTER TABLE `passenger_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `passenger_id` (`passenger_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reservation_id` (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `donsal_id` (`donsal_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `idx_reservation_date` (`reservation_date`),
  ADD KEY `idx_reservation_status` (`status`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_route_status` (`status`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `donsals`
--
ALTER TABLE `donsals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `driver_ratings`
--
ALTER TABLE `driver_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_updates`
--
ALTER TABLE `location_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `passengers`
--
ALTER TABLE `passengers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `passenger_registrations`
--
ALTER TABLE `passenger_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `donsals`
--
ALTER TABLE `donsals`
  ADD CONSTRAINT `donsals_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `donsals_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`);

--
-- Constraints for table `driver_ratings`
--
ALTER TABLE `driver_ratings`
  ADD CONSTRAINT `driver_ratings_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `driver_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `location_updates`
--
ALTER TABLE `location_updates`
  ADD CONSTRAINT `location_updates_ibfk_1` FOREIGN KEY (`donsal_id`) REFERENCES `donsals` (`id`),
  ADD CONSTRAINT `location_updates_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `passenger_registrations`
--
ALTER TABLE `passenger_registrations`
  ADD CONSTRAINT `passenger_registrations_ibfk_1` FOREIGN KEY (`passenger_id`) REFERENCES `passengers` (`id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`donsal_id`) REFERENCES `donsals` (`id`),
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
