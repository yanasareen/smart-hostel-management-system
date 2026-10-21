<?php
/**
 * Smart Hostel Complaint Management System
 * Student & Public - Room Availability Explorer (FR-08)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$db = get_db();

// Filters
$filter_block = trim($_GET['block'] ?? '');
$filter_type = trim($_GET['type'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM rooms WHERE 1=1 ";
$params = [];

if (!empty($filter_block)) {
    $sql .= " AND block LIKE ? ";
    $params[] = "%$filter_block%";
}
if (!empty($filter_type)) {
    $sql .= " AND room_type LIKE ? ";
    $params[] = "%$filter_type%";
}
if ($filter_status === 'available') {
    $sql .= " AND status = 'Available' AND (capacity - occupied_beds) > 0 ";
} elseif (!empty($filter_status)) {
    $sql .= " AND status = ? ";
    $params[] = $filter_status;
}

$sql .= " ORDER BY block, floor, room_number";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Summary counters
$total_rooms = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$total_vacant_beds = $db->query("SELECT SUM(capacity - occupied_beds) FROM rooms WHERE status = 'Available'")->fetchColumn() ?: 0;

$page_title = 'Available Rooms';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-700 via-indigo-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-blue-200 border border-white/20 mb-2">
                <i data-lucide="door-open" class="w-3.5 h-3.5"></i> Room Inventory & Vacancy (FR-08)
            </div>
            <h1 class="text-2xl font-black">Hostel Room Availability</h1>
            <p class="text-xs sm:text-sm text-blue-200 mt-1">Explore available blocks, bed vacancies, and room amenities in real time.</p>
        </div>

        <div class="flex items-center gap-4 bg-white/10 px-4 py-3 rounded-2xl border border-white/10 backdrop-blur-sm">
            <div class="text-center">
                <span class="block text-2xl font-black"><?= $total_rooms ?></span>
                <span class="text-[10px] text-blue-200 uppercase font-semibold">Total Rooms</span>
            </div>
            <div class="h-8 w-px bg-white/20"></div>
            <div class="text-center">
                <span class="block text-2xl font-black text-emerald-300"><?= $total_vacant_beds ?></span>
                <span class="text-[10px] text-blue-200 uppercase font-semibold">Vacant Beds</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <form method="GET" action="available_rooms.php" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs items-end">
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Hostel Block</label>
                <select name="block" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                    <option value="">All Blocks</option>
                    <option value="Block A" <?= strpos($filter_block, 'Block A') !== false ? 'selected' : '' ?>>Block A (Aryabhata)</option>
                    <option value="Block B" <?= strpos($filter_block, 'Block B') !== false ? 'selected' : '' ?>>Block B (Bhaskara)</option>
                    <option value="Block C" <?= strpos($filter_block, 'Block C') !== false ? 'selected' : '' ?>>Block C (Chanakya)</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Room Sharing Type</label>
                <select name="type" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                    <option value="">All Room Types</option>
                    <option value="Single" <?= strpos($filter_type, 'Single') !== false ? 'selected' : '' ?>>Single Room (AC)</option>
                    <option value="Double" <?= strpos($filter_type, 'Double') !== false ? 'selected' : '' ?>>Double Sharing</option>
                    <option value="Triple" <?= strpos($filter_type, 'Triple') !== false ? 'selected' : '' ?>>Triple Sharing</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Vacancy Status</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                    <option value="">All Statuses</option>
                    <option value="available" <?= $filter_status === 'available' ? 'selected' : '' ?>>Only Available / Vacant Beds</option>
                    <option value="Full" <?= $filter_status === 'Full' ? 'selected' : '' ?>>Full / Occupied</option>
                    <option value="Under Maintenance" <?= $filter_status === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition flex items-center justify-center gap-1.5">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i> Apply Filter
                </button>
                <?php if ($filter_block || $filter_type || $filter_status): ?>
                    <a href="available_rooms.php" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl" title="Clear Filters">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Room Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($rooms)): ?>
            <?php foreach ($rooms as $r): ?>
                <?php 
                    $vacant = $r['capacity'] - $r['occupied_beds'];
                    $is_available = ($r['status'] === 'Available' && $vacant > 0);
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm card-hover flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?= htmlspecialchars($r['block']) ?></span>
                                <h3 class="text-xl font-bold text-slate-900 mt-0.5">Room <?= htmlspecialchars($r['room_number']) ?></h3>
                            </div>
                            <span class="badge-status <?= $is_available ? 'badge-resolved' : ($r['status'] === 'Under Maintenance' ? 'badge-escalated' : 'badge-pending') ?>">
                                <?= $is_available ? "$vacant Vacant Bed(s)" : htmlspecialchars($r['status']) ?>
                            </span>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Tier:</span>
                                <strong class="text-slate-900"><?= htmlspecialchars($r['room_type']) ?></strong>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Floor:</span>
                                <strong class="text-slate-800"><?= $r['floor'] ?> Floor</strong>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Occupancy:</span>
                                <strong class="text-slate-800"><?= $r['occupied_beds'] ?> / <?= $r['capacity'] ?> Beds</strong>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Semester Fee:</span>
                                <strong class="text-blue-600 font-bold"><?= CURRENCY_SYMBOL ?><?= number_format($r['fee_per_semester'], 2) ?></strong>
                            </div>
                        </div>

                        <?php if ($r['amenities']): ?>
                            <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                                <i data-lucide="sparkle" class="w-3 h-3 inline text-amber-500 mr-1"></i>
                                <?= htmlspecialchars($r['amenities']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end">
                        <a href="apply_room.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs transition">
                            Apply for Room <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full p-12 text-center bg-white rounded-2xl border border-slate-200">
                <i data-lucide="search-x" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                <p class="text-sm font-bold text-slate-700">No rooms match your filter criteria.</p>
                <a href="available_rooms.php" class="mt-2 inline-block text-xs text-blue-600 hover:underline">Reset filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
