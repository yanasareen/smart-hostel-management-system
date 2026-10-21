<?php
/**
 * Smart Hostel Complaint Management System
 * Official Printable Fee Receipt
 * Requirements: FR-23, FR-24, BR-06
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_login();

$receipt_id = intval($_GET['id'] ?? 0);
if ($receipt_id <= 0) {
    die("Invalid receipt requested.");
}

$db = get_db();
$stmt = $db->prepare("SELECT p.*, s.reg_no, s.department, s.year, u.name as student_name, u.phone as student_phone,
                             r.room_number, r.block, u_acc.name as accountant_name
                      FROM payments p
                      JOIN students s ON p.student_id = s.id
                      JOIN users u ON s.user_id = u.id
                      LEFT JOIN rooms r ON s.room_id = r.id
                      LEFT JOIN users u_acc ON p.recorded_by = u_acc.id
                      WHERE p.id = ?");
$stmt->execute([$receipt_id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    die("Receipt record not found.");
}

// Security Check (NFR-08): Student can only view their own receipt
if (current_user_role() === 'student') {
    $current_student_id = $db->prepare("SELECT id FROM students WHERE user_id = ?");
    $current_student_id->execute([current_user_id()]);
    $sid = $current_student_id->fetchColumn();
    if ($receipt['student_id'] != $sid) {
        die("Access denied. You may only view your own receipts.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt - <?= htmlspecialchars($receipt['receipt_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .receipt-box { box-shadow: none !important; border: 1px solid #000 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4 font-sans text-slate-800">

    <!-- Top Action Bar -->
    <div class="max-w-3xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="javascript:history.back()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white px-3 py-2 rounded-lg border border-slate-200 shadow-sm transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg shadow-sm transition">
            <i data-lucide="printer" class="w-4 h-4"></i> Print Official Receipt
        </button>
    </div>

    <!-- Official Receipt Document -->
    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-slate-300 shadow-xl p-8 sm:p-12 relative overflow-hidden receipt-box">
        <!-- University Watermark -->
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none select-none">
            <i data-lucide="building-2" class="w-96 h-96"></i>
        </div>

        <!-- Header -->
        <div class="border-b-2 border-slate-900 pb-6 flex justify-between items-start gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-slate-900 text-white rounded-xl flex items-center justify-center font-bold text-xl">
                        AU
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 tracking-tight uppercase"><?= HOSTEL_NAME ?></h1>
                        <p class="text-xs text-slate-600 font-medium"><?= HOSTEL_ADDRESS ?></p>
                        <p class="text-[11px] text-slate-500">Office of the Chief Warden & Hostel Accounts Department</p>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded bg-slate-100 border border-slate-300 font-mono text-xs font-bold text-slate-900">
                    <?= htmlspecialchars($receipt['receipt_number']) ?>
                </span>
                <p class="text-[11px] text-slate-500 mt-1.5">Date: <strong><?= date('d F Y', strtotime($receipt['created_at'])) ?></strong></p>
                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase mt-1">
                    ✓ Verified Paid
                </span>
            </div>
        </div>

        <!-- Title -->
        <div class="py-4 text-center">
            <h2 class="text-base font-black text-slate-900 tracking-wider uppercase underline underline-offset-4">HOSTEL FEE PAYMENT RECEIPT</h2>
        </div>

        <!-- Student Information Grid -->
        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mb-6 grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Resident Name</span>
                <strong class="text-slate-900 text-sm"><?= htmlspecialchars($receipt['student_name']) ?></strong>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Registration Number</span>
                <strong class="text-slate-900 text-sm font-mono"><?= htmlspecialchars($receipt['reg_no']) ?></strong>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Department / Year</span>
                <strong class="text-slate-800"><?= htmlspecialchars($receipt['department']) ?> (<?= htmlspecialchars($receipt['year']) ?>)</strong>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Allocated Room</span>
                <strong class="text-slate-800"><?= $receipt['room_number'] ? 'Room ' . htmlspecialchars($receipt['room_number']) . ' (' . htmlspecialchars($receipt['block']) . ')' : 'Not Assigned' ?></strong>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Payment Mode</span>
                <strong class="text-slate-800"><?= htmlspecialchars($receipt['payment_mode']) ?></strong>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Transaction Reference</span>
                <strong class="text-slate-800 font-mono text-[11px]"><?= htmlspecialchars($receipt['transaction_ref'] ?: 'Direct Counter') ?></strong>
            </div>
        </div>

        <!-- Payment Breakdown Table -->
        <table class="w-full text-left text-xs text-slate-700 border border-slate-300 rounded-lg overflow-hidden mb-6">
            <thead class="bg-slate-100 font-bold uppercase text-slate-700 border-b border-slate-300">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">Fee Particulars</th>
                    <th class="p-3">Academic Term</th>
                    <th class="p-3 text-right">Amount (INR)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <tr>
                    <td class="p-3">1</td>
                    <td class="p-3">
                        <strong class="text-slate-900"><?= htmlspecialchars($receipt['fee_type']) ?></strong>
                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($receipt['remarks'] ?: 'Standard accommodation and maintenance charges') ?></p>
                    </td>
                    <td class="p-3"><?= htmlspecialchars($receipt['semester']) ?></td>
                    <td class="p-3 text-right font-mono font-bold text-slate-900">
                        <?= CURRENCY_SYMBOL ?><?= number_format($receipt['amount'], 2) ?>
                    </td>
                </tr>
            </tbody>
            <tfoot class="bg-slate-50 border-t-2 border-slate-300 font-bold">
                <tr>
                    <td colspan="3" class="p-3 text-right uppercase text-slate-700">Total Amount Paid:</td>
                    <td class="p-3 text-right text-base text-slate-900 font-black">
                        <?= CURRENCY_SYMBOL ?><?= number_format($receipt['amount'], 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures & Authentication Stamp -->
        <div class="mt-12 pt-6 border-t border-slate-200 grid grid-cols-2 gap-8 text-xs text-slate-600">
            <div>
                <div class="w-32 h-16 border border-dashed border-slate-300 rounded-lg flex items-center justify-center text-[10px] text-slate-400 uppercase tracking-widest mb-2">
                    Official Stamp
                </div>
                <p class="text-[11px] text-slate-400">Computer generated receipt under IEEE SRS v1.0 specifications.</p>
            </div>
            <div class="text-right flex flex-col justify-end">
                <p class="font-bold text-slate-900"><?= htmlspecialchars($receipt['accountant_name'] ?? 'Authorized Accounts Officer') ?></p>
                <p class="text-[11px] text-slate-500">Hostel Accounts Officer / Bursar</p>
                <p class="text-[10px] text-slate-400 mt-0.5"><?= HOSTEL_NAME ?></p>
            </div>
        </div>
    </div>

    <script>
        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>
</body>
</html>
