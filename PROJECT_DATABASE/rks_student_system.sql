-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 12:24 PM
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
-- Database: `rks_student_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `activity_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `activity_name`, `description`) VALUES
(1, 'NSS', 'National Service Scheme'),
(2, 'JRC', 'Junior Red Cross'),
(3, 'NCC', 'National Cadet Corps'),
(4, 'Cultural Committee', 'Cultural Activities'),
(5, 'Sports Committee', 'Sports Activities'),
(6, 'Tech Club', 'Technical Club'),
(7, 'Debate Club', 'Debate and Discussion'),
(8, 'phothography', 'User-added activity'),
(9, 'NSS', 'National Service Scheme'),
(10, 'JRC', 'Junior Red Cross'),
(11, 'NCC', 'National Cadet Corps'),
(12, 'Cultural Committee', 'Cultural Activities'),
(13, 'Sports Committee', 'Sports Activities'),
(14, 'Tech Club', 'Technical Club'),
(15, 'Debate Club', 'Debate and Discussion'),
(16, 'NSS', 'National Service Scheme'),
(17, 'JRC', 'Junior Red Cross'),
(18, 'NCC', 'National Cadet Corps'),
(19, 'Cultural Committee', 'Cultural Activities'),
(20, 'Sports Committee', 'Sports Activities'),
(21, 'Tech Club', 'Technical Club'),
(22, 'Debate Club', 'Debate and Discussion');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `certificate_title` varchar(200) DEFAULT NULL,
  `certificate_file` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_schedules`
--

CREATE TABLE `class_schedules` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `teacher_name` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `dept_code` varchar(10) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `hod_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_code`, `dept_name`, `full_name`, `hod_id`, `created_at`) VALUES
(1, 'DME', 'DME', 'Diploma in Mechanical Engineering', NULL, '2026-02-25 05:24:04'),
(2, 'DAE', 'DAE', 'Diploma in Automobile Engineering', 11, '2026-02-25 05:24:04'),
(3, 'DCE', 'DCE', 'Diploma in Compter Engineering', NULL, '2026-02-25 05:24:04');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `subject`, `message`, `is_read`, `created_at`) VALUES
(57, 68, 1, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:23:15'),
(58, 68, 58, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:23:15'),
(59, 68, 73, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:23:15'),
(60, 68, 1, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:50:41'),
(61, 68, 58, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:50:41'),
(62, 68, 73, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:50:41'),
(63, 68, 1, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:50:49'),
(64, 68, 58, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:50:49'),
(65, 68, 73, 'Profile Update Notification', 'Student profile information was changed.', 0, '2026-03-20 08:50:49'),
(66, 86, 53, 'hi', 'hi', 0, '2026-04-02 10:02:45'),
(67, 68, 53, 'TEACHER BEATING', 'vetrii', 0, '2026-04-08 10:42:59');

-- --------------------------------------------------------

--
-- Table structure for table `profile_edit_requests`
--

CREATE TABLE `profile_edit_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by_hod` int(11) DEFAULT NULL,
  `hod_approval_date` timestamp NULL DEFAULT NULL,
  `approved_by_admin` int(11) DEFAULT NULL,
  `admin_approval_date` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile_edit_requests`
--

INSERT INTO `profile_edit_requests` (`id`, `student_id`, `user_id`, `status`, `requested_at`, `approved_by_hod`, `hod_approval_date`, `approved_by_admin`, `admin_approval_date`, `rejection_reason`) VALUES
(1, 23, 44, 'pending', '2026-03-12 04:13:02', NULL, NULL, NULL, NULL, NULL),
(2, 19, 33, 'pending', '2026-03-12 04:43:49', NULL, NULL, NULL, NULL, NULL),
(3, 32, 68, 'approved', '2026-03-19 11:02:28', NULL, NULL, 68, '2026-03-19 11:02:28', NULL),
(4, 33, 69, 'approved', '2026-03-19 11:06:56', NULL, NULL, 69, '2026-03-19 11:06:56', NULL),
(5, 38, 86, 'approved', '2026-04-02 10:02:15', NULL, NULL, 86, '2026-04-02 10:02:15', NULL),
(6, 36, 84, 'approved', '2026-04-02 10:05:29', NULL, NULL, 84, '2026-04-02 10:05:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `request_type` enum('leave','certificate','transfer','other') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `department` varchar(50) DEFAULT NULL,
  `requested_date` date DEFAULT NULL,
  `response_date` date DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sent_emails`
--

CREATE TABLE `sent_emails` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sports`
--

CREATE TABLE `sports` (
  `id` int(11) NOT NULL,
  `sport_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sports`
--

INSERT INTO `sports` (`id`, `sport_name`, `description`) VALUES
(1, 'Cricket', 'Cricket Sport'),
(2, 'Football', 'Football/Soccer'),
(3, 'Basketball', 'Basketball'),
(4, 'Badminton', 'Badminton'),
(5, 'Tennis', 'Tennis'),
(6, 'Volleyball', 'Volleyball'),
(7, 'Hockey', 'Hockey'),
(8, 'Athletics', 'Track and Field'),
(9, 'CHESS', 'User-added sport'),
(10, 'kho-kho', 'User-added sport'),
(11, 'Cricket', 'Cricket Sport'),
(12, 'Football', 'Football/Soccer'),
(13, 'Basketball', 'Basketball'),
(14, 'Badminton', 'Badminton'),
(15, 'Tennis', 'Tennis'),
(16, 'Volleyball', 'Volleyball'),
(17, 'Hockey', 'Hockey'),
(18, 'Athletics', 'Track and Field'),
(19, 'Cricket', 'Cricket Sport'),
(20, 'Football', 'Football/Soccer'),
(21, 'Basketball', 'Basketball'),
(22, 'Badminton', 'Badminton'),
(23, 'Tennis', 'Tennis'),
(24, 'Volleyball', 'Volleyball'),
(25, 'Hockey', 'Hockey'),
(26, 'Athletics', 'Track and Field');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `registration_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `aadhar_number` varchar(12) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `mobile` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `other_activities` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `registration_number`, `first_name`, `last_name`, `father_name`, `mother_name`, `aadhar_number`, `date_of_birth`, `mobile`, `email`, `department_id`, `semester`, `profile_pic`, `video_file`, `other_activities`, `created_at`, `updated_at`) VALUES
(17, 29, '25508883', 'VELAUDAMA ABHI NARASIMHA', 'REDDY', 'lqiyewwfqy', 'ludkgjfshmz', '123485732628', '2008-02-01', '9849732963', 'abhinarasimhareddyv@gmal.com', 2, NULL, NULL, '', '', '2026-03-06 10:13:48', '2026-03-06 10:13:48'),
(18, 30, 'RKS202600030', 'vetriivel', '', NULL, NULL, NULL, NULL, NULL, 'vetriivel.pm.2027@rkmshome.org', NULL, 1, NULL, NULL, NULL, '2026-03-09 09:54:33', '2026-03-09 09:54:33'),
(19, 33, 'RKS202600033', 'shanmugam', 'm', '', '', '123435678901', '2009-03-13', '', 'shanmugam.m.2027@rkmshome.org', NULL, 1, '', '', '', '2026-03-09 09:54:33', '2026-03-12 03:57:51'),
(20, 34, 'RKS202600034', 'AKASH B', '', NULL, NULL, NULL, NULL, NULL, 'akash.b.2027@rkmshome.org', NULL, 1, NULL, NULL, NULL, '2026-03-09 09:54:33', '2026-03-09 09:54:33'),
(21, 35, 'RKS202600035', 'Aruna Nithi.pa', '', NULL, NULL, NULL, NULL, NULL, 'arunanithi.pa.2027@rkmshome.org', NULL, 1, NULL, NULL, NULL, '2026-03-09 09:54:33', '2026-03-09 09:54:33'),
(22, 42, '1234', 'xcvb', 'rdvghkj,.', 'nag', 'hgcvhbjn', '987645689703', '2009-01-01', '1234567890', 'abcd@gmail.com', 1, NULL, NULL, '', '', '2026-03-10 09:52:43', '2026-03-10 09:52:43'),
(23, 44, '255088883', 'madhusudhan', 'reddy', 'narasimha reddy', 'bharathi', '120298273645', '2007-01-26', '9515512334', 'madhusudhanreddy.s.2027@rkmshome.org', 3, NULL, '23_1773223747.png', '', '', '2026-03-11 06:31:34', '2026-03-11 10:09:07'),
(24, 46, '25508877', 'nandakumar', 'cm', 'muthu', 'devi', '123456789165', '2009-07-03', '6303534357', 'nandakumar.cm.dce@gmail.com', 3, NULL, '24_1773287056.png', '', '', '2026-03-11 06:44:00', '2026-03-12 03:44:16'),
(25, 47, '25508888', 'SuDhArSaN', 'B', 'vanakkam', 'amma', '123456789102', '0001-01-01', '9876543201', 'sudharsan.b.vanakkam@gmail.com', 3, NULL, NULL, '', '', '2026-03-11 07:45:08', '2026-03-11 07:45:08'),
(27, 49, '25508885', 'VELAUDAM', 'ABHI', 'MUNASWAMY', 'KALPANA', '785698376897', '2009-07-01', '9849732963', 'velaudamabhinarasimhareddy.v.2027@rkmshome.org', 3, NULL, '27_1773286470.png', '', '', '2026-03-11 08:41:15', '2026-03-12 03:34:30'),
(28, 64, '13211', 'yuvaraja', 'a', 'MUNASWAMY', 'bharathi', '123456788765', '2026-03-19', '5467895354', 'abcfd@gmail.com', 1, 1, NULL, '', '', '2026-03-12 09:03:52', '2026-03-12 09:03:52'),
(29, 65, '25508889', 'VELAUDAMA ABHI NARASIMHA', 'REDDY', 'MUNASWAMY', 'KALPANA', '630926179005', '2009-01-07', '9849732963', 'abhinarasimhareddy@gmail.com', 3, 1, NULL, '', '', '2026-03-12 09:06:25', '2026-03-12 09:06:25'),
(30, 66, '123456', 'user', 'us', 'king', 'queen', '098765432345', '2009-01-01', '9876546789', 'user@gmail.com', 1, 1, NULL, '', '', '2026-03-12 09:16:11', '2026-03-12 09:16:11'),
(31, 67, '25508890', 'Priya', 'V`', 'Gopal', 'Deepa', '987654312345', '2009-12-10', '9876543201', 'priya@gmail.com', 3, 1, NULL, '', '', '2026-03-19 10:39:19', '2026-03-19 10:39:19'),
(32, 68, '255088900', 'VETRIIVEL', 'PM', 'PRABHU V', 'MYTHILI P', '224986620122', '2008-10-22', '9344142773', 'vetriivel.pm.2027@gmail.com', 3, 1, '32_1773996649.png', '', '', '2026-03-19 10:44:23', '2026-03-20 08:50:49'),
(33, 69, '25508857', 'ESWARAPANDI', 'M', 'RAMA', 'DHIVE', '696606656441', '2008-07-21', '9585806250', 'eswarapandi.m.2027@rkmshome.org', 3, 1, NULL, '', '', '2026-03-19 11:06:22', '2026-03-19 11:06:22'),
(34, 73, 'hoddce123', 'DHILIP', 'KUMAR', 'abcd', 'abcd', '908765431234', '1974-01-01', '6787654634', 'hoddce@rkmshome.org', 3, 1, NULL, '', '', '2026-03-20 08:10:08', '2026-03-20 08:10:08'),
(35, 74, 'Secretary123', 'swami dharmistanandha', 'maharaj', 'RAMAKRISHNA', 'SARADA', '897643534568', '1965-01-01', '6787654634', 'Secretary@rkmshome.org', 2, 1, NULL, '', '', '2026-03-20 09:05:16', '2026-03-20 09:05:16'),
(36, 84, '25508', 'Gokul', 'SK', 'father', 'mather', '123456789112', '2009-03-13', '1234678923', NULL, 3, 1, NULL, NULL, NULL, '2026-03-20 10:38:23', '2026-03-20 10:38:23'),
(37, 85, '12345', 'yuvaraj', 'm', 'father', 'mather', '123456778998', '2009-03-13', '1234567891', NULL, 3, 2, NULL, NULL, NULL, '2026-03-20 10:49:17', '2026-03-20 10:49:17'),
(38, 86, '765789', 'Z', 'U', 'd', 'sfdg', '234223456532', '2026-04-25', '8765456789', 'zye@gmail.com', 2, 1, NULL, '', '', '2026-04-02 10:01:48', '2026-04-02 10:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `student_activities`
--

CREATE TABLE `student_activities` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_activities`
--

INSERT INTO `student_activities` (`id`, `student_id`, `activity_id`, `added_at`) VALUES
(22, 17, 4, '2026-03-06 10:13:48'),
(23, 22, 5, '2026-03-10 09:52:43'),
(24, 22, 6, '2026-03-10 09:52:43'),
(25, 23, 11, '2026-03-11 06:31:34'),
(26, 25, 5, '2026-03-11 07:45:10'),
(27, 25, 13, '2026-03-11 07:45:10'),
(28, 28, 1, '2026-03-12 09:03:52'),
(29, 29, 8, '2026-03-12 09:06:25'),
(30, 30, 8, '2026-03-12 09:16:11'),
(31, 31, 4, '2026-03-19 10:39:19'),
(32, 32, 8, '2026-03-19 10:44:23'),
(33, 33, 1, '2026-03-19 11:06:23'),
(34, 33, 8, '2026-03-19 11:06:23'),
(35, 33, 5, '2026-03-19 11:06:23'),
(36, 34, 8, '2026-03-20 08:10:08'),
(37, 35, 8, '2026-03-20 09:05:16'),
(38, 35, 6, '2026-03-20 09:05:16'),
(39, 38, 1, '2026-04-02 10:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `student_sports`
--

CREATE TABLE `student_sports` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `sport_id` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_sports`
--

INSERT INTO `student_sports` (`id`, `student_id`, `sport_id`, `added_at`) VALUES
(42, 17, 7, '2026-03-06 10:13:48'),
(43, 22, 14, '2026-03-10 09:52:43'),
(45, 24, 6, '2026-03-11 06:44:00'),
(46, 24, 16, '2026-03-11 06:44:00'),
(47, 24, 24, '2026-03-11 06:44:00'),
(48, 25, 8, '2026-03-11 07:45:09'),
(49, 25, 18, '2026-03-11 07:45:09'),
(50, 25, 26, '2026-03-11 07:45:09'),
(51, 25, 2, '2026-03-11 07:45:10'),
(52, 25, 12, '2026-03-11 07:45:10'),
(53, 25, 20, '2026-03-11 07:45:10'),
(71, 23, 10, '2026-03-11 09:48:55'),
(73, 27, 8, '2026-03-11 09:51:38'),
(74, 27, 4, '2026-03-11 09:51:38'),
(75, 27, 2, '2026-03-11 09:51:38'),
(76, 27, 10, '2026-03-11 09:51:38'),
(77, 28, 1, '2026-03-12 09:03:52'),
(78, 29, 5, '2026-03-12 09:06:25'),
(80, 31, 6, '2026-03-19 10:39:19'),
(81, 32, 9, '2026-03-19 10:44:23'),
(82, 32, 1, '2026-03-19 10:44:23'),
(83, 32, 2, '2026-03-19 10:44:23'),
(84, 33, 1, '2026-03-19 11:06:22'),
(85, 34, 10, '2026-03-20 08:10:08'),
(86, 35, 5, '2026-03-20 09:05:16'),
(87, 38, 4, '2026-04-02 10:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `subject_specialization` varchar(200) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `class_name` varchar(50) DEFAULT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(20) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','secretary','principal','hod','class_teacher','student') NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `department`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$12$vgjoPTE8t1ar/uWzSOt21OTlUDtvv90ZnE5aFT2YVJr0ADnAgWklC', 'admin@rkmshome.edu', 'admin', NULL, '2026-02-25 05:24:04', '2026-03-11 06:24:14'),
(53, 'abhi_2009_', '$2y$12$YUFW64T4eGsoPGC8JO.vgOXmhkBHChSoaHBPBbC9zScX9V74aBi1q', 'abhinarasimhareddy2009@gmail.com', 'class_teacher', '', '2026-03-12 06:13:00', '2026-03-12 09:12:19'),
(54, 'abhi_2009_00', '$2y$12$U0rotr39LYLgFTZcMNdvwe3nXnh/BH1K5yLZp9u.gfTb3lPFUA4pe', 'ABHI@GMAL.COM', 'student', '3', '2026-03-12 06:20:20', '2026-03-12 06:20:20'),
(55, 'shanmugam', '$2y$12$e/OU9uzG49gu09pBq2MW1uOraXA/X4/ZGXqT/z2Q/8nBZIOmedbCO', 'shanmugam.m.2027@rkmshome.org', 'student', '3', '2026-03-12 06:20:37', '2026-03-12 06:20:37'),
(56, 'sivamani', '$2y$12$HmeS5073Yc2Aeby6fiSiFuTV.YZs.I26LgnGu1KOW.YYhDwslF5aS', 'sivamani.a.2027@rkmshome.org', 'student', '3', '2026-03-12 06:43:00', '2026-03-12 06:43:00'),
(57, 'abhi_2009_01', '$2y$12$6Guy5V6o8.9lhK9lkW6Xaer20MRet8KOWbzjXw4eXGLyZG.lIUlUi', 'abcd@gmail.com', 'student', '3', '2026-03-12 07:41:05', '2026-03-12 07:41:05'),
(58, 'raja', '$2y$12$wEyZRl5XeXbL4jEfcS1bcOGg5FO630hFFa0M.SIlrHhjTmMTLtrqG', 'king@gnail.com', 'admin', 'DCE', '2026-03-12 07:49:01', '2026-03-12 09:26:20'),
(59, 'aba', '$2y$12$9h9sfcTsN1Fkvfa7sxRmt.Io9qFWU6.QPP.zxCwqMNrCphb1j325u', 'abcdea@gmail.com', 'student', '3', '2026-03-12 08:35:24', '2026-03-12 08:35:24'),
(60, 'mmm', '$2y$12$xSaGMAO.70vFWmx1vTVK3uodz/8w.ZDRUrcUbRGVg0zQbcVivfmcG', 'xfcnvbm@gmail.com', 'student', '1', '2026-03-12 08:39:09', '2026-03-12 08:39:09'),
(61, 'abhi', '$2y$12$10xpgwriuJhaT59KoBw34u70qdatGtZV8H4pHkEIj8hITHQRurzMC', 'abhi234@gmail.com', 'student', '1', '2026-03-12 08:42:05', '2026-03-12 08:42:05'),
(62, 'abcdeg', '$2y$12$uEOXOZoOg6n.IRAn52edne7Dru1Fk2YFkELAX9AL8XHsZuW2inio.', 'add@gmail.com', 'student', '2', '2026-03-12 08:44:41', '2026-03-12 08:44:41'),
(63, 'qwer', '$2y$12$IiasBkuDBrCdhZD2Muo0gOPRFS2LEHA3lFO7I4BXopOtwTUhta5sm', 'asdf@gmail.com', 'student', '2', '2026-03-12 08:48:06', '2026-03-12 08:48:06'),
(64, 'abh1', '$2y$12$6Md88rBXlst8Yvs5IahuaOyGsph3PdV3h0LmBkKcZ.NiPi.FQ5W4S', 'abcfd@gmail.com', 'student', '1', '2026-03-12 09:03:52', '2026-03-12 09:03:52'),
(65, 'abhi@123', '$2y$12$C0C0yi8EZyA.1lnHmAlxieIgzryKi716S7sbI95y/2swFYxJEV7lC', 'abhinarasimhareddy@gmail.com', 'student', '3', '2026-03-12 09:06:25', '2026-03-12 09:06:25'),
(66, 'user', '$2y$12$xHPNG28GbsxSVND3V6nKzOuhNtN3Rfzf4gF1LPZJ98eXSJhouBy/.', 'user@gmail.com', 'hod', '', '2026-03-12 09:16:11', '2026-03-12 09:50:47'),
(67, 'Priya', '$2y$12$DKwoaIm.85kXlgxONc8dcuH1I.7Nk61.rEXPZDcNaV3ojBYcdeNAa', 'priya@gmail.com', 'student', '3', '2026-03-19 10:39:19', '2026-03-19 10:39:19'),
(68, 'Vetriivel', '$2y$12$d7gCp6oS2vUjYLUla.X5d.8BiavHeFZ.a1M4gaLRzdEnejaRx8m3O', 'vetriivel.pm.2027@gmail.com', 'student', '3', '2026-03-19 10:44:23', '2026-03-19 10:44:23'),
(69, 'ESWARAPANDI', '$2y$12$iu8pLjjpzXd00FsBeVbAK.R70CxthaCrR6Ctuefrct.uOJYhOeugS', 'eswarapandi.m.2027@rkmshome.org', 'student', '3', '2026-03-19 11:06:22', '2026-03-19 11:06:22'),
(73, 'hod@dce', '$2y$12$ZX3V6ZjC03GzsJ3ixa1m9uadISmfOmWtdb.T0kdt3zcH2B.3GmeGG', 'hoddce@rkmshome.org', 'hod', 'DCE', '2026-03-20 08:10:08', '2026-03-20 08:11:13'),
(74, 'Secretary', '$2y$12$rFg85PSueJDyoC47DlqGtOkIUDqbPDYWcAw91iLFXi5FWYuCC8Nk6', 'Secretary@rkmshome.org', 'secretary', '', '2026-03-20 09:05:16', '2026-03-20 09:05:58'),
(84, 'arun', '$2y$12$mbn0dlxMXtsE8tqAvN8bhebtkQbi18N.vUW4ISAhOKhOSEo8kYZJ2', 'gokul.sk.2027@rkmshome.org', 'student', NULL, '2026-03-20 10:38:23', '2026-04-02 10:04:40'),
(85, 'yuvaraj', '$2y$10$Cz.7ObcUHaDgzAAKtEJVOuuuWZRw4X3yDZob5XraPsUYpN9/n1Yme', 'yuvaraj.c.2027@rkmshome.org', 'student', NULL, '2026-03-20 10:49:17', '2026-03-20 10:49:17'),
(86, 'xyz', '$2y$12$h83OG/T9l8bM4h/u7kdqc.aqy.C5oYSdY9SA9vmMajBGTfMLCggWC', 'zye@gmail.com', 'student', '2', '2026-04-02 10:01:48', '2026-04-02 10:01:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`attendance_date`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dept_code` (`dept_code`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `profile_edit_requests`
--
ALTER TABLE `profile_edit_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`student_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by_hod` (`approved_by_hod`),
  ADD KEY `approved_by_admin` (`approved_by_admin`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `responded_by` (`responded_by`);

--
-- Indexes for table `sent_emails`
--
ALTER TABLE `sent_emails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `sports`
--
ALTER TABLE `sports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `student_activities`
--
ALTER TABLE `student_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- Indexes for table `student_sports`
--
ALTER TABLE `student_sports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `sport_id` (`sport_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_schedules`
--
ALTER TABLE `class_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `profile_edit_requests`
--
ALTER TABLE `profile_edit_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sent_emails`
--
ALTER TABLE `sent_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sports`
--
ALTER TABLE `sports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `student_activities`
--
ALTER TABLE `student_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `student_sports`
--
ALTER TABLE `student_sports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD CONSTRAINT `class_schedules_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `profile_edit_requests`
--
ALTER TABLE `profile_edit_requests`
  ADD CONSTRAINT `profile_edit_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profile_edit_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profile_edit_requests_ibfk_3` FOREIGN KEY (`approved_by_hod`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `profile_edit_requests_ibfk_4` FOREIGN KEY (`approved_by_admin`) REFERENCES `users` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sent_emails`
--
ALTER TABLE `sent_emails`
  ADD CONSTRAINT `sent_emails_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `student_activities`
--
ALTER TABLE `student_activities`
  ADD CONSTRAINT `student_activities_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_activities_ibfk_2` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`);

--
-- Constraints for table `student_sports`
--
ALTER TABLE `student_sports`
  ADD CONSTRAINT `student_sports_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_sports_ibfk_2` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
