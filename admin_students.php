<?php
/**
 * Smart Hostel Complaint Management System
 * Administrator - Student Records Management (FR-25)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role('admin');

$db = get_db();

// Handle Status Update
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_student_status') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $new_status = $_POST['student_status'] ?? 'Active Resident';
    if ($student_id > 0) {
        $stmt = $db->prepare("UPDATE students SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $student_id]);
        set_flash('success', "Student resident status updated to '$new_status'.");
    }
    header('Location: admin_students.php');
    exit;
}

// Search
$search_student = trim($_GET['sq'] ?? '');
$sql_students = "SELECT s.*, u.name, u.email, u.phone, r.room_number, r.block,
                        (SELECT COUNT(*) FROM complaints WHERE student_id = s.id) as complaint_count,
                        (SELECT SUM(amount) FROM payments WHERE student_id = s.id AND status = 'Paid') as total_paid
                 FROM students s
                 JOIN users u ON s.user_id = u.id
                 LEFT JOIN rooms r ON s.room_id = r.id
                 WHERE 1=1 ";
$params_st = [];
if (!empty($search_student)) {
    $sql_students .= " AND (s.reg_no LIKE ? OR u.name LIKE ? OR s.department LIKE ?) ";
    $params_st = ["%$search_student%", "%$search_student%", "%$search_student%"];
}
$sql_students .= " ORDER BY s.id DESC";
$stmt_st = $db->prepare($sql_students);
$stmt_st->execute($params_st);
$students_data = $stmt_st->fetchAll();

$page_title = 'Student Records';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-400/30 mb-2">
                <i data-lucide="users" class="w-3.5 h-3.5"></i> Resident Administration (FR-25)
            </div>
            <h1 class="text-2xl font-black">Student Records Management</h1>
            <p class="text-xs sm:text-sm text-purple-200 mt-1">Manage enrolled residents, view contact history, emergency contacts, and active status.</p>
        </div>
        <span class="px-3.5 py-1.5 rounded-xl font-bold text-xs bg-white/10 text-purple-200 border border-white/20">
            <?= count($students_data) ?> Registered Students
        </span>
    </div>

    <!-- Student Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="font-bold text-slate-900 text-base">Enrolled Students Register</h3>

            <!-- Search Form -->
            <form method="GET" action="admin_students.php" class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" name="sq" value="<?= htmlspecialchars($search_student) ?>" placeholder="Search student name or reg #..." class="pl-9 pr-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-purple-500 w-56">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2"></i>
                </div>
                <?php if ($search_student): ?>
                    <a href="admin_students.php" class="p-1.5 text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Student</th>
                        <th class="px-6 py-3.5">Department & Year</th>
                        <th class="px-6 py-3.5">Assigned Room</th>
                        <th class="px-6 py-3.5">Emergency Contact</th>
                        <th class="px-6 py-3.5">Complaints</th>
                        <th class="px-6 py-3.5">Fees Paid</th>
                        <th class="px-6 py-3.5">Resident Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($students_data as $st): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900"><?= htmlspecialchars($st['name']) ?></p>
                                <p class="text-[11px] text-slate-500"><?= htmlspecialchars($st['reg_no']) ?> • <?= htmlspecialchars($st['email']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($st['department']) ?></span>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($st['year']) ?> • <?= htmlspecialchars($st['gender']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?= $st['room_number'] ? '<span class="font-semibold text-slate-900">Room ' . htmlspecialchars($st['room_number']) . '</span> (' . htmlspecialchars($st['block']) . ')' : '<span class="text-slate-400">Not Assigned</span>' ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-[11px]">
                                <?= htmlspecialchars($st['emergency_contact'] ?: '—') ?>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800">
                                <?= $st['complaint_count'] ?> ticket(s)
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-600">
                                <?= CURRENCY_SYMBOL ?><?= number_format($st['total_paid'] ?: 0, 0) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge-status <?= $st['status'] === 'Active Resident' ? 'badge-resolved' : 'badge-low' ?>">
                                    <?= htmlspecialchars($st['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="openModal('editStudentModal_<?= $st['id'] ?>')" class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold transition">
                                    Manage
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Student Modal -->
                        <div id="editStudentModal_<?= $st['id'] ?>" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
                            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl modal-content">
                                <div class="flex justify-between items-start border-b border-slate-100 pb-3 mb-4">
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-base">Manage Resident Profile</h3>
                                        <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($st['name']) ?> (<?= htmlspecialchars($st['reg_no']) ?>)</p>
                                    </div>
                                    <button onclick="closeModal('editStudentModal_<?= $st['id'] ?>')" class="text-slate-400 hover:text-slate-600">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <form method="POST" action="admin_students.php">
                                    <input type="hidden" name="action" value="update_student_status">
                                    <input type="hidden" name="student_id" value="<?= $st['id'] ?>">

                                    <div class="space-y-4 text-xs">
                                        <div>
                                            <label class="block font-semibold text-slate-700 mb-1">Update Status (FR-25)</label>
                                            <select name="student_status" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-purple-500 bg-white">
                                                <option value="Active Resident" <?= $st['status'] === 'Active Resident' ? 'selected' : '' ?>>Active Resident</option>
                                                <option value="Room Allocated" <?= $st['status'] === 'Room Allocated' ? 'selected' : '' ?>>Room Allocated (Awaiting Check-in)</option>
                                                <option value="Checked Out" <?= $st['status'] === 'Checked Out' ? 'selected' : '' ?>>Checked Out</option>
                                                <option value="Suspended" <?= $st['status'] === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2">
                                        <button type="button" onclick="closeModal('editStudentModal_<?= $st['id'] ?>')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">Cancel</button>
                                        <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-sm">Save Changes</button>
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
