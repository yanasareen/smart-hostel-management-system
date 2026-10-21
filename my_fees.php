<?php
/**
 * Smart Hostel Complaint Management System
 * Student - Fees & Printable Receipts (FR-24, BR-06)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role('student');

$db = get_db();
$user_id = current_user_id();

// Fetch Student Profile
$stmt = $db->prepare("SELECT s.*, r.room_number, r.block, r.room_type, r.fee_per_semester 
                      FROM students s 
                      LEFT JOIN rooms r ON s.room_id = r.id 
                      WHERE s.user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();
$student_id = $student['id'];

// Fetch Payments
$stmt = $db->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$student_id]);
$my_payments = $stmt->fetchAll();

$total_paid = 0;
foreach ($my_payments as $p) {
    if ($p['status'] === 'Paid') {
        $total_paid += $p['amount'];
    }
}

$semester_fee = $student['fee_per_semester'] ?: 35000.00;
$fee_status = ($total_paid >= $semester_fee) ? 'Fully Paid' : 'Pending Dues';

$page_title = 'Fees & Receipts';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-700 via-indigo-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-blue-200 border border-white/20 mb-2">
                <i data-lucide="receipt" class="w-3.5 h-3.5"></i> Student Finance & Ledger (FR-24)
            </div>
            <h1 class="text-2xl font-black">Hostel Fees & Digital Receipts</h1>
            <p class="text-xs sm:text-sm text-blue-200 mt-1">Inspect your fee payment statements, verify transaction refs, and print official receipts.</p>
        </div>
        <span class="px-3.5 py-1.5 rounded-xl font-bold text-xs <?= $fee_status === 'Fully Paid' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-amber-500/20 text-amber-300 border border-amber-400/30' ?>">
            Status: <?= $fee_status ?>
        </span>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Fees Cleared</span>
                <p class="text-2xl font-black text-slate-900"><?= CURRENCY_SYMBOL ?><?= number_format($total_paid, 2) ?></p>
                <span class="text-xs text-emerald-600 font-medium"><?= count($my_payments) ?> Verified Payment(s)</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="file-check-2" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Semester Assessment</span>
                <p class="text-2xl font-black text-blue-600"><?= CURRENCY_SYMBOL ?><?= number_format($semester_fee, 2) ?></p>
                <span class="text-xs text-slate-500"><?= htmlspecialchars($student['room_type'] ?: 'Standard Double') ?></span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="printer" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Digital Receipts</span>
                <p class="text-2xl font-black text-purple-600"><?= count($my_payments) ?></p>
                <span class="text-xs text-slate-500">Ready for A4 Print</span>
            </div>
        </div>
    </div>

    <!-- Payments Ledger Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Recorded Payment Transactions</h3>
                <p class="text-xs text-slate-500 mt-0.5">Official transactions recorded by the Hostel Accountant (FR-21, BR-06)</p>
            </div>
        </div>

        <?php if (!empty($my_payments)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5">Receipt Serial</th>
                            <th class="px-6 py-3.5">Fee Category</th>
                            <th class="px-6 py-3.5">Amount</th>
                            <th class="px-6 py-3.5">Payment Mode</th>
                            <th class="px-6 py-3.5">Transaction Ref / UTR</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Official Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($my_payments as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    <?= htmlspecialchars($p['receipt_number']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($p['fee_type']) ?></p>
                                    <p class="text-[10px] text-slate-400"><?= htmlspecialchars($p['semester']) ?></p>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <?= CURRENCY_SYMBOL ?><?= number_format($p['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    <?= htmlspecialchars($p['payment_mode']) ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px] text-slate-500">
                                    <?= htmlspecialchars($p['transaction_ref'] ?: 'N/A') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="badge-status badge-resolved">
                                        <?= htmlspecialchars($p['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="receipt_print.php?id=<?= $p['id'] ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold transition">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> View / Print
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center text-xs text-slate-500">
                <i data-lucide="receipt" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                No fee payment records found. Contact the Accountant office to record offline bank or counter deposits.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
