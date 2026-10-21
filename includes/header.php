<?php
/**
 * Smart Hostel Complaint Management System
 * Shared Header Component
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Trigger periodic SLA escalation check (FR-19)
run_sla_escalation_check();

$current_role = current_user_role();
$user = current_user();

// Fetch unread notifications count if logged in
$unread_notifs = 0;
$latest_notifs = [];
if (is_logged_in()) {
    $db = get_db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([current_user_id()]);
    $unread_notifs = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([current_user_id()]);
    $latest_notifs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | ' . APP_SHORT_NAME : APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js for Interactive Bar Graphs & Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="min-h-screen flex flex-col font-sans antialiased text-slate-800">

<!-- SE Lab Demonstration Switcher Bar (Always Available for Quick Evaluation) -->
<div class="demo-bar px-4 py-2 text-xs flex flex-wrap items-center justify-between gap-2 border-b border-slate-700 select-none">
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-500/20 text-blue-300 border border-blue-500/30">
            SE Lab Mode
        </span>
        <span class="text-slate-300">
            Current: <strong class="text-white uppercase"><?= $current_role ? $current_role : 'Guest' ?></strong>
            <?= $user ? '(' . htmlspecialchars($user['name']) . ')' : '' ?>
        </span>
    </div>
    <div class="flex items-center gap-1.5 flex-wrap">
        <span class="text-slate-400 mr-1">Switch Role:</span>
        <a href="api.php?action=quick_login&role=student" class="px-2 py-1 rounded bg-slate-800 hover:bg-blue-600 transition text-slate-200 hover:text-white flex items-center gap-1 <?= $current_role === 'student' ? 'ring-1 ring-blue-400 bg-blue-900/50' : '' ?>">
            <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i> Student (Yana)
        </a>
        <a href="api.php?action=quick_login&role=warden" class="px-2 py-1 rounded bg-slate-800 hover:bg-emerald-600 transition text-slate-200 hover:text-white flex items-center gap-1 <?= $current_role === 'warden' ? 'ring-1 ring-emerald-400 bg-emerald-900/50' : '' ?>">
            <i data-lucide="shield" class="w-3.5 h-3.5"></i> Warden
        </a>
        <a href="api.php?action=quick_login&role=accountant" class="px-2 py-1 rounded bg-slate-800 hover:bg-amber-600 transition text-slate-200 hover:text-white flex items-center gap-1 <?= $current_role === 'accountant' ? 'ring-1 ring-amber-400 bg-amber-900/50' : '' ?>">
            <i data-lucide="receipt" class="w-3.5 h-3.5"></i> Accountant
        </a>
        <a href="api.php?action=quick_login&role=admin" class="px-2 py-1 rounded bg-slate-800 hover:bg-purple-600 transition text-slate-200 hover:text-white flex items-center gap-1 <?= $current_role === 'admin' ? 'ring-1 ring-purple-400 bg-purple-900/50' : '' ?>">
            <i data-lucide="settings" class="w-3.5 h-3.5"></i> Admin
        </a>
        <a href="setup.php" class="ml-2 px-2 py-1 rounded bg-slate-700 hover:bg-slate-600 text-slate-300 transition text-[11px]" title="Reset database to fresh seed state">
            <i data-lucide="refresh-cw" class="w-3 h-3 inline"></i> Reset DB
        </a>
    </div>
</div>
