-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2026 at 12:13 PM
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
-- Database: `property_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `allottees`
--

CREATE TABLE `allottees` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `allottee_name` varchar(150) NOT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `aadhar_no` varchar(20) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `aadhar_photo` varchar(255) DEFAULT NULL,
  `pan_photo` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `allotment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allottees`
--

INSERT INTO `allottees` (`id`, `property_id`, `allottee_name`, `father_name`, `mobile`, `aadhar_no`, `pan_no`, `aadhar_photo`, `pan_photo`, `address`, `allotment_date`, `created_at`) VALUES
(1, 2, 'Ramesh Kumar', 'Vinod kumar', '9595971312', '788765309894', 'ETBOG6755H', 'aadhar_1784959817_3314.jpeg', 'pan_1784959817_7640.jpeg', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '2026-07-25', '2026-07-25 06:10:17'),
(2, 3, 'Vinod kumar', 'Ramesh singh', '9878130678', '546434029845', 'WAVFP7643M', 'aadhar_1784962443_5036.jpeg', 'pan_1784962443_8979.jpeg', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '2026-07-25', '2026-07-25 06:54:03'),
(3, 30, 'Anju panday', 'Ritesh Panday', '9876541234', '987654321123', 'ESWER4325M', 'aadhar_1784963893_8045.jpg', 'pan_1784963893_7325.jpg', 'Gomati Nagar Alok khand', '2026-07-24', '2026-07-25 07:17:51'),
(4, 4, 'Rajesh Kumar', 'Saran LAl', '9878123298', '098765432112', 'asdf2345q', 'aadhar_1784965686_1165.jpg', 'pan_1784965686_9067.jpeg', '1ad Annat Nagar scheme I', '2026-07-03', '2026-07-25 07:48:06'),
(5, 1, 'manpj kumar sagar', 'ravinandan singh', '8787120278', '767687929897', 'ESDWF6544M', 'aadhar_1784965975_1743.jpeg', 'pan_1784965975_4791.jpeg', 'LUCKNOW GOMTI NAGAR VIRAJ KHAND', '2026-07-08', '2026-07-25 07:52:55');

-- --------------------------------------------------------

--
-- Table structure for table `citizens`
--

CREATE TABLE `citizens` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `is_verified` enum('no','yes') DEFAULT 'no',
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `citizens`
--

INSERT INTO `citizens` (`id`, `phone_number`, `full_name`, `is_verified`, `status`, `created_at`) VALUES
(1, '9876543210', NULL, 'yes', 'active', '2026-08-20 10:06:40'),
(2, '9878130678', NULL, 'yes', 'active', '2026-08-20 10:08:59');

-- --------------------------------------------------------

--
-- Table structure for table `citizen_requests`
--

CREATE TABLE `citizen_requests` (
  `id` int(11) NOT NULL,
  `citizen_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `request_type` enum('Mutation','KYC','NOC','Surrender') NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('Submitted','Under Review','Approved','Rejected') DEFAULT 'Submitted',
  `reference_number` varchar(50) NOT NULL,
  `decision_remark` varchar(255) DEFAULT NULL,
  `decision_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `login_time`, `ip_address`) VALUES
(1, 1, '2026-07-25 05:36:23', '::1'),
(2, 1, '2026-07-25 06:10:36', '::1'),
(3, 2, '2026-07-25 06:12:15', '::1'),
(4, 1, '2026-07-25 06:34:27', '::1'),
(5, 4, '2026-07-25 06:50:57', '::1'),
(6, 1, '2026-07-25 06:52:14', '::1'),
(7, 4, '2026-07-25 07:18:39', '::1'),
(8, 4, '2026-07-25 07:20:23', '::1'),
(9, 1, '2026-07-25 07:25:18', '::1'),
(10, 4, '2026-07-25 07:26:18', '::1'),
(11, 1, '2026-07-25 07:27:09', '::1'),
(12, 4, '2026-07-25 07:38:32', '::1'),
(13, 2, '2026-07-25 07:39:02', '::1'),
(14, 1, '2026-07-25 07:39:23', '::1'),
(15, 1, '2026-07-25 08:05:19', '::1'),
(16, 1, '2026-08-07 12:17:28', '::1'),
(17, 4, '2026-08-07 12:20:17', '::1'),
(18, 1, '2026-08-20 10:00:33', '::1'),
(19, 1, '2026-08-20 10:08:26', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `scheme_name` varchar(150) NOT NULL,
  `scheme_address` varchar(255) DEFAULT NULL,
  `property_no` varchar(50) NOT NULL,
  `property_code` varchar(50) NOT NULL,
  `address` varchar(255) NOT NULL,
  `area_size` varchar(50) DEFAULT NULL,
  `property_type` enum('Residential','Commercial','Shop','Office','Plot','Flat') NOT NULL DEFAULT 'Residential',
  `allotment_date` date DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `category` enum('Lottery','Auction','FCFS','Direct Allotment') NOT NULL,
  `status` enum('Available','Pending','Sold','Allotted') DEFAULT 'Available',
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `added_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `scheme_name`, `property_no`, `property_code`, `address`, `area_size`, `price`, `category`, `status`, `description`, `image`, `added_by`, `created_at`) VALUES
(1, 'ParkView Apartments, Basant Kunj Yogna.', 'P-101', '504001', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '1200 sq ft', 2500000.00, 'Lottery', 'Allotted', NULL, NULL, 1, '2026-07-25 06:04:59'),
(2, 'Anant Nagar Yojana Phase-IV', 'P-102', '504002', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '1500 sq ft', 3200000.00, 'Auction', 'Allotted', NULL, NULL, 1, '2026-07-25 06:08:24'),
(3, 'Anant Nagar Yojana Phase-IV', 'P-103', '504003', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '1800 sq ft', 3900000.00, 'Lottery', 'Allotted', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', NULL, 1, '2026-07-25 06:08:24'),
(4, 'Anant Nagar Yojana Phase-IV', 'P-104', '504004', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '2100 sq ft', 4600000.00, 'Auction', 'Allotted', NULL, NULL, 1, '2026-07-25 06:08:24'),
(5, 'Anant Nagar Yojana Phase-IV', 'P-105', '504005', 'RAIBAREILY ROAD (Sch) > SHARDA NAGAR (Sub Sch) > RATAN KHAND (Sect) > RATAN LOK (Apart)', '2400 sq ft', 5300000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(6, 'Anant Nagar Yojana Phase-IV', 'P-106', '504006', 'SITAPUR ROAD (Sch) > JANAKIPURAM (Sub Sch) > SECTOR-J EXTENSION (Sect) > SULABH AWAS (Apart)', '2700 sq ft', 6000000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(7, 'Anant Nagar Yojana Phase-IV', 'P-107', '504007', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '3000 sq ft', 6700000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(8, 'Anant Nagar Yojana Phase-IV', 'P-108', '504008', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '3300 sq ft', 7400000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(9, 'Anant Nagar Yojana Phase-IV', 'P-109', '504009', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '3600 sq ft', 8100000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(10, 'Anant Nagar Yojana Phase-IV', 'P-110', '504010', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '3900 sq ft', 8800000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(11, 'Aish Bagh scheme', 'P-111', '504011', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '4200 sq ft', 9500000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(12, 'Aishbagh Square Scheme', 'P-112', '504012', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '4500 sq ft', 10200000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(13, 'Anant Nagar Phase-IV', 'P-113', '504013', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '4800 sq ft', 10900000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(14, 'Park-View Apartments', 'P-114', '504014', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '5100 sq ft', 11600000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(15, 'ParkView Apartments, Basant Kunj Yogna.', 'P-115', '504015', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '5400 sq ft', 12300000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(16, 'Anant Nagar Yojana Phase-IV', 'P-116', '504016', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '5700 sq ft', 13000000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(17, 'Anant Nagar Yojana Phase-IV', 'P-117', '504017', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '6000 sq ft', 13700000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(18, 'Anant Nagar Yojana Phase-IV', 'P-118', '504018', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '6300 sq ft', 14400000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(19, 'Anant Nagar Yojana Phase-IV', 'P-119', '504019', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '6600 sq ft', 15100000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(20, 'Anant Nagar Yojana Phase-IV', 'P-120', '504020', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '6900 sq ft', 15800000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(21, 'Anant Nagar Yojana Phase-IV', 'P-121', '504021', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '7200 sq ft', 16500000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(22, 'Anant Nagar Yojana Phase-IV', 'P-122', '504022', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '7500 sq ft', 17200000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(23, 'Anant Nagar Yojana Phase-IV', 'P-123', '504023', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '7800 sq ft', 17900000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(24, 'Anant Nagar Yojana Phase-IV', 'P-124', '504024', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '8100 sq ft', 18600000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(25, 'Aish Bagh scheme', 'P-125', '504025', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '8400 sq ft', 19300000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(26, 'Aishbagh Square Scheme', 'P-126', '504026', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '8700 sq ft', 20000000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(27, 'Anant Nagar Phase-IV', 'P-127', '504027', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '9000 sq ft', 20700000.00, 'Lottery', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(28, 'Park-View Apartments', 'P-128', '504028', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '9300 sq ft', 21400000.00, 'Auction', 'Available', NULL, NULL, 1, '2026-07-25 06:08:24'),
(29, 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '1RP-111', '504098', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '120', 160000.00, 'FCFS', 'Available', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', NULL, 1, '2026-07-25 06:55:32'),
(30, 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '1RP-112', '504097', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', '113', 170000.00, 'Direct Allotment', 'Allotted', 'DEVPUR PARA (Sch) > NOT APPLICABLE (Sub Sch) > PARA (Sect) > KABIR NAGAR (Apart)', NULL, 1, '2026-07-25 06:57:38'),
(31, 'BALA GANJ SCHEME', '1/9861', '505001', 'BALAGANJ STETION', '1500', 9800002.00, 'Direct Allotment', 'Available', '', 'prop_1784966208_6008.png', 1, '2026-07-25 07:56:48');

-- --------------------------------------------------------

--
-- Table structure for table `property_payments`
--

CREATE TABLE `property_payments` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_payments`
--

INSERT INTO `property_payments` (`id`, `property_id`, `payment_date`, `amount`, `payment_mode`, `reference_no`, `bank_account`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 3, '2026-07-25', 12000.00, 'Bank Transfer', '12345678', '9876543', 'Installment', 1, '2026-07-25 07:14:11'),
(2, 3, '2026-07-25', 12000.00, 'Bank Transfer', '12345678', '9876543', 'Installment', 1, '2026-07-25 07:14:24'),
(3, 30, '2026-07-25', 170000.00, 'Cash', '1234567', '12345678', 'FULL PAYMENT', 4, '2026-07-25 07:19:19'),
(4, 30, '2026-07-25', 1234567.00, 'Other', '1234567', '1234567', 'ASDFGHJKL', 4, '2026-07-25 07:19:39'),
(5, 4, '2026-07-25', 234.00, 'QR', '234', '12345', 'asdfg', 1, '2026-07-25 07:48:32'),
(6, 4, '2026-07-25', 234.00, 'QR', '234', '12345', 'asdfg', 1, '2026-07-25 07:48:43'),
(7, 30, '2026-08-07', 23456.00, 'Cash', '234567', '1234567890', 'sdfghuik', 4, '2026-08-07 12:21:21');

-- --------------------------------------------------------

--
-- Table structure for table `public_announcements`
--

CREATE TABLE `public_announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `display_priority` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Admin'),
(3, 'LDA'),
(2, 'Property Officer'),
(5, 'SO'),
(4, 'UDC');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'helpline_number', '+91 522 2011501'),
(2, 'portal_contact_email', 'support@ldalucknow.in'),
(3, 'portal_name', 'LDA Property Portal');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password`, `role_id`, `status`, `created_at`) VALUES
(1, 'System Admin', 'admin', 'admin@example.com', '$2y$10$/PK.0XEpEdC69pA3T8iVceE4oO.qopdnVfupXei9K0SoxCJiVMdoy', 1, 'active', '2026-07-25 05:36:08'),
(2, 'Vinod Kumar Shrivastava', 'UDC123', 'ldaerp123@gmail.com', '$2y$10$6xe9xR/54j0xPP1MI1K7QulmlDYmXDU72gEm0fg07Q/.K1GqnM54W', 4, 'active', '2026-07-25 06:11:46'),
(3, 'Abhijeet Singh', 'PO123', 'ldaerp1234@gmail.com', '$2y$10$A3ALVF3oSWBcLSk9lByQPuNueHUnxsm.2aOyKr3ytngonDxsxhiV.', 2, 'active', '2026-07-25 06:35:20'),
(4, 'Sanjay Tiwari', 'SO123', 'ldaerp12345@gmail.com', '$2y$10$czKUoCL/B1jO3wNffJRMVueDUqA0mNYu9F4hoiUDiMx7tuIrgXP.O', 5, 'active', '2026-07-25 06:36:04');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` enum('Lottery','Auction','FCFS','Direct Allotment') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `category`) VALUES
(1, 2, 'Auction'),
(10, 3, 'Lottery'),
(11, 3, 'Auction'),
(12, 3, 'FCFS'),
(6, 4, 'Lottery'),
(7, 4, 'Auction'),
(8, 4, 'FCFS'),
(9, 4, 'Direct Allotment');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allottees`
--
ALTER TABLE `allottees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `citizens`
--
ALTER TABLE `citizens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone_number` (`phone_number`);

--
-- Indexes for table `citizen_requests`
--
ALTER TABLE `citizen_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `citizen_id` (`citizen_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `property_code` (`property_code`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `property_payments`
--
ALTER TABLE `property_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `public_announcements`
--
ALTER TABLE `public_announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`user_id`,`category`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allottees`
--
ALTER TABLE `allottees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `citizens`
--
ALTER TABLE `citizens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `citizen_requests`
--
ALTER TABLE `citizen_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `property_payments`
--
ALTER TABLE `property_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `public_announcements`
--
ALTER TABLE `public_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allottees`
--
ALTER TABLE `allottees`
  ADD CONSTRAINT `allottees_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `citizen_requests`
--
ALTER TABLE `citizen_requests`
  ADD CONSTRAINT `citizen_requests_ibfk_1` FOREIGN KEY (`citizen_id`) REFERENCES `citizens` (`id`),
  ADD CONSTRAINT `citizen_requests_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`);

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `property_payments`
--
ALTER TABLE `property_payments`
  ADD CONSTRAINT `property_payments_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
