<?php
/**
 * Smart Hostel Complaint Management System
 * Warden - Check-In & Check-Out Register (FR-09)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role(['warden', 'admin']);

$db = get_db();

// Handle Check-in & Check-out actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_checkin') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $checkin_date = $_POST['check_in_date'] ?? date('Y-m-d');
        if ($student_id > 0) {
            $stmt = $db->prepare("UPDATE students SET check_in_date = ?, status = 'Active Resident' WHERE id = ?");
            $stmt->execute([$checkin_date, $student_id]);
            set_flash('success', 'Student check-in recorded successfully.');
        }
        header('Location: warden_checkin.php');
        exit;
    }

    if ($action === 'record_checkout') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $checkout_date = $_POST['check_out_date'] ?? date('Y-m-d');
        if ($student_id > 0) {
            $stmt = $db->prepare("SELECT room_id FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $r_id = $stmt->fetchColumn();

            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE students SET check_out_date = ?, status = 'Checked Out', room_id = NULL WHERE id = ?");
            $stmt->execute([$checkout_date, $student_id]);

            if ($r_id) {
                $stmt = $db->prepare("UPDATE rooms SET occupied_beds = MAX(0, occupied_beds - 1), status = 'Available' WHERE id = ?");
                $stmt->execute([$r_id]);
            }
            $db->commit();
            set_flash('success', 'Student check-out recorded and room bed has been returned to available inventory.');
        }
        header('Location: warden_checkin.php');
        exit;
    }
}

// Fetch all residents
$residents = $db->query("SELECT s.*, u.name as student_name, u.email, u.phone, r.room_number, r.block 
                         FROM students s 
                         JOIN users u ON s.user_id = u.id 
                         LEFT JOIN rooms r ON s.room_id = r.id 
                         ORDER BY s.id DESC")->fetchAll();

$page_title = 'Check-In & Check-Out';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-emerald-800 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 mb-2">
                <i data-lucide="log-in" class="w-3.5 h-3.5"></i> Resident Arrival & Clearance (FR-09)
            </div>
            <h1 class="text-2xl font-black">Check-In & Check-Out Register</h1>
            <p class="text-xs sm:text-sm text-emerald-200 mt-1">Record campus arrival dates and process clearance on student departure.</p>
        </div>
        <span class="px-3.5 py-1.5 rounded-xl font-bold text-xs bg-white/10 text-emerald-200 border border-white/20">
            <?= count($residents) ?> Enrolled Residents
        </span>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base">Resident Register</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Student</th>
                        <th class="px-6 py-3.5">Room</th>
                        <th class="px-6 py-3.5">Check-in Date</th>
                        <th class="px-6 py-3.5">Check-out Date</th>
                        <th class="px-6 py-3.5">Resident Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($residents as $res): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900"><?= htmlspecialchars($res['student_name']) ?></p>
                                <p class="text-[11px] text-slate-500"><?= htmlspecialchars($res['reg_no']) ?> • <?= htmlspecialchars($res['department']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?= $res['room_number'] ? 'Room ' . htmlspecialchars($res['room_number']) . ' (' . htmlspecialchars($res['block']) . ')' : '<span class="text-slate-400">Not Allocated</span>' ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= $res['check_in_date'] ? date('d M Y', strtotime($res['check_in_date'])) : '<span class="text-amber-600 font-semibold">Not checked in</span>' ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= $res['check_out_date'] ? date('d M Y', strtotime($res['check_out_date'])) : '<span class="text-slate-400">Active</span>' ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge-status <?= $res['status'] === 'Active Resident' ? 'badge-resolved' : ($res['status'] === 'Checked Out' ? 'badge-low' : 'badge-pending') ?>">
                                    <?= htmlspecialchars($res['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <?php if (!$res['check_in_date'] && $res['room_number']): ?>
                                    <form method="POST" action="warden_checkin.php" class="inline">
                                        <input type="hidden" name="action" value="record_checkin">
                                        <input type="hidden" name="student_id" value="<?= $res['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition">
                                            Record Check-In
                                        </button>
                                    </form>
                                <?php elseif ($res['check_in_date'] && !$res['check_out_date']): ?>
                                    <form method="POST" action="warden_checkin.php" onsubmit="return confirm('Clear and check out student? This will release their allocated room bed.');" class="inline">
                                        <input type="hidden" name="action" value="record_checkout">
                                        <input type="hidden" name="student_id" value="<?= $res['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-red-100 text-slate-700 hover:text-red-700 font-semibold transition">
                                            Process Check-Out
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
