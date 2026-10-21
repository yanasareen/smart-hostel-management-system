<?php
/**
 * Smart Hostel Complaint Management System
 * Warden - Accommodation Applications Review (FR-06)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role(['warden', 'admin']);

$db = get_db();
$warden_user = current_user();

// Handle POST: Review Application (FR-06)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review_application') {
    $app_id = intval($_POST['application_id'] ?? 0);
    $decision = $_POST['decision'] ?? 'Approved';
    $notes = trim($_POST['review_notes'] ?? '');

    if ($app_id > 0 && in_array($decision, ['Approved', 'Rejected'])) {
        $stmt = $db->prepare("UPDATE applications SET status = ?, reviewed_by = ?, review_notes = ? WHERE id = ?");
        $stmt->execute([$decision, $warden_user['id'], $notes, $app_id]);

        // Notify Student
        $stmt = $db->prepare("SELECT s.user_id, s.reg_no FROM applications a JOIN students s ON a.student_id = s.id WHERE a.id = ?");
        $stmt->execute([$app_id]);
        $st = $stmt->fetch();
        if ($st) {
            $type = ($decision === 'Approved') ? 'success' : 'danger';
            create_notification($st['user_id'], "Accommodation Application $decision", "Warden review completed: $notes", $type, 'apply_room.php');
        }

        set_flash('success', "Accommodation application #$app_id has been marked as $decision.");
    }
    header('Location: warden_applications.php');
    exit;
}

// Fetch all applications
$stmt = $db->query("SELECT a.*, s.reg_no, s.department, s.year, s.gender, s.emergency_contact, u.name as student_name, u.phone as student_phone, r.room_number as allocated_room_num
                    FROM applications a
                    JOIN students s ON a.student_id = s.id
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN rooms r ON a.allocated_room_id = r.id
                    ORDER BY a.id DESC");
$all_applications = $stmt->fetchAll();

$page_title = 'Review Applications';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-emerald-800 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 mb-2">
                <i data-lucide="file-check" class="w-3.5 h-3.5"></i> Warden Operations (FR-06)
            </div>
            <h1 class="text-2xl font-black">Accommodation Applications Review</h1>
            <p class="text-xs sm:text-sm text-emerald-200 mt-1">Review student preferences, approve accommodation, or reject with official remarks.</p>
        </div>
        <a href="warden_allocation.php" class="px-4 py-2.5 rounded-xl bg-white text-emerald-900 hover:bg-emerald-50 font-bold text-xs shadow-md transition flex items-center gap-2 flex-shrink-0">
            <i data-lucide="bed" class="w-4 h-4 text-emerald-600"></i> Go to Room Allocation
        </a>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base">Student Application Queue (<?= count($all_applications) ?> Total)</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Student</th>
                        <th class="px-6 py-3.5">Preferences</th>
                        <th class="px-6 py-3.5">Dietary / Remarks</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Warden Review Notes</th>
                        <th class="px-6 py-3.5 text-right">Review Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($all_applications as $app): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900"><?= htmlspecialchars($app['student_name']) ?></p>
                                <p class="text-[11px] text-slate-500"><?= htmlspecialchars($app['reg_no']) ?> • <?= htmlspecialchars($app['department']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($app['student_phone'] ?? '') ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <strong class="text-slate-800"><?= htmlspecialchars($app['preferred_room_type']) ?></strong>
                                <p class="text-[11px] text-slate-500"><?= htmlspecialchars($app['preferred_block']) ?></p>
                            </td>
                            <td class="px-6 py-4 max-w-xs">
                                <span class="font-semibold text-slate-700"><?= htmlspecialchars($app['dietary_preference']) ?></span>
                                <p class="text-[11px] text-slate-500 italic"><?= htmlspecialchars($app['remarks'] ?: 'None') ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge-status <?= $app['status'] === 'Approved' ? 'badge-resolved' : ($app['status'] === 'Rejected' ? 'badge-escalated' : 'badge-pending') ?>">
                                    <?= htmlspecialchars($app['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-700 max-w-xs">
                                <?= htmlspecialchars($app['review_notes'] ?: '—') ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if ($app['status'] === 'Pending'): ?>
                                    <button onclick="openModal('reviewAppModal_<?= $app['id'] ?>')" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition">
                                        Review
                                    </button>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs">Reviewed</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Review Modal -->
                        <div id="reviewAppModal_<?= $app['id'] ?>" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
                            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl modal-content">
                                <div class="flex justify-between items-start border-b border-slate-100 pb-3 mb-4">
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-base">Review Application #<?= $app['id'] ?></h3>
                                        <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($app['student_name']) ?> (<?= htmlspecialchars($app['reg_no']) ?>)</p>
                                    </div>
                                    <button onclick="closeModal('reviewAppModal_<?= $app['id'] ?>')" class="text-slate-400 hover:text-slate-600">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <form method="POST" action="warden_applications.php">
                                    <input type="hidden" name="action" value="review_application">
                                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

                                    <div class="space-y-4 text-xs">
                                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                            <p><strong>Preference:</strong> <?= htmlspecialchars($app['preferred_room_type']) ?> in <?= htmlspecialchars($app['preferred_block']) ?></p>
                                            <p class="mt-1"><strong>Remarks:</strong> <?= htmlspecialchars($app['remarks'] ?: 'None') ?></p>
                                        </div>

                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Decision * (FR-06)</label>
                                            <select name="decision" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 bg-white">
                                                <option value="Approved">Approve Application</option>
                                                <option value="Rejected">Reject Application</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Warden Review Remarks</label>
                                            <textarea name="review_notes" rows="3" required placeholder="Enter justification for the student..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500"></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2">
                                        <button type="button" onclick="closeModal('reviewAppModal_<?= $app['id'] ?>')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">Cancel</button>
                                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm">Save Decision</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
