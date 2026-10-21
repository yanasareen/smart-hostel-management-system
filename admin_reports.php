<?php
/**
 * Smart Hostel Complaint Management System
 * Administrator - Reports Generator & Audit Export (FR-26, FR-28)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role('admin');

$db = get_db();

// Filter parameters
$report_type = $_GET['report_type'] ?? 'complaints';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$report_records = [];
if ($report_type === 'complaints') {
    $stmt = $db->prepare("SELECT c.*, s.reg_no, u.name as student_name, r.room_number 
                          FROM complaints c 
                          JOIN students s ON c.student_id = s.id 
                          JOIN users u ON s.user_id = u.id 
                          LEFT JOIN rooms r ON c.room_id = r.id 
                          WHERE DATE(c.created_at) BETWEEN ? AND ? 
                          ORDER BY c.id DESC");
    $stmt->execute([$start_date, $end_date]);
    $report_records = $stmt->fetchAll();
} elseif ($report_type === 'payments') {
    $stmt = $db->prepare("SELECT p.*, s.reg_no, u.name as student_name 
                          FROM payments p 
                          JOIN students s ON p.student_id = s.id 
                          JOIN users u ON s.user_id = u.id 
                          WHERE DATE(p.created_at) BETWEEN ? AND ? 
                          ORDER BY p.id DESC");
    $stmt->execute([$start_date, $end_date]);
    $report_records = $stmt->fetchAll();
}

$page_title = 'Administrative Reports';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-400/30 mb-2">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> Management Auditing (FR-26)
            </div>
            <h1 class="text-2xl font-black">Administrative Reports Generator</h1>
            <p class="text-xs sm:text-sm text-purple-200 mt-1">Generate filtered summaries for hostel complaints, room occupancy, and fee audits.</p>
        </div>

        <button onclick="window.print()" class="no-print px-4 py-2.5 rounded-xl bg-white text-purple-900 hover:bg-purple-50 font-bold text-xs shadow-md transition flex items-center gap-2 flex-shrink-0">
            <i data-lucide="printer" class="w-4 h-4 text-purple-600"></i> Print / Export Report
        </button>
    </div>

    <!-- Filter Form Card -->
    <div class="no-print bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <form method="GET" action="admin_reports.php" class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs items-end">
            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Select Report Type *</label>
                <select name="report_type" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-white">
                    <option value="complaints" <?= $report_type === 'complaints' ? 'selected' : '' ?>>Hostel Complaints Report</option>
                    <option value="payments" <?= $report_type === 'payments' ? 'selected' : '' ?>>Fee Collections Report</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 px-4 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition flex items-center justify-center gap-1.5">
                    <i data-lucide="filter" class="w-4 h-4"></i> Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- Report Document Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6 sm:p-8">
        <div class="border-b border-slate-200 pb-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <span class="text-xs uppercase font-bold text-purple-600 tracking-wider">Official Hostel Audit Report</span>
                <h3 class="text-xl font-black text-slate-900 mt-0.5">
                    <?= $report_type === 'complaints' ? 'Complaints & Maintenance Log' : 'Fee Collections & Revenue Audit' ?>
                </h3>
                <p class="text-xs text-slate-500 mt-1">Period: <strong><?= date('d M Y', strtotime($start_date)) ?></strong> to <strong><?= date('d M Y', strtotime($end_date)) ?></strong></p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-800">
                    <?= count($report_records) ?> Record(s) Found
                </span>
            </div>
        </div>

        <?php if (!empty($report_records)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <?php if ($report_type === 'complaints'): ?>
                                <th class="p-3">Ticket ID</th>
                                <th class="p-3">Student</th>
                                <th class="p-3">Category</th>
                                <th class="p-3">Priority</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Date</th>
                            <?php else: ?>
                                <th class="p-3">Receipt No</th>
                                <th class="p-3">Student</th>
                                <th class="p-3">Fee Type</th>
                                <th class="p-3">Payment Mode</th>
                                <th class="p-3">Amount</th>
                                <th class="p-3">Date</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($report_records as $rec): ?>
                            <tr>
                                <?php if ($report_type === 'complaints'): ?>
                                    <td class="p-3 font-mono font-bold text-slate-900">#<?= htmlspecialchars($rec['ticket_id']) ?></td>
                                    <td class="p-3"><?= htmlspecialchars($rec['student_name']) ?> (<?= htmlspecialchars($rec['reg_no']) ?>)</td>
                                    <td class="p-3 font-medium text-slate-800"><?= htmlspecialchars($rec['category']) ?></td>
                                    <td class="p-3">
                                        <span class="badge-status <?= $rec['priority'] === 'High' ? 'badge-high' : ($rec['priority'] === 'Medium' ? 'badge-medium' : 'badge-low') ?>">
                                            <?= htmlspecialchars($rec['priority']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <span class="badge-status <?= $rec['status'] === 'Resolved' ? 'badge-resolved' : ($rec['status'] === 'In Progress' ? 'badge-progress' : 'badge-pending') ?>">
                                            <?= htmlspecialchars($rec['status']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-500"><?= date('d M Y', strtotime($rec['created_at'])) ?></td>
                                <?php else: ?>
                                    <td class="p-3 font-mono font-bold text-slate-900"><?= htmlspecialchars($rec['receipt_number']) ?></td>
                                    <td class="p-3"><?= htmlspecialchars($rec['student_name']) ?> (<?= htmlspecialchars($rec['reg_no']) ?>)</td>
                                    <td class="p-3 font-medium text-slate-800"><?= htmlspecialchars($rec['fee_type']) ?></td>
                                    <td class="p-3"><?= htmlspecialchars($rec['payment_mode']) ?></td>
                                    <td class="p-3 font-bold text-emerald-600"><?= CURRENCY_SYMBOL ?><?= number_format($rec['amount'], 2) ?></td>
                                    <td class="p-3 text-slate-500"><?= date('d M Y', strtotime($rec['created_at'])) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center text-xs text-slate-500">
                No records found for the chosen date range.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
