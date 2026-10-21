<?php
/**
 * Smart Hostel Complaint Management System
 * Administrator - Room Inventory & Facility Controller (FR-08)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role('admin');

$db = get_db();

// Handle Room Actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add Room
    if ($action === 'add_room') {
        $room_number = trim($_POST['room_number'] ?? '');
        $block = trim($_POST['block'] ?? 'Block A (Aryabhata)');
        $floor = intval($_POST['floor'] ?? 1);
        $room_type = trim($_POST['room_type'] ?? 'Double (Non-AC)');
        $capacity = intval($_POST['capacity'] ?? 2);
        $fee = floatval($_POST['fee_per_semester'] ?? 30000);
        $amenities = trim($_POST['amenities'] ?? '');

        if (!empty($room_number) && $capacity > 0) {
            try {
                $stmt = $db->prepare("INSERT INTO rooms (room_number, block, floor, room_type, capacity, fee_per_semester, status, amenities) VALUES (?, ?, ?, ?, ?, ?, 'Available', ?)");
                $stmt->execute([$room_number, $block, $floor, $room_type, $capacity, $fee, $amenities]);
                set_flash('success', "Room $room_number ($block) added to hostel inventory successfully!");
            } catch (Throwable $e) {
                set_flash('danger', "Error adding room: " . $e->getMessage());
            }
        }
        header('Location: admin_rooms.php');
        exit;
    }

    // Toggle Room Status
    if ($action === 'toggle_room_status') {
        $room_id = intval($_POST['room_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? 'Available';
        if ($room_id > 0) {
            $stmt = $db->prepare("UPDATE rooms SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $room_id]);
            set_flash('success', "Room status updated to '$new_status'.");
        }
        header('Location: admin_rooms.php');
        exit;
    }
}

// Fetch Rooms
$rooms_data = $db->query("SELECT * FROM rooms ORDER BY block, room_number")->fetchAll();

$page_title = 'Room Inventory';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-400/30 mb-2">
                <i data-lucide="door-closed" class="w-3.5 h-3.5"></i> Inventory Control (FR-08)
            </div>
            <h1 class="text-2xl font-black">Hostel Room Inventory & Facility Controller</h1>
            <p class="text-xs sm:text-sm text-purple-200 mt-1">Add new living units, toggle maintenance status, and view occupancy rates.</p>
        </div>

        <button onclick="openModal('addRoomModal')" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md transition flex items-center gap-2 flex-shrink-0">
            <i data-lucide="plus-circle" class="w-4 h-4"></i> Add New Room
        </button>
    </div>

    <!-- Rooms Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($rooms_data as $rm): ?>
            <?php 
                $vacant = $rm['capacity'] - $rm['occupied_beds'];
            ?>
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm text-xs flex flex-col justify-between card-hover">
                <div>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"><?= htmlspecialchars($rm['block']) ?></span>
                            <h3 class="font-black text-slate-900 text-lg">Room <?= htmlspecialchars($rm['room_number']) ?></h3>
                        </div>
                        <span class="badge-status <?= $rm['status'] === 'Available' ? 'badge-resolved' : ($rm['status'] === 'Under Maintenance' ? 'badge-escalated' : 'badge-pending') ?>">
                            <?= htmlspecialchars($rm['status']) ?>
                        </span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-slate-600">
                        <p><strong>Floor:</strong> <?= $rm['floor'] ?> Floor</p>
                        <p><strong>Tier:</strong> <span class="text-blue-600 font-bold"><?= htmlspecialchars($rm['room_type']) ?></span></p>
                        <p><strong>Fee:</strong> <?= CURRENCY_SYMBOL ?><?= number_format($rm['fee_per_semester'], 0) ?> / semester</p>
                        <p><strong>Occupancy:</strong> <?= $rm['occupied_beds'] ?> / <?= $rm['capacity'] ?> Beds (<?= $vacant ?> free)</p>
                    </div>

                    <?php if ($rm['amenities']): ?>
                        <div class="mt-3 text-[11px] text-slate-500 italic">
                            <?= htmlspecialchars($rm['amenities']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end">
                    <form method="POST" action="admin_rooms.php">
                        <input type="hidden" name="action" value="toggle_room_status">
                        <input type="hidden" name="room_id" value="<?= $rm['id'] ?>">
                        <input type="hidden" name="new_status" value="<?= $rm['status'] === 'Under Maintenance' ? 'Available' : 'Under Maintenance' ?>">
                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition">
                            <?= $rm['status'] === 'Under Maintenance' ? 'Mark Available' : 'Set Under Maintenance' ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Add New Room (FR-08) -->
<div id="addRoomModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl modal-content">
        <div class="flex justify-between items-start border-b border-slate-100 pb-3 mb-4">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Add New Room to Inventory</h3>
                <p class="text-xs text-slate-500 mt-0.5">Register room capacity and fee rate (FR-08)</p>
            </div>
            <button onclick="closeModal('addRoomModal')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="admin_rooms.php">
            <input type="hidden" name="action" value="add_room">

            <div class="space-y-4 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Room Number *</label>
                        <input type="text" name="room_number" required placeholder="e.g. 305" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Floor</label>
                        <input type="number" name="floor" value="3" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Hostel Block *</label>
                    <select name="block" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                        <option value="Block A (Aryabhata)">Block A (Aryabhata)</option>
                        <option value="Block B (Bhaskara)">Block B (Bhaskara)</option>
                        <option value="Block C (Chanakya)">Block C (Chanakya)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Room Type</label>
                        <select name="room_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white">
                            <option value="Single (AC)">Single (AC)</option>
                            <option value="Double (AC)">Double (AC)</option>
                            <option value="Double (Non-AC)">Double (Non-AC)</option>
                            <option value="Triple (Non-AC)">Triple (Non-AC)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Bed Capacity *</label>
                        <input type="number" name="capacity" value="2" min="1" max="6" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Semester Fee (₹) *</label>
                    <input type="number" step="0.01" name="fee_per_semester" value="35000" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Amenities</label>
                    <input type="text" name="amenities" placeholder="e.g. Study Tables, Attached Washroom, Balcony" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeModal('addRoomModal')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-sm">Save Room</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
