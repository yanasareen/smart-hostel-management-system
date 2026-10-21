<?php
/**
 * Smart Hostel Complaint Management System
 * Accountant Dashboard & Financial Overview (FR-21 to FR-24)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role(['accountant', 'admin']);

$db = get_db();

// Financial Statistics
$total_revenue = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'Paid'")->fetchColumn() ?: 0;
$total_receipts = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'Paid'")->fetchColumn() ?: 0;
$today_collections = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'Paid' AND DATE(created_at) = CURRENT_DATE")->fetchColumn() ?: 0;

// Collections by Mode
$modes_data = $db->query("SELECT payment_mode, SUM(amount) as total FROM payments WHERE status = 'Paid' GROUP BY payment_mode")->fetchAll();
$mode_map = ['Bank Transfer' => 0, 'UPI Payment' => 0, 'Cash Deposit' => 0, 'Cheque / DD' => 0];
foreach ($modes_data as $md) {
    $mode_map[$md['payment_mode']] = (float)$md['total'];
}

// Collections by Fee Type
$types_data = $db->query("SELECT fee_type, SUM(amount) as total FROM payments WHERE status = 'Paid' GROUP BY fee_type")->fetchAll();

// Recent 5 Transactions
$recent_payments = $db->query("SELECT p.*, s.reg_no, u.name as student_name 
                               FROM payments p 
                               JOIN students s ON p.student_id = s.id 
                               JOIN users u ON s.user_id = u.id 
                               ORDER BY p.id DESC LIMIT 5")->fetchAll();

$page_title = 'Finance Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-amber-700 via-orange-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-400/30 mb-3 backdrop-blur-sm">
                <i data-lucide="receipt" class="w-3.5 h-3.5"></i> Hostel Finance Analytics (FR-21)
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Hostel Financial Command</h1>
            <p class="text-xs sm:text-sm text-amber-200 mt-1">
                Monitor fee collection streams, verify transaction volumes, and audit revenue balances.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="accountant_record_payment.php" class="px-5 py-2.5 rounded-xl bg-white text-amber-900 hover:bg-amber-50 font-bold text-xs shadow-md transition flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-amber-600"></i> Record Fee Payment
            </a>
            <a href="accountant_receipts.php" class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/20 transition flex items-center gap-2">
                <i data-lucide="receipt" class="w-4 h-4"></i> View Receipts Ledger
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Collections</span>
                <p class="text-2xl font-black text-slate-900"><?= CURRENCY_SYMBOL ?><?= number_format($total_revenue, 2) ?></p>
                <span class="text-xs text-emerald-600 font-medium">Recorded & Verified</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="file-check-2" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Receipts Issued</span>
                <p class="text-2xl font-black text-slate-900"><?= $total_receipts ?></p>
                <a href="accountant_receipts.php" class="text-xs text-blue-600 font-medium hover:underline">Inspect serials &rarr;</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Today's Collections</span>
                <p class="text-2xl font-black text-amber-600"><?= CURRENCY_SYMBOL ?><?= number_format($today_collections, 2) ?></p>
                <span class="text-xs text-slate-500"><?= date('d M Y') ?></span>
            </div>
        </div>
    </div>

    <!-- Visual Bar Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Bar Chart: Payment Modes Breakdown -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Collections by Payment Mode (Bar Graph)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Distribution of collected fee funds across payment channels</p>
                </div>
            </div>

            <div class="relative h-72 w-full pt-2">
                <canvas id="paymentModeBarChart"></canvas>
            </div>
        </div>

        <!-- Doughnut Chart: Fee Category Share -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base mb-1">Fee Category Share</h3>
                <p class="text-xs text-slate-500 mb-4">Hostel room fee vs caution vs fines</p>
                <div class="relative h-56 w-full flex items-center justify-center">
                    <canvas id="feeCategoryChart"></canvas>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 text-center">
                <a href="accountant_receipts.php" class="text-xs font-semibold text-amber-700 hover:underline">
                    View Receipts Ledger &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Payments Quick Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base">Recent Transactions</h3>
            <a href="accountant_receipts.php" class="text-xs text-blue-600 hover:underline font-semibold">View All Receipts &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Receipt Serial</th>
                        <th class="px-6 py-3.5">Student</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Amount</th>
                        <th class="px-6 py-3.5">Mode</th>
                        <th class="px-6 py-3.5">Date</th>
                        <th class="px-6 py-3.5 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recent_payments as $rp): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-900"><?= htmlspecialchars($rp['receipt_number']) ?></td>
                            <td class="px-6 py-4 font-semibold text-slate-800"><?= htmlspecialchars($rp['student_name']) ?> (<?= htmlspecialchars($rp['reg_no']) ?>)</td>
                            <td class="px-6 py-4"><?= htmlspecialchars($rp['fee_type']) ?></td>
                            <td class="px-6 py-4 font-bold text-emerald-600"><?= CURRENCY_SYMBOL ?><?= number_format($rp['amount'], 2) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($rp['payment_mode']) ?></td>
                            <td class="px-6 py-4 text-slate-500"><?= date('d M Y', strtotime($rp['created_at'])) ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="receipt_print.php?id=<?= $rp['id'] ?>" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-800 hover:bg-amber-100 font-semibold transition">
                                    Print Receipt
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Payment Mode Bar Chart
    const ctxMode = document.getElementById('paymentModeBarChart').getContext('2d');
    new Chart(ctxMode, {
        type: 'bar',
        data: {
            labels: ['Bank Transfer', 'UPI Payment', 'Cash Deposit', 'Cheque / DD'],
            datasets: [{
                label: 'Total Collected (₹)',
                data: [
                    <?= $mode_map['Bank Transfer'] ?>,
                    <?= $mode_map['UPI Payment'] ?>,
                    <?= $mode_map['Cash Deposit'] ?>,
                    <?= $mode_map['Cheque / DD'] ?>
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.85)',
                    'rgba(16, 185, 129, 0.85)',
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(139, 92, 246, 0.85)'
                ],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (c) { return ' ₹' + c.parsed.y.toLocaleString(); }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Fee Category Doughnut Chart
    const ctxType = document.getElementById('feeCategoryChart').getContext('2d');
    new Chart(ctxType, {
        type: 'doughnut',
        data: {
            labels: [<?php foreach ($types_data as $td) echo "'" . addslashes($td['fee_type']) . "',"; ?>],
            datasets: [{
                data: [<?php foreach ($types_data as $td) echo $td['total'] . ","; ?>],
                backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
