<?php
/**
 * Smart Hostel Complaint Management System
 * Accountant - Receipts Ledger & Printable Serials (FR-23, FR-24)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role(['accountant', 'admin']);

$db = get_db();

// Search & Filter
$search_query = trim($_GET['q'] ?? '');
$sql_pay = "SELECT p.*, s.reg_no, s.department, u.name as student_name, r.room_number, r.block, u_acc.name as accountant_name
            FROM payments p
            JOIN students s ON p.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN rooms r ON s.room_id = r.id
            LEFT JOIN users u_acc ON p.recorded_by = u_acc.id
            WHERE 1=1 ";
$params_pay = [];
if (!empty($search_query)) {
    $sql_pay .= " AND (s.reg_no LIKE ? OR u.name LIKE ? OR p.receipt_number LIKE ? OR p.transaction_ref LIKE ?) ";
    $q_like = "%$search_query%";
    $params_pay = [$q_like, $q_like, $q_like, $q_like];
}
$sql_pay .= " ORDER BY p.id DESC";
$stmt = $db->prepare($sql_pay);
$stmt->execute($params_pay);
$all_payments = $stmt->fetchAll();

$total_receipts = count($all_payments);
$total_collected = 0;
foreach ($all_payments as $ap) {
    $total_collected += $ap['amount'];
}

$page_title = 'Fee Receipts';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-amber-700 via-orange-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-400/30 mb-2">
                <i data-lucide="receipt" class="w-3.5 h-3.5"></i> Financial Audit & Serials (FR-23)
            </div>
            <h1 class="text-2xl font-black">Hostel Fee Receipts Ledger</h1>
            <p class="text-xs sm:text-sm text-amber-200 mt-1">Search issued receipts, verify bank transactions, and launch official print layouts.</p>
        </div>

        <a href="accountant_record_payment.php" class="px-4 py-2.5 rounded-xl bg-white text-amber-900 hover:bg-amber-50 font-bold text-xs shadow-md transition flex items-center gap-2 flex-shrink-0">
            <i data-lucide="plus-circle" class="w-4 h-4 text-amber-600"></i> Record New Payment
        </a>
    </div>

    <!-- Receipts Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Issued Receipts (<?= $total_receipts ?> Records)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Total Amount: <strong class="text-emerald-600"><?= CURRENCY_SYMBOL ?><?= number_format($total_collected, 2) ?></strong></p>
            </div>

            <!-- Search Form -->
            <form method="GET" action="accountant_receipts.php" class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search student or receipt #..." class="pl-9 pr-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500 w-56">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2"></i>
                </div>
                <?php if ($search_query): ?>
                    <a href="accountant_receipts.php" class="p-1.5 text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($all_payments)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5">Receipt No</th>
                            <th class="px-6 py-3.5">Student</th>
                            <th class="px-6 py-3.5">Room</th>
                            <th class="px-6 py-3.5">Fee Category</th>
                            <th class="px-6 py-3.5">Amount</th>
                            <th class="px-6 py-3.5">Payment Mode & Ref</th>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5 text-right">Receipt Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($all_payments as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    <?= htmlspecialchars($p['receipt_number']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900"><?= htmlspecialchars($p['student_name']) ?></p>
                                    <p class="text-[11px] text-slate-500"><?= htmlspecialchars($p['reg_no']) ?> • <?= htmlspecialchars($p['department']) ?></p>
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    <?= $p['room_number'] ? 'Room ' . htmlspecialchars($p['room_number']) : '—' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($p['fee_type']) ?></span>
                                    <p class="text-[10px] text-slate-400"><?= htmlspecialchars($p['semester']) ?></p>
                                </td>
                                <td class="px-6 py-4 font-bold text-emerald-600">
                                    <?= CURRENCY_SYMBOL ?><?= number_format($p['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    <p class="font-medium"><?= htmlspecialchars($p['payment_mode']) ?></p>
                                    <p class="text-[10px] font-mono text-slate-400"><?= htmlspecialchars($p['transaction_ref'] ?: 'N/A') ?></p>
                                </td>
                                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($p['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="receipt_print.php?id=<?= $p['id'] ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800 hover:bg-amber-100 font-semibold transition">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center text-xs text-slate-500">
                No fee receipts matched your search query.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
