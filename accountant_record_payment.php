<?php
/**
 * Smart Hostel Complaint Management System
 * Accountant - Record Student Fee Payment (FR-22, FR-23, BR-06)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role(['accountant', 'admin']);

$db = get_db();
$accountant_user = current_user();

// Handle Record Payment
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $fee_type = trim($_POST['fee_type'] ?? 'Hostel & Mess Fee');
    $amount = floatval($_POST['amount'] ?? 0);
    $semester = trim($_POST['semester'] ?? 'Fall Semester 2026');
    $payment_mode = trim($_POST['payment_mode'] ?? 'Bank Transfer');
    $transaction_ref = trim($_POST['transaction_ref'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($student_id <= 0 || $amount <= 0) {
        set_flash('danger', 'Please select a student and enter a valid payment amount.');
    } else {
        $receipt_number = 'RCP-' . date('Y') . '-' . rand(10000, 99999);

        $stmt = $db->prepare("INSERT INTO payments (receipt_number, student_id, fee_type, amount, semester, payment_mode, transaction_ref, status, recorded_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, 'Paid', ?, ?)");
        $stmt->execute([$receipt_number, $student_id, $fee_type, $amount, $semester, $payment_mode, $transaction_ref, $accountant_user['id'], $remarks]);
        $payment_id = $db->lastInsertId();

        // Notify Student
        $stmt = $db->prepare("SELECT user_id, reg_no FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $st = $stmt->fetch();
        if ($st) {
            create_notification(
                $st['user_id'], 
                'Official Fee Receipt Issued', 
                "Receipt #$receipt_number for " . CURRENCY_SYMBOL . number_format($amount, 2) . " ($fee_type) has been generated.", 
                'success', 
                'my_fees.php'
            );
        }

        set_flash('success', "Payment of " . CURRENCY_SYMBOL . number_format($amount, 2) . " recorded successfully! Official Receipt #$receipt_number generated.");
        header('Location: accountant_receipts.php');
        exit;
    }
}

// Fetch Students
$students_list = $db->query("SELECT s.id, s.reg_no, s.department, u.name, r.room_number, r.block 
                             FROM students s 
                             JOIN users u ON s.user_id = u.id 
                             LEFT JOIN rooms r ON s.room_id = r.id 
                             ORDER BY u.name")->fetchAll();

$page_title = 'Record Fee Payment';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-amber-700 via-orange-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-400/30 mb-2">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> Fee Transaction Entry (FR-22)
            </div>
            <h1 class="text-2xl font-black">Record Student Fee Payment</h1>
            <p class="text-xs sm:text-sm text-amber-200 mt-1">Record bank transfers, UPI transactions, or cash counter payments to generate official receipts.</p>
        </div>
        <a href="accountant_receipts.php" class="px-4 py-2.5 rounded-xl bg-white text-amber-900 hover:bg-amber-50 font-bold text-xs shadow-md transition flex items-center gap-2 flex-shrink-0">
            <i data-lucide="receipt" class="w-4 h-4 text-amber-600"></i> View All Receipts
        </a>
    </div>

    <!-- Payment Entry Form -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h3 class="text-base font-bold text-slate-900 mb-1">New Fee Entry Form</h3>
        <p class="text-xs text-slate-500 mb-6">Enter payment particulars. A verifiable serial receipt will be created immediately (BR-06).</p>

        <form method="POST" action="accountant_record_payment.php" class="space-y-4">
            <input type="hidden" name="action" value="record_payment">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Select Resident Student *</label>
                <select name="student_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500 bg-white">
                    <option value="">-- Choose Student --</option>
                    <?php foreach ($students_list as $sl): ?>
                        <option value="<?= $sl['id'] ?>">
                            <?= htmlspecialchars($sl['name']) ?> (<?= htmlspecialchars($sl['reg_no']) ?>) • <?= htmlspecialchars($sl['department']) ?> <?= $sl['room_number'] ? ' - Room ' . $sl['room_number'] : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Fee Category *</label>
                    <select name="fee_type" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="Hostel & Mess Fee">Hostel & Mess Fee</option>
                        <option value="Room Caution Deposit">Room Caution Deposit</option>
                        <option value="Electricity / AC Surcharge">Electricity / AC Surcharge</option>
                        <option value="Late Registration Fine">Late Registration Fine</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" required placeholder="38000.00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Academic Semester</label>
                    <input type="text" name="semester" value="Fall Semester 2026" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Mode *</label>
                    <select name="payment_mode" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                        <option value="UPI Payment">UPI / QR Transfer</option>
                        <option value="Cheque / DD">Cheque / Demand Draft</option>
                        <option value="Cash Deposit">Cash Deposit at Counter</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Transaction Reference / UTR Number</label>
                <input type="text" name="transaction_ref" placeholder="e.g. UTR-AXIS-992147812903" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Accountant Remarks</label>
                <input type="text" name="remarks" placeholder="e.g. Paid in full for Autumn session" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500">
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-md transition flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i> Record Payment & Generate Receipt
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
