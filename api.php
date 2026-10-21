<?php
/**
 * Smart Hostel Complaint Management System
 * API Endpoint for Dynamic AJAX Features & Quick Demo Switcher
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

$db = get_db();

switch ($action) {
    case 'check_duplicate':
        // FR-18: Identify potentially duplicate complaints about the same issue and location
        $category = trim($_GET['category'] ?? '');
        $room_id = intval($_GET['room_id'] ?? 0);
        $location = trim($_GET['location'] ?? '');

        if (empty($category)) {
            echo json_encode(['duplicate_found' => false]);
            exit;
        }

        // Look for active complaints in same room/location and category
        if ($room_id > 0) {
            $stmt = $db->prepare("SELECT id, ticket_id, category, priority, title, status, location_details, created_at 
                                  FROM complaints 
                                  WHERE category = ? AND room_id = ? AND status != 'Resolved' 
                                  ORDER BY id DESC LIMIT 1");
            $stmt->execute([$category, $room_id]);
            $ticket = $stmt->fetch();
        } else if (!empty($location)) {
            $stmt = $db->prepare("SELECT id, ticket_id, category, priority, title, status, location_details, created_at 
                                  FROM complaints 
                                  WHERE category = ? AND location_details LIKE ? AND status != 'Resolved' 
                                  ORDER BY id DESC LIMIT 1");
            $stmt->execute([$category, '%' . $location . '%']);
            $ticket = $stmt->fetch();
        } else {
            $ticket = false;
        }

        if ($ticket) {
            echo json_encode([
                'duplicate_found' => true,
                'ticket' => $ticket
            ]);
        } else {
            echo json_encode(['duplicate_found' => false]);
        }
        exit;

    case 'mark_notification_read':
        $notif_id = intval($_GET['id'] ?? 0);
        $user_id = current_user_id();
        if ($notif_id > 0 && $user_id) {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notif_id, $user_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;

    case 'quick_login':
        // Demo Role Switcher helper
        $role = $_GET['role'] ?? '';
        $allowed = ['student', 'warden', 'accountant', 'admin'];
        if (in_array($role, $allowed)) {
            $stmt = $db->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
            $stmt->execute([$role]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user;
                
                $redirects = [
                    'student' => 'student_dashboard.php',
                    'warden' => 'warden_dashboard.php',
                    'accountant' => 'accountant_dashboard.php',
                    'admin' => 'admin_dashboard.php'
                ];
                
                header('Location: ' . $redirects[$role]);
                exit;
            }
        }
        header('Location: index.php');
        exit;

    default:
        echo json_encode(['error' => 'Invalid action']);
        exit;
}
