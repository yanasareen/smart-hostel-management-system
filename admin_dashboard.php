<?php
/**
 * Smart Hostel Complaint Management System
 * Administrator Dashboard & Central Analytics (FR-27, FR-28)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role('admin');

$db = get_db();
$admin_user = current_user();

run_sla_escalation_check();

// 1. Complaint Metrics (FR-27)
$total_cmps = $db->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pending_cmps = $db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();
$in_prog_cmps = $db->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetchColumn();
$resolved_cmps = $db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
$escalated_cmps = $db->query("SELECT COUNT(*) FROM complaints WHERE is_escalated = 1 AND status != 'Resolved'")->fetchColumn();

// 2. Category Distribution
$cat_breakdown = $db->query("SELECT category, COUNT(*) as count FROM complaints GROUP BY category ORDER BY count DESC")->fetchAll();
$all_cats = ['Electricity' => 0, 'Plumbing' => 0, 'Wi-Fi' => 0, 'Cleaning' => 0, 'Furniture' => 0, 'Other' => 0];
foreach ($cat_breakdown as $cb) {
    if (isset($all_cats[$cb['category']])) {
        $all_cats[$cb['category']] = (int)$cb['count'];
    }
}

// 3. Satisfaction Ratings (FR-20, FR-27)
$avg_rating = $db->query("SELECT AVG(rating) FROM complaint_feedback")->fetchColumn() ?: 5.0;
$total_feedbacks = $db->query("SELECT COUNT(*) FROM complaint_feedback")->fetchColumn();

// 4. Room Occupancy & Capacity (FR-28)
$total_capacity = $db->query("SELECT SUM(capacity) FROM rooms")->fetchColumn() ?: 1;
$total_occupied = $db->query("SELECT SUM(occupied_beds) FROM rooms")->fetchColumn() ?: 0;
$occupancy_rate = ($total_occupied / $total_capacity) * 100;

// 5. Total Financials (FR-28)
$total_revenue = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'Paid'")->fetchColumn() ?: 0;

$page_title = 'Admin Analytics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-400/30 mb-3 backdrop-blur-sm">
                <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i> Administrator Analytics Command (FR-27)
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Hostel Operations Analytics</h1>
            <p class="text-xs sm:text-sm text-purple-200 mt-1">
                Real-time service health, resolution satisfaction metrics, room inventory control, and reports.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="admin_students.php" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md transition flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4"></i> Student Records
            </a>
            <a href="admin_reports.php" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/20 transition flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Generate Reports
            </a>
        </div>
    </div>

    <!-- Analytics KPI Cards (FR-27) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="clipboard-check" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Complaints Made</span>
                <p class="text-2xl font-black text-slate-900"><?= $total_cmps ?></p>
                <span class="text-xs text-blue-600 font-medium"><?= $pending_cmps ?> Pending • <?= $in_prog_cmps ?> In Progress</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Resolution Rate</span>
                <p class="text-2xl font-black text-emerald-600">
                    <?= $total_cmps > 0 ? round(($resolved_cmps / $total_cmps) * 100) : 100 ?>%
                </p>
                <span class="text-xs text-amber-500 font-bold"><?= number_format($avg_rating, 1) ?> ★ (<?= $total_feedbacks ?> reviews)</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="bed-double" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Room Occupancy Rate</span>
                <p class="text-2xl font-black text-purple-600"><?= round($occupancy_rate) ?>%</p>
                <a href="admin_rooms.php" class="text-xs text-slate-500 hover:underline"><?= $total_occupied ?> / <?= $total_capacity ?> Beds Occupied &rarr;</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Fee Collections</span>
                <p class="text-2xl font-black text-slate-900"><?= CURRENCY_SYMBOL ?><?= number_format($total_revenue, 0) ?></p>
                <a href="admin_reports.php?report_type=payments" class="text-xs text-emerald-600 font-medium hover:underline">Financial report &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Visual Bar Graphs Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Bar Chart: Complaint Resolution Status -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Hostel Complaints & Resolution Bar Graph (FR-27)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Total complaints, resolved issues, active repairs, and SLA escalations</p>
                </div>
            </div>

            <div class="relative h-72 w-full pt-2">
                <canvas id="adminBarChart"></canvas>
            </div>
        </div>

        <!-- Doughnut Chart: Categories -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base mb-1">Category Distribution</h3>
                <p class="text-xs text-slate-500 mb-4">Breakdown across infrastructure areas</p>
                <div class="relative h-56 w-full flex items-center justify-center">
                    <canvas id="adminCategoryChart"></canvas>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 text-center">
                <a href="admin_reports.php?report_type=complaints" class="text-xs font-semibold text-purple-700 hover:underline">
                    Generate Complaints Audit &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Hub -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <a href="admin_students.php" class="p-6 rounded-2xl bg-white border border-slate-200 hover:border-purple-500 hover:shadow-md transition group text-left">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <h4 class="font-bold text-base text-slate-900">Student Records Management</h4>
            <p class="text-xs text-slate-500 mt-1">Search, inspect profiles, emergency contacts, and manage active resident statuses</p>
        </a>

        <a href="admin_rooms.php" class="p-6 rounded-2xl bg-white border border-slate-200 hover:border-purple-500 hover:shadow-md transition group text-left">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                <i data-lucide="door-closed" class="w-6 h-6"></i>
            </div>
            <h4 class="font-bold text-base text-slate-900">Room Inventory Controller</h4>
            <p class="text-xs text-slate-500 mt-1">Add new living units, adjust bed capacities, and set maintenance blocks</p>
        </a>

        <a href="admin_reports.php" class="p-6 rounded-2xl bg-white border border-slate-200 hover:border-purple-500 hover:shadow-md transition group text-left">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                <i data-lucide="file-spreadsheet" class="w-6 h-6"></i>
            </div>
            <h4 class="font-bold text-base text-slate-900">Administrative Reports Generator</h4>
            <p class="text-xs text-slate-500 mt-1">Export date-filtered summaries of complaints, room occupancy, and fee collections</p>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Admin Bar Chart
    const ctxBar = document.getElementById('adminBarChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Total Made', 'Resolved', 'In Progress', 'Pending', 'Escalated (SLA)'],
            datasets: [{
                label: 'System Complaints',
                data: [<?= $total_cmps ?>, <?= $resolved_cmps ?>, <?= $in_prog_cmps ?>, <?= $pending_cmps ?>, <?= $escalated_cmps ?>],
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

    // Admin Category Chart
    const ctxCat = document.getElementById('adminCategoryChart').getContext('2d');
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
