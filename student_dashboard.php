<?php
/**
 * Smart Hostel Complaint Management System
 * Student Portal - Interactive Analytics Dashboard (FR-01, FR-14, FR-20, FR-27)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role('student');

$db = get_db();
$user_id = current_user_id();

// 1. Fetch Student Profile
$stmt = $db->prepare("SELECT s.*, r.room_number, r.block, r.floor, r.room_type, r.fee_per_semester, r.amenities 
                      FROM students s 
                      LEFT JOIN rooms r ON s.room_id = r.id 
                      WHERE s.user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    $u = current_user();
    $stmt = $db->prepare("INSERT INTO students (user_id, reg_no, department, year, gender, status) VALUES (?, ?, 'Engineering', '1st Year', 'Other', 'Registered')");
    $stmt->execute([$user_id, $u['reg_no'] ?? 'REG-NEW']);
    header('Location: student_dashboard.php');
    exit;
}

$student_id = $student['id'];

// 2. Roommates
$roommates = [];
if ($student['room_id']) {
    $stmt = $db->prepare("SELECT u.name, u.phone, s.reg_no, s.department, s.year 
                          FROM students s 
                          JOIN users u ON s.user_id = u.id 
                          WHERE s.room_id = ? AND s.id != ?");
    $stmt->execute([$student['room_id'], $student_id]);
    $roommates = $stmt->fetchAll();
}

// 3. Complaint Metrics for Visual Bar Graph
$total_cmps = $db->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ?");
$total_cmps->execute([$student_id]);
$cnt_total = $total_cmps->fetchColumn();

$res_cmps = $db->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status = 'Resolved'");
$res_cmps->execute([$student_id]);
$cnt_resolved = $res_cmps->fetchColumn();

$prog_cmps = $db->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status = 'In Progress'");
$prog_cmps->execute([$student_id]);
$cnt_in_prog = $prog_cmps->fetchColumn();

$pend_cmps = $db->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status = 'Pending'");
$pend_cmps->execute([$student_id]);
$cnt_pending = $pend_cmps->fetchColumn();

$esc_cmps = $db->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND is_escalated = 1");
$esc_cmps->execute([$student_id]);
$cnt_escalated = $esc_cmps->fetchColumn();

// 4. Complaints by Category
$cat_stmt = $db->prepare("SELECT category, COUNT(*) as count FROM complaints WHERE student_id = ? GROUP BY category");
$cat_stmt->execute([$student_id]);
$category_data = $cat_stmt->fetchAll();

// Category dictionary for chart
$all_cats = ['Electricity' => 0, 'Plumbing' => 0, 'Wi-Fi' => 0, 'Cleaning' => 0, 'Furniture' => 0, 'Other' => 0];
foreach ($category_data as $cd) {
    if (isset($all_cats[$cd['category']])) {
        $all_cats[$cd['category']] = (int)$cd['count'];
    }
}

// 5. Recent Complaints
$stmt = $db->prepare("SELECT * FROM complaints WHERE student_id = ? ORDER BY id DESC LIMIT 5");
$stmt->execute([$student_id]);
$recent_complaints = $stmt->fetchAll();

// 6. Recent Notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 4");
$stmt->execute([$user_id]);
$recent_notifications = $stmt->fetchAll();

$page_title = 'Student Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-blue-200 border border-white/20 mb-3 backdrop-blur-sm">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Student Command & Analytics Dashboard
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Welcome, <?= htmlspecialchars(current_user()['name']) ?></h1>
            <p class="text-xs sm:text-sm text-blue-200 mt-1">
                Reg No: <strong class="text-white"><?= htmlspecialchars($student['reg_no']) ?></strong> • 
                Department: <strong class="text-white"><?= htmlspecialchars($student['department']) ?></strong> • 
                Year: <strong class="text-white"><?= htmlspecialchars($student['year']) ?></strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="my_complaints.php" class="px-4 py-2.5 rounded-xl bg-white text-blue-900 hover:bg-blue-50 font-bold text-xs shadow-md transition flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-blue-600"></i> File Maintenance Complaint
            </a>
            <a href="apply_room.php" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/20 transition flex items-center gap-2">
                <i data-lucide="home" class="w-4 h-4"></i> Room Application
            </a>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Complaints -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Complaints Made</span>
                <p class="text-2xl font-black text-slate-900"><?= $cnt_total ?></p>
                <span class="text-xs text-blue-600 font-medium"><?= $cnt_pending ?> Pending Review</span>
            </div>
        </div>

        <!-- Resolved Complaints -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="check-circle-2" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Complaints Resolved</span>
                <p class="text-2xl font-black text-emerald-600"><?= $cnt_resolved ?></p>
                <span class="text-xs text-emerald-700 font-medium"><?= $cnt_total > 0 ? round(($cnt_resolved / $cnt_total) * 100) : 100 ?>% Resolution Rate</span>
            </div>
        </div>

        <!-- In Progress & Active -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Technician Dispatched</span>
                <p class="text-2xl font-black text-cyan-600"><?= $cnt_in_prog ?></p>
                <span class="text-xs text-slate-500">Currently in progress</span>
            </div>
        </div>

        <!-- Room Status -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="door-closed" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Allocated Room</span>
                <p class="text-xl font-black text-slate-900"><?= $student['room_number'] ? 'Room ' . htmlspecialchars($student['room_number']) : 'Not Assigned' ?></p>
                <span class="text-xs text-purple-600 font-medium"><?= $student['block'] ? htmlspecialchars($student['block']) : 'Apply in Room section' ?></span>
            </div>
        </div>
    </div>

    <!-- Interactive Visual Bar Graphs Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Bar Chart: Complaint Resolution Status -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Complaints Resolution Analytics (Bar Graph)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Visual distribution of complaints filed, resolved, in-progress, and escalated</p>
                </div>
                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-700">
                    Live Chart
                </span>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-72 w-full pt-2">
                <canvas id="complaintStatusBarChart"></canvas>
            </div>
        </div>

        <!-- Doughnut / Bar Chart: Categories Distribution -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Issue Categories</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Breakdown of reported issues</p>
                    </div>
                </div>

                <div class="relative h-60 w-full flex items-center justify-center">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 text-center">
                <a href="my_complaints.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center justify-center gap-1">
                    Manage Complaints <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Hub -->
    <div class="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-2xl p-6 text-white shadow-md">
        <h3 class="text-sm font-bold uppercase tracking-wider text-blue-300 mb-1">Direct Portal Navigation</h3>
        <p class="text-xs text-slate-400 mb-4">Click below to open dedicated pages with full state reload and separate tools:</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="apply_room.php" class="p-4 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 transition group text-left">
                <div class="w-9 h-9 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center mb-2 group-hover:scale-105 transition">
                    <i data-lucide="file-plus" class="w-5 h-5"></i>
                </div>
                <h4 class="font-bold text-sm text-white">Apply for Room</h4>
                <p class="text-[11px] text-slate-400 mt-1">Submit room preference form & inspect decision status</p>
            </a>

            <a href="available_rooms.php" class="p-4 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 transition group text-left">
                <div class="w-9 h-9 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-2 group-hover:scale-105 transition">
                    <i data-lucide="door-open" class="w-5 h-5"></i>
                </div>
                <h4 class="font-bold text-sm text-white">Available Rooms</h4>
                <p class="text-[11px] text-slate-400 mt-1">Explore all blocks, bed vacancies, and room amenities</p>
            </a>

            <a href="my_complaints.php" class="p-4 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 transition group text-left">
                <div class="w-9 h-9 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center mb-2 group-hover:scale-105 transition">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
                <h4 class="font-bold text-sm text-white">My Complaints</h4>
                <p class="text-[11px] text-slate-400 mt-1">Submit tickets with duplicate warnings & rate repairs</p>
            </a>

            <a href="my_fees.php" class="p-4 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 transition group text-left">
                <div class="w-9 h-9 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center mb-2 group-hover:scale-105 transition">
                    <i data-lucide="receipt" class="w-5 h-5"></i>
                </div>
                <h4 class="font-bold text-sm text-white">Fees & Receipts</h4>
                <p class="text-[11px] text-slate-400 mt-1">View payment statements and print official receipts</p>
            </a>
        </div>
    </div>

    <!-- Room Details & Roommates -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-900 text-base mb-4">My Allocated Room & Living Space</h3>
            <?php if ($student['room_number']): ?>
                <div class="space-y-4 text-xs">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Room Number</span>
                            <span class="text-lg font-black text-slate-900">Room <?= htmlspecialchars($student['room_number']) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Hostel Block</span>
                            <span class="text-sm font-bold text-slate-800"><?= htmlspecialchars($student['block']) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Floor</span>
                            <span class="text-sm font-bold text-slate-800"><?= $student['floor'] ?> Floor</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Room Type</span>
                            <span class="text-sm font-bold text-blue-600"><?= htmlspecialchars($student['room_type']) ?></span>
                        </div>
                    </div>

                    <?php if ($student['amenities']): ?>
                        <div class="p-3 rounded-xl bg-blue-50/50 border border-blue-100 text-slate-700">
                            <strong>Room Amenities:</strong> <?= htmlspecialchars($student['amenities']) ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[11px] mb-2">Roommates</h4>
                        <?php if (!empty($roommates)): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <?php foreach ($roommates as $rm): ?>
                                    <div class="p-3 rounded-xl border border-slate-200 bg-white flex items-center justify-between">
                                        <div>
                                            <p class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($rm['name']) ?></p>
                                            <p class="text-[11px] text-slate-500"><?= htmlspecialchars($rm['department']) ?> (<?= htmlspecialchars($rm['reg_no']) ?>)</p>
                                        </div>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($rm['phone']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-slate-400 italic text-xs">No other roommates assigned to this room yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-8 text-center text-xs text-slate-500">
                    <i data-lucide="home" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                    <p class="font-semibold text-slate-700">No room currently allocated.</p>
                    <p class="mt-1">Submit an accommodation application to get assigned by the Warden.</p>
                    <a href="apply_room.php" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition">
                        <i data-lucide="file-plus" class="w-4 h-4"></i> Apply Now
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent In-App Notifications -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-900 text-base mb-4">Recent Updates</h3>
            <div class="space-y-3 text-xs">
                <?php if (!empty($recent_notifications)): ?>
                    <?php foreach ($recent_notifications as $rn): ?>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-2.5">
                            <i data-lucide="<?= $rn['type'] === 'success' ? 'check-circle' : ($rn['type'] === 'warning' ? 'alert-triangle' : 'info') ?>" class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($rn['title']) ?></p>
                                <p class="text-slate-600 mt-0.5 leading-relaxed"><?= htmlspecialchars($rn['message']) ?></p>
                                <span class="text-[10px] text-slate-400 mt-1 block"><?= time_ago($rn['created_at']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-slate-400 italic">No recent updates.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script to Render Visual Bar Graphs -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Complaint Status Overview Bar Chart
    const ctxBar = document.getElementById('complaintStatusBarChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Total Made', 'Resolved', 'In Progress', 'Pending', 'Escalated (SLA)'],
            datasets: [{
                label: 'Complaints Count',
                data: [<?= $cnt_total ?>, <?= $cnt_resolved ?>, <?= $cnt_in_prog ?>, <?= $cnt_pending ?>, <?= $cnt_escalated ?>],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.85)',   // Blue for Total
                    'rgba(16, 185, 129, 0.85)',   // Emerald for Resolved
                    'rgba(6, 182, 212, 0.85)',    // Cyan for In Progress
                    'rgba(245, 158, 11, 0.85)',   // Amber for Pending
                    'rgba(239, 68, 68, 0.85)'     // Red for Escalated
                ],
                borderColor: [
                    '#2563eb',
                    '#059669',
                    '#0891b2',
                    '#d97706',
                    '#dc2626'
                ],
                borderWidth: 1.5,
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
                        label: function(context) {
                            return ' ' + context.parsed.y + ' Complaint(s)';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Categories Distribution Chart
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: ['Electricity', 'Plumbing', 'Wi-Fi', 'Cleaning', 'Furniture', 'Other'],
            datasets: [{
                data: [
                    <?= $all_cats['Electricity'] ?>,
                    <?= $all_cats['Plumbing'] ?>,
                    <?= $all_cats['Wi-Fi'] ?>,
                    <?= $all_cats['Cleaning'] ?>,
                    <?= $all_cats['Furniture'] ?>,
                    <?= $all_cats['Other'] ?>
                ],
                backgroundColor: [
                    '#f59e0b', // Electricity (Amber)
                    '#3b82f6', // Plumbing (Blue)
                    '#06b6d4', // Wi-Fi (Cyan)
                    '#10b981', // Cleaning (Green)
                    '#8b5cf6', // Furniture (Purple)
                    '#94a3b8'  // Other (Slate)
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, font: { size: 10 } }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
