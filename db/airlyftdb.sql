-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 14, 2026 at 08:22 PM
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
-- Database: `airlyftdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_messages`
--

CREATE TABLE `ai_chat_messages` (
  `message_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `sender_type` enum('user','agent') NOT NULL,
  `message` text NOT NULL,
  `ai_response_raw` text DEFAULT NULL,
  `is_fallback` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_sessions`
--

CREATE TABLE `ai_chat_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_token` varchar(64) NOT NULL,
  `start_time` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `message_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sched_id` int(11) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `booking_status` enum('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `is_round_trip` tinyint(1) DEFAULT 0,
  `return_departure` datetime DEFAULT NULL,
  `return_arrival` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_history`
--

CREATE TABLE `booking_history` (
  `history_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_status` enum('Confirmed','Cancelled') NOT NULL DEFAULT 'Confirmed',
  `total_amount` decimal(10,2) NOT NULL,
  `action` varchar(50) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emailnotification`
--

CREATE TABLE `emailnotification` (
  `email_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `recipient` varchar(100) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `email_notif_status` enum('Pending','Sent','Failed') DEFAULT 'Pending',
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lift`
--

CREATE TABLE `lift` (
  `lift_id` int(11) NOT NULL,
  `aircraft_type` varchar(100) DEFAULT NULL,
  `aircraft_name` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `lift_status` enum('available','maintenance','inactive') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lift`
--

INSERT INTO `lift` (`lift_id`, `aircraft_type`, `aircraft_name`, `capacity`, `price`, `lift_status`) VALUES
(1, 'CESSNA 206-1', 'Cessna 206', 5, 20000.00, 'available'),
(2, 'CESSNA GRAND CARAVAN EX-1', 'Cessna Grand Caravan EX', 10, 35000.00, 'available'),
(3, 'Airbus H160-1', 'Airbus H160', 8, 40000.00, 'available'),
(4, 'Sikorsky S-76-D', 'Sikorsky S-76D', 6, 45000.00, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `passenger`
--

CREATE TABLE `passenger` (
  `passenger_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `passenger_f_name` varchar(50) DEFAULT NULL,
  `passenger_l_name` varchar(50) DEFAULT NULL,
  `insurance` enum('yes','no') NOT NULL DEFAULT 'no',
  `address` varchar(255) DEFAULT NULL,
  `passenger_phone_number` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `method` varchar(50) DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Failed') DEFAULT 'Pending',
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `place`
--

CREATE TABLE `place` (
  `place_id` int(11) NOT NULL,
  `place_name` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `place`
--

INSERT INTO `place` (`place_id`, `place_name`, `location`, `description`) VALUES
(1, 'Amanpulo Palawan', 'Pamalican Island, Palawan', 'Secluded Luxury in Pamalican Island'),
(2, 'Balesin Island Quezon Province', 'Balesin Island, Quezon Province', 'Exclusive Island Getaway'),
(3, 'Amorita Resort Panglao', 'Panglao, Bohol', 'Bohol\'s Cliffside Sanctuary'),
(4, 'Huma Island Resort Palawan', 'Coron, Palawan', 'Overwater Villas in Coron'),
(5, 'El Nido Resorts Apulit Island', 'Apulit Island, El Nido', '\"Palawan\'s Pristine Beauty'),
(6, 'Banwa Private Island', 'Roxas, Palawan', 'Ultimate Exclusive Retreat'),
(7, 'Nay Palad Surigao del Norte', 'Siargao Island, Surigao del Norte', 'Barefoot Luxury in Siargao'),
(8, 'Alphaland Baguio', 'Itogon, Benguet', 'Mountain Serenity in Baguio'),
(9, 'Shangri-La Boracay', 'Boracay Island, Aklan', 'Boracay\'s Premier Resort'),
(10, 'The Farm San Benito Lipa', 'San Benito', 'Holistic Wellness Retreat'),
(11, 'Aureo La Union', 'San Fernando, La Union', 'Coastal Charm in La Union'),
(12, 'Eagle Point Beach Anilao', 'Mabini, Batangas', 'Seaside Dive & Leisure Resort');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `schedule_id` int(11) NOT NULL,
  `lift_id` int(11) DEFAULT NULL,
  `place_id` int(11) DEFAULT NULL,
  `departure_time` datetime DEFAULT NULL,
  `arrival_time` datetime DEFAULT NULL,
  `airport` enum('MNL','CEB','DVO') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smsnotification`
--

CREATE TABLE `smsnotification` (
  `sms_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sms_status` enum('Pending','Sent','Failed') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('Client','Admin') NOT NULL DEFAULT 'Client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `source_system` enum('Electripid','Airlyft') NOT NULL DEFAULT 'Airlyft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `phone`, `password`, `role`, `created_at`, `deleted_at`, `source_system`) VALUES
(1, 'red', NULL, 'red@email.com', '09157881886', '$2y$10$RFgNynH1E70vdPJbam/kgu.qe4CY..WJBBI.zYYgGuOJKZDiHlyTK', 'Client', '2026-01-14 07:11:20', NULL, 'Airlyft'),
(2, 'Admin', NULL, 'admin@airlyft.com', '0000000000', '$2y$10$YdUdd.RtBlHnvves.MZCU.zpG6xCQng.VtLo.fsDb/JHk1Un8F09G', 'Admin', '2026-01-14 07:11:20', NULL, 'Airlyft'),
(3, 'dew rew', NULL, 'dew@email.com', '0978158861', '$2y$10$R5QMxoxU3SixK56DrAygju0xjkEH0J2NSHgbH7DpmmGi7qsklXYo6', 'Client', '2026-01-14 07:11:20', NULL, 'Airlyft');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sched_id` (`sched_id`);

--
-- Indexes for table `booking_history`
--
ALTER TABLE `booking_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `emailnotification`
--
ALTER TABLE `emailnotification`
  ADD PRIMARY KEY (`email_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `lift`
--
ALTER TABLE `lift`
  ADD PRIMARY KEY (`lift_id`);

--
-- Indexes for table `passenger`
--
ALTER TABLE `passenger`
  ADD PRIMARY KEY (`passenger_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `place`
--
ALTER TABLE `place`
  ADD PRIMARY KEY (`place_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `lift_id` (`lift_id`),
  ADD KEY `place_id` (`place_id`);

--
-- Indexes for table `smsnotification`
--
ALTER TABLE `smsnotification`
  ADD PRIMARY KEY (`sms_id`),
  ADD KEY `booking_id` (`booking_id`);

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
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_history`
--
ALTER TABLE `booking_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emailnotification`
--
ALTER TABLE `emailnotification`
  MODIFY `email_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lift`
--
ALTER TABLE `lift`
  MODIFY `lift_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `passenger`
--
ALTER TABLE `passenger`
  MODIFY `passenger_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `place`
--
ALTER TABLE `place`
  MODIFY `place_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smsnotification`
--
ALTER TABLE `smsnotification`
  MODIFY `sms_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD CONSTRAINT `ai_chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `ai_chat_sessions` (`session_id`);

--
-- Constraints for table `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  ADD CONSTRAINT `ai_chat_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`sched_id`) REFERENCES `schedule` (`schedule_id`);

--
-- Constraints for table `booking_history`
--
ALTER TABLE `booking_history`
  ADD CONSTRAINT `booking_history_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`),
  ADD CONSTRAINT `booking_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `emailnotification`
--
ALTER TABLE `emailnotification`
  ADD CONSTRAINT `emailnotification_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`);

--
-- Constraints for table `passenger`
--
ALTER TABLE `passenger`
  ADD CONSTRAINT `passenger_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `passenger_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`);

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`lift_id`) REFERENCES `lift` (`lift_id`),
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`place_id`) REFERENCES `place` (`place_id`);

--
-- Constraints for table `smsnotification`
--
ALTER TABLE `smsnotification`
  ADD CONSTRAINT `smsnotification_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;