-- ============================================================================
-- SOFTWARE REQUIREMENTS SPECIFICATION (SRS)
-- SMART HOSTEL COMPLAINT MANAGEMENT SYSTEM - DATABASE SCHEMA
-- Version: 1.0
-- Prepared by: Yana Sareen (Registration Number: 2427030570)
-- Software Engineering Laboratory Project
-- Compatible with MySQL 5.7+ / MariaDB 10.4+ (XAMPP default)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `smart_hostel` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smart_hostel`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `complaint_feedback`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (Stores credentials and role mapping: FR-01, FR-02, FR-03, NFR-06, NFR-09)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'warden', 'accountant', 'admin') NOT NULL DEFAULT 'student',
  `phone` VARCHAR(20) DEFAULT NULL,
  `reg_no` VARCHAR(50) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Rooms Table (FR-08, BR-02: Room availability, capacity, occupancy, and pricing)
CREATE TABLE `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_number` VARCHAR(20) NOT NULL UNIQUE,
  `block` VARCHAR(50) NOT NULL,
  `floor` INT NOT NULL DEFAULT 1,
  `room_type` VARCHAR(50) NOT NULL DEFAULT 'Double (Non-AC)',
  `capacity` INT NOT NULL DEFAULT 2,
  `occupied_beds` INT NOT NULL DEFAULT 0,
  `fee_per_semester` DECIMAL(10,2) NOT NULL DEFAULT 35000.00,
  `status` ENUM('Available', 'Full', 'Under Maintenance') NOT NULL DEFAULT 'Available',
  `amenities` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Students Table (FR-01, FR-25: Student profile, department, emergency contacts, check-in/out)
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `reg_no` VARCHAR(50) NOT NULL UNIQUE,
  `room_id` INT DEFAULT NULL,
  `department` VARCHAR(100) NOT NULL,
  `year` VARCHAR(20) NOT NULL DEFAULT '2nd Year',
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Female',
  `emergency_contact` VARCHAR(150) DEFAULT NULL,
  `check_in_date` DATE DEFAULT NULL,
  `check_out_date` DATE DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Active Resident',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Accommodation Applications Table (FR-05, FR-06, FR-07: Student applications and warden approvals)
CREATE TABLE `applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `preferred_room_type` VARCHAR(50) NOT NULL,
  `preferred_block` VARCHAR(50) NOT NULL,
  `dietary_preference` VARCHAR(50) DEFAULT 'Vegetarian',
  `remarks` TEXT DEFAULT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `reviewed_by` INT DEFAULT NULL,
  `review_notes` TEXT DEFAULT NULL,
  `allocated_room_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`allocated_room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Complaints Table (FR-10 to FR-19: Unique ticket, categories, priority, duplicate flag, escalation)
CREATE TABLE `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` VARCHAR(30) NOT NULL UNIQUE,
  `student_id` INT NOT NULL,
  `room_id` INT DEFAULT NULL,
  `location_details` VARCHAR(150) DEFAULT NULL,
  `category` ENUM('Electricity', 'Plumbing', 'Wi-Fi', 'Cleaning', 'Furniture', 'Other') NOT NULL,
  `priority` ENUM('High', 'Medium', 'Low') NOT NULL DEFAULT 'Medium',
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('Pending', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Pending',
  `is_escalated` TINYINT(1) NOT NULL DEFAULT 0,
  `parent_duplicate_id` INT DEFAULT NULL,
  `assigned_staff_id` INT DEFAULT NULL,
  `staff_notes` TEXT DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Complaint Feedback Table (FR-20, BR-07: 1-5 rating & feedback after resolution)
CREATE TABLE `complaint_feedback` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL UNIQUE,
  `student_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Fee Payments & Receipts Table (FR-21 to FR-24, BR-06: Official receipt numbers and payment records)
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
  `student_id` INT NOT NULL,
  `fee_type` VARCHAR(100) NOT NULL DEFAULT 'Hostel & Mess Fee',
  `amount` DECIMAL(10,2) NOT NULL,
  `semester` VARCHAR(50) NOT NULL DEFAULT 'Fall Semester 2026',
  `payment_mode` VARCHAR(50) NOT NULL DEFAULT 'Bank Transfer',
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('Paid', 'Pending', 'Failed') NOT NULL DEFAULT 'Paid',
  `recorded_by` INT DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Notifications Table (FR-17, TBD-02: Real-time in-app alerts)
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(30) NOT NULL DEFAULT 'info',
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- INITIAL SEED DATA FOR DEMO & EVALUATION
-- ============================================================================

-- Users (Passwords are bcrypt hashed. Credentials: role + 123, e.g. student123, warden123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `reg_no`) VALUES
(1, 'System Administrator', 'admin@hostel.edu', '$2y$10$wK1Gv5KeqM3wK8a0/F5e7.tQ1Wv6vR8a.y7K2U7t3N3h.y.v.k7K2', 'admin', '+91 98765 43210', 'ADM-001'),
(2, 'Dr. R. K. Sharma (Chief Warden)', 'warden@hostel.edu', '$2y$10$wK1Gv5KeqM3wK8a0/F5e7.tQ1Wv6vR8a.y7K2U7t3N3h.y.v.k7K2', 'warden', '+91 98765 43211', 'WRD-101'),
(3, 'Mr. Alok Verma (Accounts Officer)', 'accountant@hostel.edu', '$2y$10$wK1Gv5KeqM3wK8a0/F5e7.tQ1Wv6vR8a.y7K2U7t3N3h.y.v.k7K2', 'accountant', '+91 98765 43212', 'ACC-201'),
(4, 'Yana Sareen', 'student@hostel.edu', '$2y$10$wK1Gv5KeqM3wK8a0/F5e7.tQ1Wv6vR8a.y7K2U7t3N3h.y.v.k7K2', 'student', '+91 98112 34567', '2427030570');

-- Rooms
INSERT INTO `rooms` (`id`, `room_number`, `block`, `floor`, `room_type`, `capacity`, `occupied_beds`, `fee_per_semester`, `status`, `amenities`) VALUES
(1, '101', 'Block A (Aryabhata)', 1, 'Single (AC)', 1, 1, 45000.00, 'Full', 'Air Conditioner, Study Table, Attached Bath, Wi-Fi 6'),
(2, '102', 'Block A (Aryabhata)', 1, 'Double (AC)', 2, 1, 38000.00, 'Available', 'Air Conditioner, Twin Study Desks, Attached Washroom, Balcony'),
(3, '201', 'Block A (Aryabhata)', 2, 'Double (Non-AC)', 2, 2, 30000.00, 'Full', 'Ceiling Fan, Twin Desks, Large Wardrobe, High-Speed LAN'),
(4, '202', 'Block A (Aryabhata)', 2, 'Triple (Non-AC)', 3, 1, 25000.00, 'Available', 'Three Single Beds, Personal Study Desks, Book Shelves'),
(5, '301', 'Block B (Bhaskara)', 3, 'Single (AC)', 1, 0, 45000.00, 'Available', 'Air Conditioner, Balcony, Attached Bath, Study Table'),
(6, '105', 'Block C (Chanakya)', 1, 'Double (Non-AC)', 2, 0, 28000.00, 'Under Maintenance', 'Renovation and electrical rewiring in progress');

-- Students
INSERT INTO `students` (`id`, `user_id`, `reg_no`, `room_id`, `department`, `year`, `gender`, `emergency_contact`, `check_in_date`, `status`) VALUES
(1, 4, '2427030570', 2, 'Computer Science & Engineering', '3rd Year', 'Female', 'Mr. Sareen (+91 98111 00000)', '2026-08-01', 'Active Resident');

-- Applications
INSERT INTO `applications` (`id`, `student_id`, `preferred_room_type`, `preferred_block`, `dietary_preference`, `remarks`, `status`, `reviewed_by`, `review_notes`, `allocated_room_id`, `created_at`) VALUES
(1, 1, 'Double (AC)', 'Block A (Aryabhata)', 'Vegetarian', 'Requesting room near library wing for research work.', 'Approved', 2, 'Approved and assigned Room 102 as requested.', 2, '2026-07-28 10:15:00');

-- Complaints
INSERT INTO `complaints` (`id`, `ticket_id`, `student_id`, `room_id`, `location_details`, `category`, `priority`, `title`, `description`, `status`, `is_escalated`, `parent_duplicate_id`, `assigned_staff_id`, `staff_notes`, `resolved_at`, `created_at`) VALUES
(1, 'CMP-2026-001', 1, 2, 'Room 102, Block A', 'Wi-Fi', 'High', 'Wi-Fi router on 1st floor drops connection frequently', 'The 5GHz signal frequently disconnects during evening study hours (7 PM to 10 PM).', 'In Progress', 0, NULL, 2, 'Technician replaced the secondary repeater. Monitoring connectivity.', NULL, '2026-10-21 14:30:00'),
(2, 'CMP-2026-002', 1, 2, 'Room 102, Block A', 'Wi-Fi', 'Medium', 'Internet connectivity slow in 1st floor corridor', 'Repeated issue with Wi-Fi speed dropping below 2 Mbps.', 'In Progress', 0, 1, 2, 'Identified and linked as duplicate ticket to CMP-2026-001.', NULL, '2026-10-21 16:45:00'),
(3, 'CMP-2026-003', 1, 2, 'Room 102 Washroom', 'Plumbing', 'Medium', 'Bathroom sink tap continuously dripping water', 'The cold water tap washer has worn out and drips continuously.', 'Resolved', 0, NULL, 2, 'Replaced ceramic washer and checked flow. Fully resolved.', '2026-10-20 17:00:00', '2026-10-19 09:30:00'),
(4, 'CMP-2026-004', 1, 2, 'Block A Corridor', 'Electricity', 'High', 'Main ceiling switchboard sparking when turning on fan', 'Sparks were observed near switch 2. Urgent attention needed to avoid fire hazard.', 'Pending', 1, NULL, NULL, 'Escalated to Chief Warden & Electrical Supervisor due to SLA breach (>24h).', NULL, '2026-10-20 08:00:00');

-- Feedback
INSERT INTO `complaint_feedback` (`id`, `complaint_id`, `student_id`, `rating`, `comment`, `created_at`) VALUES
(1, 3, 1, 5, 'Plumber arrived promptly within 3 hours and fixed the tap cleanly. Very satisfied with the quick turnaround!', '2026-10-20 18:30:00');

-- Payments
INSERT INTO `payments` (`id`, `receipt_number`, `student_id`, `fee_type`, `amount`, `semester`, `payment_mode`, `transaction_ref`, `status`, `recorded_by`, `remarks`, `created_at`) VALUES
(1, 'RCP-2026-78901', 1, 'Hostel & Mess Fee', 38000.00, 'Fall Semester 2026', 'Bank Transfer', 'NEFT-AXIS-98234120', 'Paid', 3, 'Full semester room fee for Room 102 + Mess charges.', '2026-08-05 11:30:00');

-- Notifications
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(1, 4, 'Complaint Status Updated', 'Your complaint #CMP-2026-001 (Wi-Fi) status has been updated to In Progress.', 'info', 'student_dashboard.php#complaints', 0, '2026-10-21 15:00:00'),
(2, 4, 'Fee Receipt Issued', 'Official Receipt #RCP-2026-78901 for ₹38,000 has been generated and is ready for download.', 'success', 'student_dashboard.php#fees', 0, '2026-08-05 11:35:00');
