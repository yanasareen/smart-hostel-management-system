<?php
/**
 * Smart Hostel Complaint Management System
 * Student - Accommodation Application (FR-05, FR-06, BR-01)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role('student');

$db = get_db();
$user_id = current_user_id();

// Fetch Student Profile
$stmt = $db->prepare("SELECT s.*, r.room_number, r.block, r.floor, r.room_type, r.fee_per_semester, r.amenities 
                      FROM students s 
                      LEFT JOIN rooms r ON s.room_id = r.id 
                      WHERE s.user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();
$student_id = $student['id'];

// Handle POST Application Submission (FR-05)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_accommodation') {
    $room_type = trim($_POST['preferred_room_type'] ?? 'Double (Non-AC)');
    $block = trim($_POST['preferred_block'] ?? 'Block A (Aryabhata)');
    $dietary = trim($_POST['dietary_preference'] ?? 'Vegetarian');
    $remarks = trim($_POST['remarks'] ?? '');

    // Check for existing pending application
    $stmt = $db->prepare("SELECT id FROM applications WHERE student_id = ? AND status = 'Pending'");
    $stmt->execute([$student_id]);
    if ($stmt->fetch()) {
        set_flash('warning', 'You already have an accommodation application pending review.');
    } else {
        $stmt = $db->prepare("INSERT INTO applications (student_id, preferred_room_type, preferred_block, dietary_preference, remarks, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$student_id, $room_type, $block, $dietary, $remarks]);
        
        // Notify warden
        $warden_id = $db->query("SELECT id FROM users WHERE role = 'warden' LIMIT 1")->fetchColumn();
        if ($warden_id) {
            create_notification($warden_id, 'New Accommodation Application', "Student {$student['reg_no']} applied for {$room_type} in {$block}.", 'info', 'warden_applications.php');
        }

        set_flash('success', 'Hostel accommodation application submitted successfully! The Warden will review your request.');
    }
    header('Location: apply_room.php');
    exit;
}

// Fetch all application records for student
$stmt = $db->prepare("SELECT a.*, r.room_number as allocated_room_num 
                      FROM applications a 
                      LEFT JOIN rooms r ON a.allocated_room_id = r.id 
                      WHERE a.student_id = ? 
                      ORDER BY a.id DESC");
$stmt->execute([$student_id]);
$my_applications = $stmt->fetchAll();

$page_title = 'Apply for Room';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-blue-200 border border-white/20 mb-2">
                <i data-lucide="file-plus" class="w-3.5 h-3.5"></i> Hostel Accommodation (FR-05)
            </div>
            <h1 class="text-2xl font-black">Apply for Hostel Accommodation</h1>
            <p class="text-xs sm:text-sm text-blue-200 mt-1">Submit your room preferences, block choices, and track Warden approvals.</p>
        </div>
        <span class="px-3 py-1.5 rounded-xl font-bold text-xs <?= $student['room_number'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-amber-500/20 text-amber-300 border border-amber-400/30' ?>">
            <?= $student['room_number'] ? 'Room Allocated: Room ' . htmlspecialchars($student['room_number']) : 'No Room Allocated Yet' ?>
        </span>
    </div>

    <!-- Application Form Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <?php if (!$student['room_number']): ?>
            <h2 class="text-lg font-bold text-slate-900 mb-1">New Room Application</h2>
            <p class="text-xs text-slate-500 mb-6">Select your accommodation tier and preferences. Warden will review based on vacancy.</p>

            <form method="POST" action="apply_room.php" class="space-y-5">
                <input type="hidden" name="action" value="apply_accommodation">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Preferred Room Type *</label>
                        <select name="preferred_room_type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="Single (AC)">Single Room (AC) - ₹45,000/sem</option>
                            <option value="Double (AC)">Double Sharing (AC) - ₹38,000/sem</option>
                            <option value="Double (Non-AC)">Double Sharing (Non-AC) - ₹30,000/sem</option>
                            <option value="Triple (Non-AC)">Triple Sharing (Non-AC) - ₹25,000/sem</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Preferred Hostel Block *</label>
                        <select name="preferred_block" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="Block A (Aryabhata)">Block A (Aryabhata) - Senior Wing</option>
                            <option value="Block B (Bhaskara)">Block B (Bhaskara) - Study Wing</option>
                            <option value="Block C (Chanakya)">Block C (Chanakya) - East Wing</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Dietary Preference</label>
                        <select name="dietary_preference" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="Vegetarian">Vegetarian (North/South Indian)</option>
                            <option value="Non-Vegetarian">Non-Vegetarian</option>
                            <option value="Jain Food">Jain Vegetarian</option>
                            <option value="Eggetarian">Eggetarian</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Special Medical / Accessibility Remarks</label>
                        <input type="text" name="remarks" placeholder="e.g. Ground floor preferred due to knee issue" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i> Submit Accommodation Application
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="p-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center">
                        <i data-lucide="check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base">You are an Active Resident</h3>
                        <p class="text-xs text-emerald-700 mt-0.5">Currently assigned to <strong>Room <?= htmlspecialchars($student['room_number']) ?></strong> in <strong><?= htmlspecialchars($student['block']) ?></strong>.</p>
                    </div>
                </div>
                <p class="text-xs text-emerald-700 mt-3 pt-3 border-t border-emerald-200">If you wish to apply for a room transfer or change sharing tiers, please consult the Hostel Warden office.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Application Decision History (FR-06) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h3 class="text-base font-bold text-slate-900 mb-1">Application Decision History</h3>
        <p class="text-xs text-slate-500 mb-6">Track approval decisions and review remarks from the Warden.</p>

        <?php if (!empty($my_applications)): ?>
            <div class="space-y-4">
                <?php foreach ($my_applications as $app): ?>
                    <div class="p-4 rounded-xl border <?= $app['status'] === 'Approved' ? 'bg-emerald-50/50 border-emerald-200' : ($app['status'] === 'Rejected' ? 'bg-red-50/50 border-red-200' : 'bg-amber-50/50 border-amber-200') ?>">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="font-bold text-sm text-slate-900">Application #<?= $app['id'] ?></span>
                                <p class="text-xs text-slate-600 mt-0.5">Requested: <strong><?= htmlspecialchars($app['preferred_room_type']) ?></strong> in <strong><?= htmlspecialchars($app['preferred_block']) ?></strong></p>
                            </div>
                            <span class="badge-status <?= $app['status'] === 'Approved' ? 'badge-resolved' : ($app['status'] === 'Rejected' ? 'badge-escalated' : 'badge-pending') ?>">
                                <?= htmlspecialchars($app['status']) ?>
                            </span>
                        </div>

                        <?php if ($app['remarks']): ?>
                            <p class="text-xs text-slate-500 mt-2"><strong>Your Notes:</strong> <?= htmlspecialchars($app['remarks']) ?></p>
                        <?php endif; ?>

                        <?php if ($app['review_notes']): ?>
                            <div class="mt-3 p-3 bg-white rounded-lg border border-slate-200 text-xs">
                                <span class="font-bold text-slate-800">Warden Review Notes:</span>
                                <p class="text-slate-600 mt-0.5"><?= htmlspecialchars($app['review_notes']) ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="text-[10px] text-slate-400 mt-2">
                            Submitted on <?= date('d M Y, h:i A', strtotime($app['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-xs text-slate-500 italic">No past applications found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
