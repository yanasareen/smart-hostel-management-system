<?php
/**
 * Smart Hostel Complaint Management System
 * Warden Operations Overview & Analytics Hub (FR-06 to FR-19)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role(['warden', 'admin']);

$db = get_db();
$warden_user = current_user();

run_sla_escalation_check();

// Operational Metrics
$count_pending_apps = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")->fetchColumn();
$count_pending_cmps = $db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();
$count_in_progress_cmps = $db->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetchColumn();
$count_resolved_cmps = $db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
$count_escalated_cmps = $db->query("SELECT COUNT(*) FROM complaints WHERE is_escalated = 1 AND status != 'Resolved'")->fetchColumn();
$count_total_cmps = $db->query("SELECT COUNT(*) FROM complaints")->fetchColumn();

// Fetch Escalated Tickets
$escalated_tickets = $db->query("SELECT c.*, s.reg_no, u.name as student_name, u.phone as student_phone, r.room_number 
                                 FROM complaints c 
                                 JOIN students s ON c.student_id = s.id 
                                 JOIN users u ON s.user_id = u.id 
                                 LEFT JOIN rooms r ON c.room_id = r.id 
                                 WHERE c.is_escalated = 1 AND c.status != 'Resolved' 
                                 ORDER BY c.id DESC")->fetchAll();

// Category distribution
$cat_data = $db->query("SELECT category, COUNT(*) as count FROM complaints GROUP BY category")->fetchAll();
$all_cats = ['Electricity' => 0, 'Plumbing' => 0, 'Wi-Fi' => 0, 'Cleaning' => 0, 'Furniture' => 0, 'Other' => 0];
foreach ($cat_data as $cd) {
    if (isset($all_cats[$cd['category']])) {
        $all_cats[$cd['category']] = (int)$cd['count'];
    }
}

// Available Rooms count
$available_rooms_count = $db->query("SELECT COUNT(*) FROM rooms WHERE status != 'Under Maintenance' AND (capacity - occupied_beds) > 0")->fetchColumn();

$page_title = 'Warden Overview';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-800 via-teal-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 mb-3 backdrop-blur-sm">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Chief Warden Command Center
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Warden Operations & Analytics</h1>
            <p class="text-xs sm:text-sm text-emerald-200 mt-1">
                Overseeing applications, room capacity allocation, arrival check-ins, and SLA escalations.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="warden_applications.php" class="px-4 py-2.5 rounded-xl bg-white text-emerald-900 hover:bg-emerald-50 font-bold text-xs shadow-md transition flex items-center gap-2">
                <i data-lucide="file-check" class="w-4 h-4 text-emerald-600"></i> Review Applications (<?= $count_pending_apps ?>)
            </a>
            <a href="warden_complaints.php" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/20 transition flex items-center gap-2">
                <i data-lucide="wrench" class="w-4 h-4"></i> Manage Complaints
            </a>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Applications Pending</span>
                <p class="text-2xl font-black text-slate-900"><?= $count_pending_apps ?></p>
                <a href="warden_applications.php" class="text-xs text-amber-600 font-medium hover:underline">Review queue &rarr;</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pending Complaints</span>
                <p class="text-2xl font-black text-slate-900"><?= $count_pending_cmps ?></p>
                <span class="text-xs text-blue-600 font-medium"><?= $count_in_progress_cmps ?> In Progress</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">SLA Escalations</span>
                <p class="text-2xl font-black text-red-600"><?= $count_escalated_cmps ?></p>
                <span class="text-xs text-red-600 font-medium">Overdue unresolved tickets</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="door-open" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Available Rooms</span>
                <p class="text-2xl font-black text-emerald-600"><?= $available_rooms_count ?></p>
                <a href="warden_allocation.php" class="text-xs text-emerald-600 font-medium hover:underline">Allocate &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Visual Bar Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Bar Chart: Complaint Resolution Status -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Hostel Complaints Overview (Bar Graph)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">System-wide complaint distribution across all resident blocks</p>
                </div>
            </div>

            <div class="relative h-72 w-full pt-2">
                <canvas id="wardenBarChart"></canvas>
            </div>
        </div>

        <!-- Doughnut Chart: Categories -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base mb-1">Issue Categories Breakdown</h3>
                <p class="text-xs text-slate-500 mb-4">High volume failure areas</p>
                <div class="relative h-56 w-full flex items-center justify-center">
                    <canvas id="wardenCategoryChart"></canvas>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 text-center">
                <a href="warden_complaints.php" class="text-xs font-semibold text-emerald-700 hover:underline">
                    View & Dispatch Complaints &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Escalated SLA Tickets Panel -->
    <?php if (!empty($escalated_tickets)): ?>
        <div class="p-6 rounded-2xl bg-gradient-to-r from-red-50 to-orange-50 border-2 border-red-300 shadow-md">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center shadow-md">
                    <i data-lucide="flame" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-red-900">Urgent: SLA Escalated Complaints Matrix (FR-19)</h3>
                    <p class="text-xs text-red-700">These maintenance issues have remained unresolved beyond the time limit and require emergency attention.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($escalated_tickets as $esc): ?>
                    <div class="p-4 rounded-xl bg-white border border-red-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <span class="font-mono font-bold text-red-700 text-xs">#<?= htmlspecialchars($esc['ticket_id']) ?></span>
                                <span class="badge-status badge-escalated">OVERDUE SLA</span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm mt-1"><?= htmlspecialchars($esc['title']) ?></h4>
                            <p class="text-xs text-slate-600 mt-1"><?= htmlspecialchars($esc['description']) ?></p>
                            <div class="mt-2 text-[11px] text-slate-500">
                                <strong>Location:</strong> <?= htmlspecialchars($esc['location_details'] ?: 'Room ' . $esc['room_number']) ?> • 
                                <strong>Student:</strong> <?= htmlspecialchars($esc['student_name']) ?> (<?= htmlspecialchars($esc['student_phone']) ?>)
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end">
                            <a href="warden_complaints.php" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold transition">
                                Resolve in Complaints Hub
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navigation Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="warden_applications.php" class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group text-left">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                <i data-lucide="file-check" class="w-5 h-5"></i>
            </div>
            <h4 class="font-bold text-sm text-slate-900">Accommodation Applications</h4>
            <p class="text-xs text-slate-500 mt-1">Review student preferences and approve accommodation requests</p>
        </a>

        <a href="warden_allocation.php" class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group text-left">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                <i data-lucide="bed" class="w-5 h-5"></i>
            </div>
            <h4 class="font-bold text-sm text-slate-900">Room Allocation</h4>
            <p class="text-xs text-slate-500 mt-1">Assign approved students to vacant beds with capacity checks</p>
        </a>

        <a href="warden_checkin.php" class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group text-left">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                <i data-lucide="log-in" class="w-5 h-5"></i>
            </div>
            <h4 class="font-bold text-sm text-slate-900">Check-in / Check-out</h4>
            <p class="text-xs text-slate-500 mt-1">Record arrival timestamps and process clearance on departure</p>
        </a>

        <a href="warden_complaints.php" class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group text-left">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                <i data-lucide="wrench" class="w-5 h-5"></i>
            </div>
            <h4 class="font-bold text-sm text-slate-900">Complaints Dispatch</h4>
            <p class="text-xs text-slate-500 mt-1">Update technician status, manage duplicates, and review ratings</p>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Warden Bar Chart
    const ctxBar = document.getElementById('wardenBarChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Total Filed', 'Resolved', 'In Progress', 'Pending', 'Escalated (SLA)'],
            datasets: [{
                label: 'Hostel Complaints',
                data: [<?= $count_total_cmps ?>, <?= $count_resolved_cmps ?>, <?= $count_in_progress_cmps ?>, <?= $count_pending_cmps ?>, <?= $count_escalated_cmps ?>],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.85)',
                    'rgba(16, 185, 129, 0.85)',
                    'rgba(6, 182, 212, 0.85)',
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(239, 68, 68, 0.85)'
                ],
                borderColor: ['#2563eb', '#059669', '#0891b2', '#d97706', '#dc2626'],
                borderWidth: 1.5,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Warden Category Doughnut Chart
    const ctxCat = document.getElementById('wardenCategoryChart').getContext('2d');
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
                backgroundColor: ['#f59e0b', '#3b82f6', '#06b6d4', '#10b981', '#8b5cf6', '#94a3b8'],
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
