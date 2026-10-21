<?php
/**
 * Smart Hostel Complaint Management System
 * Software Engineering Laboratory Project
 * Prepared by: Yana Sareen (Reg: 2427030570)
 * Version: 1.0
 */

// Ensure session save path is writable
$default_sess_path = session_save_path();
if (empty($default_sess_path) || !is_writable($default_sess_path)) {
    $alt_path = sys_get_temp_dir();
    if (is_writable($alt_path)) {
        session_save_path($alt_path);
    } else {
        $local_sess = __DIR__ . '/.sessions';
        if (!is_dir($local_sess)) {
            @mkdir($local_sess, 0777, true);
        }
        if (is_dir($local_sess) && is_writable($local_sess)) {
            session_save_path($local_sess);
        }
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// System configuration
define('APP_NAME', 'Smart Hostel Complaint Management System');
define('APP_SHORT_NAME', 'SmartHostel');
define('APP_VERSION', '1.0');
define('HOSTEL_NAME', 'Apex University Smart Residence');
define('HOSTEL_ADDRESS', 'Academic Campus, Knowledge Park, Pin: 110044');
define('CURRENCY_SYMBOL', '₹');

// Database configuration (Standard XAMPP default)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_hostel');
define('DB_USER', 'root');
define('DB_PASS', '');

// Complaint SLA escalation thresholds in hours (TBD-01 in SRS)
define('ESCALATION_HIGH_HOURS', 24);    // High priority escalates after 24 hours
define('ESCALATION_MEDIUM_HOURS', 48);  // Medium priority escalates after 48 hours
define('ESCALATION_LOW_HOURS', 72);     // Low priority escalates after 72 hours

// Flash message helpers
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Authentication helpers
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_role() {
    return $_SESSION['user']['role'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('danger', 'Please log in to access this page.');
        header('Location: login.php');
        exit;
    }
}

function require_role($allowed_roles) {
    require_login();
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    $role = current_user_role();
    if (!in_array($role, $allowed_roles)) {
        set_flash('danger', 'Access denied. You do not have permission to access that section.');
        // Redirect to appropriate dashboard
        switch ($role) {
            case 'warden':
                header('Location: warden_dashboard.php');
                break;
            case 'accountant':
                header('Location: accountant_dashboard.php');
                break;
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
            default:
                header('Location: student_dashboard.php');
                break;
        }
        exit;
    }
}

// CSRF Protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input sanitization
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

// Time formatting helper
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y, h:i A', $time);
}
