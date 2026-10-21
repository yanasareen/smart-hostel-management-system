<?php
/**
 * Smart Hostel Complaint Management System
 * Warden - Complaint Dispatch & Resolution Hub (FR-15, FR-18, FR-19, BR-03)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role(['warden', 'admin']);

$db = get_db();
$warden_user = current_user();

run_sla_escalation_check();

// Handle Status Update (FR-15, BR-03)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_complaint') {
    $cmp_id = intval($_POST['complaint_id'] ?? 0);
    $status = $_POST['status'] ?? 'In Progress';
    $notes = trim($_POST['staff_notes'] ?? '');

    $valid_statuses = ['Pending', 'In Progress', 'Resolved'];
    if ($cmp_id > 0 && in_array($status, $valid_statuses)) {
        $resolved_at = ($status === 'Resolved') ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("UPDATE complaints SET status = ?, staff_notes = ?, assigned_staff_id = ?, resolved_at = COALESCE(?, resolved_at) WHERE id = ?");
        $stmt->execute([$status, $notes, $warden_user['id'], $resolved_at, $cmp_id]);

        // Notify Student (FR-17)
        $stmt = $db->prepare("SELECT c.ticket_id, c.category, s.user_id FROM complaints c JOIN students s ON c.student_id = s.id WHERE c.id = ?");
        $stmt->execute([$cmp_id]);
        $ticket_data = $stmt->fetch();

        if ($ticket_data) {
            $type = ($status === 'Resolved') ? 'success' : 'info';
            $msg = ($status === 'Resolved') 
                ? "Complaint #{$ticket_data['ticket_id']} has been Resolved! Please rate your satisfaction." 
                : "Complaint #{$ticket_data['ticket_id']} is now {$status}. Notes: $notes";
            create_notification($ticket_data['user_id'], "Complaint Status: $status", $msg, $type, 'my_complaints.php');
        }

        set_flash('success', "Ticket status updated to '$status' successfully.");
    }
    header('Location: warden_complaints.php');
    exit;
}

// Fetch complaints with filters
$filter_category = $_GET['cat'] ?? '';
$filter_status = $_GET['status'] ?? '';
$sql_cmp = "SELECT c.*, s.reg_no, u.name as student_name, u.phone as student_phone, r.room_number, r.block,
                   cf.rating, cf.comment as feedback_comment,
                   p.ticket_id as parent_ticket_num
            FROM complaints c
            JOIN students s ON c.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN rooms r ON c.room_id = r.id
            LEFT JOIN complaint_feedback cf ON c.id = cf.complaint_id
            LEFT JOIN complaints p ON c.parent_duplicate_id = p.id
            WHERE 1=1 ";
$params_cmp = [];
if (!empty($filter_category)) {
    $sql_cmp .= " AND c.category = ? ";
    $params_cmp[] = $filter_category;
}
if (!empty($filter_status)) {
    $sql_cmp .= " AND c.status = ? ";
    $params_cmp[] = $filter_status;
}
$sql_cmp .= " ORDER BY c.is_escalated DESC, c.id DESC";
$stmt = $db->prepare($sql_cmp);
$stmt->execute($params_cmp);
$all_complaints = $stmt->fetchAll();

$page_title = 'Manage Complaints';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-emerald-800 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 mb-2">
                <i data-lucide="wrench" class="w-3.5 h-3.5"></i> Maintenance Dispatch Hub (FR-15)
            </div>
            <h1 class="text-2xl font-black">Hostel Complaint Management</h1>
            <p class="text-xs sm:text-sm text-emerald-200 mt-1">Dispatch repairs, inspect linked duplicates, update workflow status, and review ratings.</p>
        </div>
    </div>

    <!-- Complaints Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Complaints Queue</h3>
                <p class="text-xs text-slate-500 mt-0.5">Total: <?= count($all_complaints) ?> tickets</p>
            </div>

            <!-- Filters -->
            <form method="GET" action="warden_complaints.php" class="flex flex-wrap items-center gap-2 text-xs">
                <select name="cat" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white">
                    <option value="">All Categories</option>
                    <option value="Electricity" <?= $filter_category === 'Electricity' ? 'selected' : '' ?>>Electricity</option>
                    <option value="Plumbing" <?= $filter_category === 'Plumbing' ? 'selected' : '' ?>>Plumbing</option>
                    <option value="Wi-Fi" <?= $filter_category === 'Wi-Fi' ? 'selected' : '' ?>>Wi-Fi</option>
                    <option value="Cleaning" <?= $filter_category === 'Cleaning' ? 'selected' : '' ?>>Cleaning</option>
                    <option value="Furniture" <?= $filter_category === 'Furniture' ? 'selected' : '' ?>>Furniture</option>
                </select>

                <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>

                <?php if ($filter_category || $filter_status): ?>
                    <a href="warden_complaints.php" class="p-1.5 text-slate-400 hover:text-slate-600" title="Reset filter">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Ticket</th>
                        <th class="px-6 py-3.5">Resident</th>
                        <th class="px-6 py-3.5">Category & Location</th>
                        <th class="px-6 py-3.5">Priority</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Student Rating</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($all_complaints as $c): ?>
                        <tr class="hover:bg-slate-50/80 transition <?= $c['is_escalated'] && $c['status'] !== 'Resolved' ? 'bg-red-50/30' : '' ?>">
                            <td class="px-6 py-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                #<?= htmlspecialchars($c['ticket_id']) ?>
                                <?php if ($c['is_escalated']): ?>
                                    <span class="block text-[10px] text-red-600 font-bold">⚠️ ESCALATED</span>
                                <?php endif; ?>
                                <span class="block text-[10px] text-slate-400 font-normal"><?= time_ago($c['created_at']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900"><?= htmlspecialchars($c['student_name']) ?></p>
                                <p class="text-[11px] text-slate-500"><?= htmlspecialchars($c['reg_no']) ?> • <?= htmlspecialchars($c['student_phone']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($c['category']) ?></span>
                                <p class="text-[11px] text-slate-500 truncate max-w-xs"><?= htmlspecialchars($c['title']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($c['location_details'] ?: 'Room ' . $c['room_number']) ?></p>
                                <?php if ($c['parent_duplicate_id']): ?>
                                    <span class="inline-block px-1.5 py-0.5 text-[9px] bg-amber-100 text-amber-800 rounded font-semibold mt-1">
                                        Duplicate of #<?= htmlspecialchars($c['parent_ticket_num'] ?? 'Parent') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge-status <?= $c['priority'] === 'High' ? 'badge-high' : ($c['priority'] === 'Medium' ? 'badge-medium' : 'badge-low') ?>">
                                    <?= htmlspecialchars($c['priority']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge-status <?= $c['status'] === 'Resolved' ? 'badge-resolved' : ($c['status'] === 'In Progress' ? 'badge-progress' : 'badge-pending') ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($c['rating']): ?>
                                    <div class="text-amber-500 font-bold" title="<?= htmlspecialchars($c['feedback_comment']) ?>">
                                        <?= str_repeat('★', $c['rating']) ?> (<?= $c['rating'] ?>/5)
                                    </div>
                                    <p class="text-[10px] text-slate-500 italic truncate max-w-[140px]">"<?= htmlspecialchars($c['feedback_comment']) ?>"</p>
                                <?php else: ?>
                                    <span class="text-slate-400 text-[11px]">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <button onclick="openModal('updateTicketModal_<?= $c['id'] ?>')" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition">
                                    Update Status
                                </button>
                            </td>
                        </tr>

                        <!-- Update Modal -->
                        <div id="updateTicketModal_<?= $c['id'] ?>" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
                            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl modal-content">
                                <div class="flex justify-between items-start border-b border-slate-100 pb-3 mb-4">
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-base">Update Ticket #<?= htmlspecialchars($c['ticket_id']) ?></h3>
                                        <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($c['category']) ?> • <?= htmlspecialchars($c['student_name']) ?></p>
                                    </div>
                                    <button onclick="closeModal('updateTicketModal_<?= $c['id'] ?>')" class="text-slate-400 hover:text-slate-600">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <form method="POST" action="warden_complaints.php">
                                    <input type="hidden" name="action" value="update_complaint">
                                    <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">

                                    <div class="space-y-4 text-xs">
                                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                            <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($c['title']) ?></p>
                                            <p class="text-slate-600 mt-1"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                                        </div>

                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Update Status (FR-15, BR-03)</label>
                                            <select name="status" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 bg-white">
                                                <option value="Pending" <?= $c['status'] === 'Pending' ? 'selected' : '' ?>>Pending (Under Review)</option>
                                                <option value="In Progress" <?= $c['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress (Technician Dispatched)</option>
                                                <option value="Resolved" <?= $c['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved (Issue Fixed)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Technician / Resolution Notes</label>
                                            <textarea name="staff_notes" rows="3" placeholder="Enter repair details..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500"><?= htmlspecialchars($c['staff_notes'] ?? '') ?></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2">
                                        <button type="button" onclick="closeModal('updateTicketModal_<?= $c['id'] ?>')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">Cancel</button>
                                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm">Save Changes</button>
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
