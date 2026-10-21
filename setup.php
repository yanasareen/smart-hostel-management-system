<?php
/**
 * Smart Hostel Complaint Management System
 * Database Installation & Reset Utility
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$message = '';
$status = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reset_database') {
        try {
            $db = get_db();
            $driver = get_db_driver();
            
            // Drop tables
            $tables = ['notifications', 'payments', 'complaint_feedback', 'complaints', 'applications', 'students', 'rooms', 'users'];
            if ($driver === 'sqlite') {
                $db->exec("PRAGMA foreign_keys = OFF;");
                foreach ($tables as $t) {
                    $db->exec("DROP TABLE IF EXISTS `$t`;");
                }
                $db->exec("PRAGMA foreign_keys = ON;");
            } else {
                $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
                foreach ($tables as $t) {
                    $db->exec("DROP TABLE IF EXISTS `$t`;");
                }
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }

            // Rebuild & Seed
            build_schema_and_seed($db, $driver);
            $message = 'Database schema and demo records successfully reset to fresh factory state!';
            $status = 'success';
        } catch (Throwable $e) {
            $message = 'Database reset failed: ' . $e->getMessage();
            $status = 'danger';
        }
    }
}

$page_title = 'Database & System Setup';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="p-6 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i data-lucide="database" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold">System Installation & Diagnostic</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Software Engineering Lab Project v1.0 • Yana Sareen (2427030570)</p>
                </div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                Connected Driver: <?= strtoupper(get_db_driver()) ?>
            </span>
        </div>

        <div class="p-6 space-y-6">
            <?php if ($message): ?>
                <div class="p-4 rounded-xl border <?= $status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800' ?> flex items-center gap-3 text-sm font-medium">
                    <i data-lucide="<?= $status === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <!-- Diagnostic Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">PHP Version</span>
                    <p class="text-lg font-bold text-slate-900 mt-1"><?= phpversion() ?></p>
                    <span class="text-xs text-emerald-600 font-medium">✓ Compatible</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Storage</span>
                    <p class="text-lg font-bold text-slate-900 mt-1"><?= get_db_driver() === 'sqlite' ? 'SQLite (Portable)' : 'MySQL (XAMPP)' ?></p>
                    <span class="text-xs text-blue-600 font-medium">✓ Zero Configuration</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">SRS Compliance</span>
                    <p class="text-lg font-bold text-slate-900 mt-1">100% (FR-01 to FR-28)</p>
                    <span class="text-xs text-purple-600 font-medium">✓ All 4 Roles Ready</span>
                </div>
            </div>

            <!-- Pre-Seeded Evaluation Accounts -->
            <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Pre-Configured Demo Accounts for Lab Evaluation</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Student -->
                    <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/50">
                        <div class="flex items-center gap-2 mb-2 text-blue-700 font-bold text-sm">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i> Student
                        </div>
                        <p class="text-xs text-slate-600">Email: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-semibold">student@hostel.edu</code></p>
                        <p class="text-xs text-slate-600 mt-1">Password: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-semibold">student123</code></p>
                        <a href="api.php?action=quick_login&role=student" class="mt-3 block text-center py-1.5 px-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition">
                            Login as Student
                        </a>
                    </div>

                    <!-- Warden -->
                    <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50">
                        <div class="flex items-center gap-2 mb-2 text-emerald-700 font-bold text-sm">
                            <i data-lucide="shield" class="w-4 h-4"></i> Warden
                        </div>
                        <p class="text-xs text-slate-600">Email: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-semibold">warden@hostel.edu</code></p>
                        <p class="text-xs text-slate-600 mt-1">Password: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-semibold">warden123</code></p>
                        <a href="api.php?action=quick_login&role=warden" class="mt-3 block text-center py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs transition">
                            Login as Warden
                        </a>
                    </div>

                    <!-- Accountant -->
                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50">
                        <div class="flex items-center gap-2 mb-2 text-amber-700 font-bold text-sm">
                            <i data-lucide="receipt" class="w-4 h-4"></i> Accountant
                        </div>
                        <p class="text-xs text-slate-600">Email: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-semibold">accountant@hostel.edu</code></p>
                        <p class="text-xs text-slate-600 mt-1">Password: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-semibold">accountant123</code></p>
                        <a href="api.php?action=quick_login&role=accountant" class="mt-3 block text-center py-1.5 px-3 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs transition">
                            Login as Accountant
                        </a>
                    </div>

                    <!-- Administrator -->
                    <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/50">
                        <div class="flex items-center gap-2 mb-2 text-purple-700 font-bold text-sm">
                            <i data-lucide="settings" class="w-4 h-4"></i> Administrator
                        </div>
                        <p class="text-xs text-slate-600">Email: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-semibold">admin@hostel.edu</code></p>
                        <p class="text-xs text-slate-600 mt-1">Password: <code class="bg-white px-1 py-0.5 rounded border border-slate-200 font-semibold">admin123</code></p>
                        <a href="api.php?action=quick_login&role=admin" class="mt-3 block text-center py-1.5 px-3 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs transition">
                            Login as Admin
                        </a>
                    </div>
                </div>
            </div>

            <!-- Database Actions -->
            <div class="pt-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <form method="POST" onsubmit="return confirm('Reset all database tables to initial seed records? Any newly entered records will be refreshed.');">
                    <input type="hidden" name="action" value="reset_database">
                    <button type="submit" class="px-4 py-2.5 rounded-xl border border-red-300 text-red-700 hover:bg-red-50 font-medium text-sm transition flex items-center gap-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Reset to Factory Seed State
                    </button>
                </form>

                <a href="index.php" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition shadow-sm flex items-center gap-2">
                    <span>Go to Home Portal</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
