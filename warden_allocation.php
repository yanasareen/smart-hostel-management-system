<?php
/**
 * Smart Hostel Complaint Management System
 * Warden - Capacity-Enforced Room Allocation (FR-07, BR-02)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role(['warden', 'admin']);

$db = get_db();

// Handle POST: Allocate Room (FR-07, BR-02)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'allocate_room') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $room_id = intval($_POST['room_id'] ?? 0);

    if ($student_id > 0 && $room_id > 0) {
        $stmt = $db->prepare("SELECT capacity, occupied_beds, status, room_number, block FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();

        if ($room && $room['status'] !== 'Under Maintenance' && $room['occupied_beds'] < $room['capacity']) {
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE students SET room_id = ?, status = 'Active Resident' WHERE id = ?");
            $stmt->execute([$room_id, $student_id]);

            $new_occ = $room['occupied_beds'] + 1;
            $new_status = ($new_occ >= $room['capacity']) ? 'Full' : 'Available';
            $stmt = $db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?");
            $stmt->execute([$new_occ, $new_status, $room_id]);

            // Notify Student
            $stmt = $db->prepare("SELECT user_id, reg_no FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $st = $stmt->fetch();
            if ($st) {
                create_notification($st['user_id'], "Room Allocated!", "You have been allocated Room {$room['room_number']} in {$room['block']}.", 'success', 'student_dashboard.php');
            }

            $db->commit();
            set_flash('success', "Room {$room['room_number']} allocated to Student (Reg: {$st['reg_no']}) successfully!");
        } else {
            set_flash('danger', 'Selected room is full or under maintenance (BR-02). Please choose an available room.');
        }
    }
    header('Location: warden_allocation.php');
    exit;
}

// Fetch approved students awaiting room allocation
$unallocated_students = $db->query("SELECT s.id, s.reg_no, s.department, u.name as student_name, a.preferred_room_type, a.preferred_block
                                     FROM students s
                                     JOIN users u ON s.user_id = u.id
                                     LEFT JOIN applications a ON s.id = a.student_id AND a.status = 'Approved'
                                     WHERE s.room_id IS NULL")->fetchAll();

// Fetch Available Rooms (with vacant beds)
$available_rooms = $db->query("SELECT * FROM rooms WHERE status != 'Under Maintenance' AND (capacity - occupied_beds) > 0 ORDER BY block, room_number")->fetchAll();

// Fetch currently allocated students
$allocated_residents = $db->query("SELECT s.*, u.name, u.phone, r.room_number, r.block, r.room_type 
                                  FROM students s 
                                  JOIN users u ON s.user_id = u.id 
                                  JOIN rooms r ON s.room_id = r.id 
                                  ORDER BY r.block, r.room_number")->fetchAll();

$page_title = 'Room Allocation';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="bg-gradient-to-r from-emerald-800 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 mb-2">
                <i data-lucide="bed" class="w-3.5 h-3.5"></i> Bed Capacity Control (FR-07, BR-02)
            </div>
            <h1 class="text-2xl font-black">Hostel Room Allocation</h1>
            <p class="text-xs sm:text-sm text-emerald-200 mt-1">Enforce room bed capacity. Only rooms with available bed space can be assigned.</p>
        </div>
        <span class="px-3.5 py-1.5 rounded-xl font-bold text-xs bg-white/10 text-emerald-200 border border-white/20">
            <?= count($unallocated_students) ?> Students Awaiting Allocation
        </span>
    </div>

    <!-- Allocation Form -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h3 class="text-base font-bold text-slate-900 mb-1">Assign Student to Room</h3>
        <p class="text-xs text-slate-500 mb-6">Select an approved student and match them to an available room with vacant capacity.</p>

        <?php if (!empty($unallocated_students) && !empty($available_rooms)): ?>
            <form method="POST" action="warden_allocation.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <input type="hidden" name="action" value="allocate_room">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Select Approved Student *</label>
                    <select name="student_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 bg-white">
                        <?php foreach ($unallocated_students as $us): ?>
                            <option value="<?= $us['id'] ?>">
                                <?= htmlspecialchars($us['student_name']) ?> (<?= htmlspecialchars($us['reg_no']) ?>) - <?= htmlspecialchars($us['department']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Select Available Room * (BR-02)</label>
                    <select name="room_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 bg-white">
                        <?php foreach ($available_rooms as $ar): ?>
                            <?php $vac = $ar['capacity'] - $ar['occupied_beds']; ?>
                            <option value="<?= $ar['id'] ?>">
                                Room <?= htmlspecialchars($ar['room_number']) ?> (<?= htmlspecialchars($ar['block']) ?>) • <?= htmlspecialchars($ar['room_type']) ?> • <?= $vac ?> Bed(s) Free
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i> Complete Room Allocation
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 text-center">
                <?= empty($unallocated_students) ? 'All approved students currently have room assignments.' : 'No rooms currently have vacant beds. Add rooms in Admin section.' ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Current Allocations Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-base">Active Room Occupants (<?= count($allocated_residents) ?> Total)</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Allocated Room</th>
                        <th class="px-6 py-3.5">Student Name</th>
                        <th class="px-6 py-3.5">Registration Number</th>
                        <th class="px-6 py-3.5">Department</th>
                        <th class="px-6 py-3.5">Contact Phone</th>
                        <th class="px-6 py-3.5">Check-In Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($allocated_residents as $ar): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">
                                Room <?= htmlspecialchars($ar['room_number']) ?>
                                <span class="block text-[11px] text-slate-500 font-normal"><?= htmlspecialchars($ar['block']) ?></span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                <?= htmlspecialchars($ar['name']) ?>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-700">
                                <?= htmlspecialchars($ar['reg_no']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= htmlspecialchars($ar['department']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= htmlspecialchars($ar['phone']) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                <?= $ar['check_in_date'] ? date('d M Y', strtotime($ar['check_in_date'])) : '<span class="text-amber-600">Pending</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
