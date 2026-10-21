<?php
/**
 * Smart Hostel Complaint Management System
 * Database Abstraction Layer (XAMPP MySQL with Automatic Socket & SQLite Fallback)
 */

require_once __DIR__ . '/config.php';

function get_db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $db_type = 'sqlite';

    // 1. Try XAMPP macOS MySQL socket first (standard for local XAMPP Apache)
    $socket_path = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
    if (file_exists($socket_path)) {
        try {
            $pdo = new PDO("mysql:unix_socket=$socket_path;dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $db_type = 'mysql';
            return $pdo;
        } catch (Throwable $e) {
            // Continue to TCP attempt
        }
    }

    // 2. Try TCP connection to MySQL (127.0.0.1:3306)
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $db_type = 'mysql';
        return $pdo;
    } catch (Throwable $e) {
        // Fallback to SQLite if MySQL is not reachable
        $sqlite_file = __DIR__ . '/smart_hostel.sqlite';
        $pdo = new PDO("sqlite:" . $sqlite_file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        $db_type = 'sqlite';
    }

    // Initialize tables if needed
    initialize_database_if_needed($pdo, $db_type);

    return $pdo;
}

function get_db_driver() {
    $db = get_db();
    return $db->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function initialize_database_if_needed($pdo, $db_type) {
    try {
        $check = $pdo->query("SELECT 1 FROM users LIMIT 1");
    } catch (Throwable $e) {
        build_schema_and_seed($pdo, $db_type);
    }
}

function build_schema_and_seed($pdo, $db_type) {
    $is_sqlite = ($db_type === 'sqlite');
    $auto_inc = $is_sqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $timestamp_def = "DATETIME DEFAULT CURRENT_TIMESTAMP";

    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id $auto_inc,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'student',
            phone VARCHAR(20),
            reg_no VARCHAR(50),
            avatar VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
            created_at $timestamp_def
        );",

        "CREATE TABLE IF NOT EXISTS rooms (
            id $auto_inc,
            room_number VARCHAR(20) NOT NULL UNIQUE,
            block VARCHAR(50) NOT NULL,
            floor INT NOT NULL DEFAULT 1,
            room_type VARCHAR(50) NOT NULL DEFAULT 'Double (Non-AC)',
            capacity INT NOT NULL DEFAULT 2,
            occupied_beds INT NOT NULL DEFAULT 0,
            fee_per_semester DECIMAL(10,2) NOT NULL DEFAULT 35000.00,
            status VARCHAR(30) NOT NULL DEFAULT 'Available',
            amenities TEXT,
            created_at $timestamp_def
        );",

        "CREATE TABLE IF NOT EXISTS students (
            id $auto_inc,
            user_id INT NOT NULL,
            reg_no VARCHAR(50) NOT NULL UNIQUE,
            room_id INT DEFAULT NULL,
            department VARCHAR(100) NOT NULL,
            year VARCHAR(20) NOT NULL DEFAULT '2nd Year',
            gender VARCHAR(20) NOT NULL DEFAULT 'Female',
            emergency_contact VARCHAR(100),
            check_in_date DATE DEFAULT NULL,
            check_out_date DATE DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'Active Resident',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
        );",

        "CREATE TABLE IF NOT EXISTS applications (
            id $auto_inc,
            student_id INT NOT NULL,
            preferred_room_type VARCHAR(50) NOT NULL,
            preferred_block VARCHAR(50) NOT NULL,
            dietary_preference VARCHAR(50) DEFAULT 'Vegetarian',
            remarks TEXT,
            status VARCHAR(30) NOT NULL DEFAULT 'Pending',
            reviewed_by INT DEFAULT NULL,
            review_notes TEXT,
            allocated_room_id INT DEFAULT NULL,
            created_at $timestamp_def,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS complaints (
            id $auto_inc,
            ticket_id VARCHAR(30) NOT NULL UNIQUE,
            student_id INT NOT NULL,
            room_id INT DEFAULT NULL,
            location_details VARCHAR(100),
            category VARCHAR(50) NOT NULL,
            priority VARCHAR(20) NOT NULL DEFAULT 'Medium',
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'Pending',
            is_escalated INT NOT NULL DEFAULT 0,
            parent_duplicate_id INT DEFAULT NULL,
            assigned_staff_id INT DEFAULT NULL,
            staff_notes TEXT,
            resolved_at DATETIME DEFAULT NULL,
            created_at $timestamp_def,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
        );",

        "CREATE TABLE IF NOT EXISTS complaint_feedback (
            id $auto_inc,
            complaint_id INT NOT NULL UNIQUE,
            student_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at $timestamp_def,
            FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS payments (
            id $auto_inc,
            receipt_number VARCHAR(50) NOT NULL UNIQUE,
            student_id INT NOT NULL,
            fee_type VARCHAR(50) NOT NULL DEFAULT 'Hostel & Mess Fee',
            amount DECIMAL(10,2) NOT NULL,
            semester VARCHAR(50) NOT NULL DEFAULT 'Fall Semester 2026',
            payment_mode VARCHAR(50) NOT NULL DEFAULT 'Bank Transfer',
            transaction_ref VARCHAR(100),
            status VARCHAR(30) NOT NULL DEFAULT 'Paid',
            recorded_by INT DEFAULT NULL,
            remarks TEXT,
            created_at $timestamp_def,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        );",

        "CREATE TABLE IF NOT EXISTS notifications (
            id $auto_inc,
            user_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(30) NOT NULL DEFAULT 'info',
            link VARCHAR(255) DEFAULT NULL,
            is_read INT NOT NULL DEFAULT 0,
            created_at $timestamp_def,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
    }

    seed_database_data($pdo);
}

function seed_database_data($pdo) {
    // 1. Seed Users
    $users = [
        ['System Administrator', 'admin@hostel.edu', password_hash('admin123', PASSWORD_BCRYPT), 'admin', '+91 98765 43210', 'ADM-001'],
        ['Dr. R. K. Sharma (Chief Warden)', 'warden@hostel.edu', password_hash('warden123', PASSWORD_BCRYPT), 'warden', '+91 98765 43211', 'WRD-101'],
        ['Mr. Alok Verma (Accounts Officer)', 'accountant@hostel.edu', password_hash('accountant123', PASSWORD_BCRYPT), 'accountant', '+91 98765 43212', 'ACC-201'],
        ['Yana Sareen', 'student@hostel.edu', password_hash('student123', PASSWORD_BCRYPT), 'student', '+91 98112 34567', '2427030570'],
        ['Rohan Mehta', 'rohan@hostel.edu', password_hash('student123', PASSWORD_BCRYPT), 'student', '+91 98223 45678', '2427030588'],
        ['Priya Nair', 'priya@hostel.edu', password_hash('student123', PASSWORD_BCRYPT), 'student', '+91 98334 56789', '2427030592']
    ];
    $stmt_user = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, reg_no) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $stmt_user->execute($u);
    }

    // 2. Seed Rooms
    $rooms = [
        ['101', 'Block A (Aryabhata)', 1, 'Single (AC)', 1, 1, 45000.00, 'Full', 'Air Conditioner, Study Table, Attached Bath, High-Speed Wi-Fi'],
        ['102', 'Block A (Aryabhata)', 1, 'Double (AC)', 2, 1, 38000.00, 'Available', 'Air Conditioner, Twin Desks, Attached Bath, Balcony'],
        ['201', 'Block A (Aryabhata)', 2, 'Double (Non-AC)', 2, 2, 30000.00, 'Full', 'Ceiling Fan, Twin Desks, Large Wardrobe'],
        ['202', 'Block A (Aryabhata)', 2, 'Triple (Non-AC)', 3, 1, 25000.00, 'Available', 'Three Beds, Personal Desks, High-Speed LAN'],
        ['301', 'Block B (Bhaskara)', 3, 'Single (AC)', 1, 0, 45000.00, 'Available', 'Air Conditioner, Private Balcony, Attached Bath'],
        ['302', 'Block B (Bhaskara)', 3, 'Double (AC)', 2, 0, 38000.00, 'Available', 'Air Conditioner, Study Lamp, Attached Washroom'],
        ['105', 'Block C (Chanakya)', 1, 'Double (Non-AC)', 2, 0, 28000.00, 'Under Maintenance', 'Renovation of plumbing lines and painting']
    ];
    $stmt_room = $pdo->prepare("INSERT INTO rooms (room_number, block, floor, room_type, capacity, occupied_beds, fee_per_semester, status, amenities) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($rooms as $r) {
        $stmt_room->execute($r);
    }

    // 3. Seed Students
    $students = [
        [4, '2427030570', 2, 'Computer Science & Engineering', '3rd Year', 'Female', 'Mr. Sareen (+91 98111 00000)', '2026-08-01', NULL, 'Active Resident'],
        [5, '2427030588', 3, 'Information Technology', '2nd Year', 'Male', 'Mr. Mehta (+91 98222 00000)', '2026-08-02', NULL, 'Active Resident'],
        [6, '2427030592', 3, 'Electronics & Comm.', '2nd Year', 'Female', 'Mrs. Nair (+91 98333 00000)', '2026-08-03', NULL, 'Active Resident']
    ];
    $stmt_student = $pdo->prepare("INSERT INTO students (user_id, reg_no, room_id, department, year, gender, emergency_contact, check_in_date, check_out_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($students as $s) {
        $stmt_student->execute($s);
    }

    // 4. Seed Applications
    $stmt_app = $pdo->prepare("INSERT INTO applications (student_id, preferred_room_type, preferred_block, dietary_preference, remarks, status, reviewed_by, review_notes, allocated_room_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_app->execute([1, 'Double (AC)', 'Block A (Aryabhata)', 'Vegetarian', 'Requesting room near library wing.', 'Approved', 2, 'Approved and assigned Room 102 as requested.', 2, '2026-07-28 10:15:00']);

    // 5. Seed Complaints
    $complaints = [
        ['CMP-2026-001', 1, 2, 'Room 102, Block A', 'Wi-Fi', 'High', 'Wi-Fi router on 1st floor drops connection frequently', 'The 5GHz signal frequently disconnects during evening study hours (7 PM to 10 PM).', 'In Progress', 0, NULL, 2, 'Technician visited; replaced secondary repeater.', NULL, '2026-10-21 14:30:00'],
        ['CMP-2026-002', 1, 2, 'Room 102, Block A', 'Wi-Fi', 'Medium', 'Internet connectivity slow in 1st floor corridor', 'Repeated issue with Wi-Fi speed dropping below 2 Mbps.', 'In Progress', 0, 1, 2, 'Linked as duplicate to CMP-2026-001.', NULL, '2026-10-21 16:45:00'],
        ['CMP-2026-003', 1, 2, 'Room 102, Washroom', 'Plumbing', 'Medium', 'Bathroom sink tap continuously dripping water', 'The cold water tap washer has worn out and drips continuously.', 'Resolved', 0, NULL, 2, 'Plumber replaced the ceramic washer. Completely leak-free.', '2026-10-20 17:00:00', '2026-10-19 09:30:00'],
        ['CMP-2026-004', 2, 3, 'Room 201, Block A', 'Electricity', 'High', 'Main ceiling switchboard sparking when turning on fan', 'Sparks were seen and a burning plastic smell was noticed near switch 2. Urgent attention needed.', 'Pending', 1, NULL, NULL, 'Escalated to Chief Warden due to safety concern and SLA breach.', NULL, '2026-10-20 08:00:00']
    ];
    $stmt_cmp = $pdo->prepare("INSERT INTO complaints (ticket_id, student_id, room_id, location_details, category, priority, title, description, status, is_escalated, parent_duplicate_id, assigned_staff_id, staff_notes, resolved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($complaints as $c) {
        $stmt_cmp->execute($c);
    }

    // 6. Seed Feedback
    $stmt_fb = $pdo->prepare("INSERT INTO complaint_feedback (complaint_id, student_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt_fb->execute([3, 1, 5, 'Plumber arrived promptly within 3 hours and fixed the tap cleanly. Very satisfied with the quick turnaround!', '2026-10-20 18:30:00']);

    // 7. Seed Payments
    $payments = [
        ['RCP-2026-78901', 1, 'Hostel & Mess Fee', 38000.00, 'Fall Semester 2026', 'Bank Transfer', 'NEFT-AXIS-98234120', 'Paid', 3, 'Full semester room fee for Room 102 + Mess charges.', '2026-08-05 11:30:00'],
        ['RCP-2026-78902', 2, 'Hostel & Mess Fee', 30000.00, 'Fall Semester 2026', 'UPI Payment', 'UPI-992147812903', 'Paid', 3, 'Semester fee for Room 201.', '2026-08-06 14:10:00']
    ];
    $stmt_pay = $pdo->prepare("INSERT INTO payments (receipt_number, student_id, fee_type, amount, semester, payment_mode, transaction_ref, status, recorded_by, remarks, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($payments as $p) {
        $stmt_pay->execute($p);
    }

    // 8. Seed Notifications
    $notifications = [
        [4, 'Complaint Status Updated', 'Your complaint #CMP-2026-001 (Wi-Fi) status has been updated to In Progress.', 'info', 'my_complaints.php', 0, '2026-10-21 15:00:00'],
        [4, 'Fee Receipt Issued', 'Official Receipt #RCP-2026-78901 for ₹38,000 has been generated and is available for download.', 'success', 'my_fees.php', 0, '2026-08-05 11:35:00'],
        [4, 'Complaint Resolved', 'Complaint #CMP-2026-003 (Plumbing) has been marked Resolved. Please provide your feedback rating.', 'success', 'my_complaints.php', 1, '2026-10-20 17:05:00'],
        [2, '⚠️ SLA Escalation Alert', 'Ticket #CMP-2026-004 (Room 201 - Electricity) has breached the 24-hour SLA threshold and is now ESCALATED.', 'warning', 'warden_dashboard.php', 0, '2026-10-21 09:00:00']
    ];
    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($notifications as $n) {
        $stmt_notif->execute($n);
    }
}

// Notification Helper
function create_notification($user_id, $title, $message, $type = 'info', $link = null) {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)");
        $stmt->execute([$user_id, $title, $message, $type, $link]);
    } catch (Throwable $e) {}
}

// Check and trigger SLA escalations dynamically (FR-19)
function run_sla_escalation_check() {
    try {
        $db = get_db();
        $driver = get_db_driver();
        if ($driver === 'sqlite') {
            $db->exec("UPDATE complaints 
                       SET is_escalated = 1 
                       WHERE status != 'Resolved' AND is_escalated = 0 AND (
                           (priority = 'High' AND (julianday('now') - julianday(created_at)) * 24 >= 24) OR
                           (priority = 'Medium' AND (julianday('now') - julianday(created_at)) * 24 >= 48) OR
                           (priority = 'Low' AND (julianday('now') - julianday(created_at)) * 24 >= 72)
                       )");
        } else {
            $db->exec("UPDATE complaints 
                       SET is_escalated = 1 
                       WHERE status != 'Resolved' AND is_escalated = 0 AND (
                           (priority = 'High' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= 24) OR
                           (priority = 'Medium' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= 48) OR
                           (priority = 'Low' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= 72)
                       )");
        }
    } catch (Throwable $e) {}
}
