-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2025 at 08:31 PM
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
-- Database: `smart_canteen`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `activity_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`activity_id`, `timestamp`, `activity_type`, `description`, `admin_id`, `user_id`, `related_id`) VALUES
(1, '2025-06-17 22:03:40', 'balance_update', 'Admin \'admin\' added NPR 1000 for NFC ID \'1001\' (Student ID: 1). New balance: NPR 1,000.00.', 1, NULL, 1),
(2, '2025-06-17 23:46:25', 'Order', 'Student aman paudel (ID: 1) purchased items via NFC. Total: Rs. 190.00', NULL, 1, 1),
(3, '2025-06-17 23:49:40', 'Order', 'Student aman paudel (ID: 1) purchased items via NFC. Total: Rs. 15.00', NULL, 1, 2),
(4, '2025-06-18 00:02:50', 'Order', 'Student aman paudel (ID: 1) purchased items via NFC. Total: Rs. 15.00', NULL, 1, 3),
(5, '2025-06-18 00:06:25', 'Order', 'Student aman paudel (ID: 1) purchased items via NFC. Total: Rs. 15.00', NULL, 1, 4),
(6, '2025-06-18 00:07:55', 'Order', 'Student aman paudel (ID: 1) purchased items via NFC. Total: Rs. 15.00', NULL, 1, 5),
(7, '2025-06-18 00:09:15', 'Order', 'Student aman paudel (ID: 1) purchased items via NFC. Total: Rs. 15.00', NULL, 1, 6),
(8, '2025-06-18 00:09:25', 'Order', 'Student aman paudel (ID: 1) purchased items via NFC. Total: Rs. 15.00', NULL, 1, 7),
(9, '2025-06-18 00:14:23', 'Order', 'Student aman paudel (ID: 1) purchased items via NFC. Total: Rs. 15.00', NULL, 1, 8);

-- --------------------------------------------------------

--
-- Table structure for table `food`
--

CREATE TABLE `food` (
  `food_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food`
--

INSERT INTO `food` (`food_id`, `name`, `price`, `image_path`, `category`, `description`, `is_available`) VALUES
(1, 'samosa', 20.00, 'images/samosa.jpg', 'Veg', 'tasty samosa', 1),
(2, 'sandwich', 85.00, 'images/veg_sandwich.jpg', 'Snack', 'veg sandwich', 1),
(3, 'masala chai', 15.00, 'images/masala_chai.jpg', 'Beverage', 'chai lelo', 1),
(9, 'chicken burger', 90.00, 'images/img_6829f75d2c3bc2.00748161.jpg', 'Non-Veg', 'delicious', 1),
(10, 'jerry', 25.00, 'images/img_6836d15c60c7c2.98979809.webp', 'Dessert', 'diabetes', 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu_time_settings`
--

CREATE TABLE `menu_time_settings` (
  `menu_type` varchar(50) NOT NULL,
  `start_hour` int(11) NOT NULL CHECK (`start_hour` >= 0 and `start_hour` <= 23),
  `end_hour` int(11) NOT NULL CHECK (`end_hour` >= 0 and `end_hour` <= 23),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `start_minute` int(11) NOT NULL DEFAULT 0 CHECK (`start_minute` >= 0 and `start_minute` <= 59),
  `end_minute` int(11) NOT NULL DEFAULT 0 CHECK (`end_minute` >= 0 and `end_minute` <= 59)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_time_settings`
--

INSERT INTO `menu_time_settings` (`menu_type`, `start_hour`, `end_hour`, `last_updated`, `start_minute`, `end_minute`) VALUES
('Breakfast', 1, 11, '2025-06-17 17:49:01', 0, 0),
('Dinner', 19, 23, '2025-06-17 17:45:09', 0, 54),
('Lunch', 11, 16, '2025-06-17 17:32:25', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `nfc_card`
--

CREATE TABLE `nfc_card` (
  `nfc_id` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `current_balance` decimal(10,2) DEFAULT 0.00,
  `pin_hash` varchar(255) NOT NULL COMMENT 'Hashed 4-digit PIN',
  `password_change_hash` varchar(255) DEFAULT NULL COMMENT 'For reset purposes',
  `status` enum('Active','Inactive','Lost','Blocked') DEFAULT 'Active',
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nfc_card`
--

INSERT INTO `nfc_card` (`nfc_id`, `student_id`, `current_balance`, `pin_hash`, `password_change_hash`, `status`, `last_used`) VALUES
('1001', 1, 705.00, '$2y$10$Km/S0eAv7X4Gc2xsgIyatufs9NgKlJXAu/g5FpalQ7KDT78eO5IpK', NULL, 'Active', '2025-06-18 00:14:23');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Store only hashed passwords',
  `role` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `password_reset_token` varchar(100) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `theme_preference` varchar(10) DEFAULT 'light' COMMENT 'User preferred theme (light/dark)',
  `items_per_page` int(11) DEFAULT 10 COMMENT 'Preferred number of items per page in tables'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `full_name`, `username`, `password_hash`, `role`, `is_active`, `last_login`, `created_at`, `password_reset_token`, `token_expiry`, `theme_preference`, `items_per_page`) VALUES
(1, 'admin user', 'admin', '$2y$10$gQV.R4xUWck7z6zoAzNeAep.1J3BjBJqwsuGTbujeOkWOn2BJiL/G', 'administrator', 1, '2025-06-17 22:31:09', '2025-05-08 01:06:43', NULL, NULL, 'light', 10);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `student_email` varchar(100) NOT NULL,
  `parent_email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nfc_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `full_name`, `contact_number`, `student_email`, `parent_email`, `username`, `nfc_id`) VALUES
(1, 'aman paudel', '9800000000', 'aman@gmail.com', 'dhakalsandesh664@gmail.com', 'aman', '1001');

-- --------------------------------------------------------

--
-- Table structure for table `time_based_menu`
--

CREATE TABLE `time_based_menu` (
  `id` int(11) NOT NULL,
  `menu_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `food_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_based_menu`
--

INSERT INTO `time_based_menu` (`id`, `menu_type`, `food_id`) VALUES
(10, 'Breakfast', 10),
(11, 'Lunch', 1),
(15, 'Dinner', 2);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `txn_id` int(11) NOT NULL,
  `nfc_id` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) UNSIGNED NOT NULL,
  `status` enum('success','failed','refunded') DEFAULT 'success',
  `transaction_time` datetime NOT NULL DEFAULT current_timestamp(),
  `formatted_id` varchar(20) GENERATED ALWAYS AS (concat('TXN-',date_format(`transaction_time`,'%Y%m%d-'),lpad(`txn_id`,5,'0'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`txn_id`, `nfc_id`, `student_id`, `total_amount`, `status`, `transaction_time`) VALUES
(1, '1001', 1, 190.00, 'success', '2025-06-17 23:46:25'),
(2, '1001', 1, 15.00, 'success', '2025-06-17 23:49:40'),
(3, '1001', 1, 15.00, 'success', '2025-06-18 00:02:50'),
(4, '1001', 1, 15.00, 'success', '2025-06-18 00:06:25'),
(5, '1001', 1, 15.00, 'success', '2025-06-18 00:07:55'),
(6, '1001', 1, 15.00, 'success', '2025-06-18 00:09:15'),
(7, '1001', 1, 15.00, 'success', '2025-06-18 00:09:25'),
(8, '1001', 1, 15.00, 'success', '2025-06-18 00:14:23');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_item`
--

CREATE TABLE `transaction_item` (
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `txn_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) UNSIGNED NOT NULL,
  `item_total` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_item`
--

INSERT INTO `transaction_item` (`item_id`, `txn_id`, `food_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 1, 20.00),
(2, 1, 2, 2, 85.00),
(3, 2, 3, 1, 15.00),
(4, 3, 3, 1, 15.00),
(5, 4, 3, 1, 15.00),
(6, 5, 3, 1, 15.00),
(7, 6, 3, 1, 15.00),
(8, 7, 3, 1, 15.00),
(9, 8, 3, 1, 15.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_related_id` (`related_id`);

--
-- Indexes for table `food`
--
ALTER TABLE `food`
  ADD PRIMARY KEY (`food_id`);

--
-- Indexes for table `menu_time_settings`
--
ALTER TABLE `menu_time_settings`
  ADD PRIMARY KEY (`menu_type`);

--
-- Indexes for table `nfc_card`
--
ALTER TABLE `nfc_card`
  ADD PRIMARY KEY (`nfc_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_nfc_student` (`student_id`),
  ADD KEY `idx_nfc_status` (`status`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_email` (`student_email`),
  ADD UNIQUE KEY `nfc_id` (`nfc_id`);

--
-- Indexes for table `time_based_menu`
--
ALTER TABLE `time_based_menu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_type_food_id` (`menu_type`,`food_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`txn_id`),
  ADD KEY `nfc_id` (`nfc_id`),
  ADD KEY `idx_transaction_student` (`student_id`);

--
-- Indexes for table `transaction_item`
--
ALTER TABLE `transaction_item`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `idx_item_transaction` (`txn_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `food`
--
ALTER TABLE `food`
  MODIFY `food_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `time_based_menu`
--
ALTER TABLE `time_based_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `txn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transaction_item`
--
ALTER TABLE `transaction_item`
  MODIFY `item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `nfc_card`
--
ALTER TABLE `nfc_card`
  ADD CONSTRAINT `nfc_card_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `time_based_menu`
--
ALTER TABLE `time_based_menu`
  ADD CONSTRAINT `time_based_menu_ibfk_1` FOREIGN KEY (`food_id`) REFERENCES `food` (`food_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`nfc_id`) REFERENCES `nfc_card` (`nfc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_item`
--
ALTER TABLE `transaction_item`
  ADD CONSTRAINT `transaction_item_ibfk_1` FOREIGN KEY (`txn_id`) REFERENCES `transaction` (`txn_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_item_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `food` (`food_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
