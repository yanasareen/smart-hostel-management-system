<?php
/**
 * Smart Hostel Complaint Management System
 * Student - Complaints Logging, Tracking & 5-Star Feedback (FR-10 to FR-14, FR-18, FR-20, BR-07)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

require_role('student');

$db = get_db();
$user_id = current_user_id();

// Fetch Student Profile
$stmt = $db->prepare("SELECT s.*, r.room_number, r.block FROM students s LEFT JOIN rooms r ON s.room_id = r.id WHERE s.user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();
$student_id = $student['id'];

// Handle POST: Submit Complaint or Feedback
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form_action = $_POST['form_action'] ?? '';

    // 1. Submit Complaint (FR-10, FR-11, FR-12, FR-13, FR-18)
    if ($form_action === 'submit_complaint') {
        $category = trim($_POST['category'] ?? '');
        $priority = trim($_POST['priority'] ?? 'Medium');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location_details'] ?? '');
        $parent_dup = !empty($_POST['parent_duplicate_id']) ? intval($_POST['parent_duplicate_id']) : null;
        $room_id = $student['room_id'] ? $student['room_id'] : null;

        $valid_categories = ['Electricity', 'Plumbing', 'Wi-Fi', 'Cleaning', 'Furniture', 'Other'];
        if (empty($category) || empty($title) || empty($description)) {
            set_flash('danger', 'Please provide a category, subject title, and problem description.');
        } elseif (!in_array($category, $valid_categories)) {
            set_flash('danger', 'Invalid complaint category selected.');
        } else {
            $ticket_id = 'CMP-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));

            $stmt = $db->prepare("INSERT INTO complaints (ticket_id, student_id, room_id, location_details, category, priority, title, description, status, parent_duplicate_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([$ticket_id, $student_id, $room_id, $location, $category, $priority, $title, $description, $parent_dup]);

            create_notification($user_id, "Complaint Registered ($ticket_id)", "Your $category complaint has been logged and assigned status 'Pending'.", 'info', 'my_complaints.php');

            $warden_id = $db->query("SELECT id FROM users WHERE role = 'warden' LIMIT 1")->fetchColumn();
            if ($warden_id) {
                $priority_note = ($priority === 'High') ? '⚠️ HIGH PRIORITY: ' : '';
                create_notification($warden_id, "{$priority_note}New Complaint #$ticket_id", "$category reported by student ({$student['reg_no']}).", 'warning', 'warden_complaints.php');
            }

            if ($parent_dup) {
                set_flash('info', "Complaint #$ticket_id logged! It was automatically linked to active ticket #CMP for consolidated maintenance dispatch.");
            } else {
                set_flash('success', "Complaint #$ticket_id successfully submitted! Tracking has commenced.");
            }
        }
        header('Location: my_complaints.php');
        exit;
    }

    // 2. Submit Feedback & Rating (FR-20, BR-07)
    if ($form_action === 'submit_feedback') {
        $complaint_id = intval($_POST['complaint_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        $stmt = $db->prepare("SELECT id, status FROM complaints WHERE id = ? AND student_id = ?");
        $stmt->execute([$complaint_id, $student_id]);
        $cmp = $stmt->fetch();

        if ($cmp && $cmp['status'] === 'Resolved') {
            if ($rating >= 1 && $rating <= 5) {
                $stmt = $db->prepare("INSERT INTO complaint_feedback (complaint_id, student_id, rating, comment) VALUES (?, ?, ?, ?)");
                $stmt->execute([$complaint_id, $student_id, $rating, $comment]);
                set_flash('success', 'Thank you! Your feedback and resolution rating have been recorded.');
            } else {
                set_flash('danger', 'Please provide a valid rating between 1 and 5 stars.');
            }
        } else {
            set_flash('danger', 'Feedback can only be provided for resolved complaints (BR-07).');
        }
        header('Location: my_complaints.php');
        exit;
    }
}

// Fetch Complaints with Filters
$filter_status = trim($_GET['status'] ?? '');
$filter_category = trim($_GET['cat'] ?? '');

$sql = "SELECT c.*, cf.rating, cf.comment as feedback_comment, p.ticket_id as parent_ticket_id 
        FROM complaints c 
        LEFT JOIN complaint_feedback cf ON c.id = cf.complaint_id 
        LEFT JOIN complaints p ON c.parent_duplicate_id = p.id 
        WHERE c.student_id = ? ";
$params = [$student_id];

if (!empty($filter_status)) {
    $sql .= " AND c.status = ? ";
    $params[] = $filter_status;
}
if (!empty($filter_category)) {
    $sql .= " AND c.category = ? ";
    $params[] = $filter_category;
}

$sql .= " ORDER BY c.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$my_complaints = $stmt->fetchAll();

$page_title = 'My Complaints';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-blue-200 border border-white/20 mb-2">
                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Service & Issue Tracking (FR-10, FR-14)
            </div>
            <h1 class="text-2xl font-black">Hostel Maintenance Complaints</h1>
            <p class="text-xs sm:text-sm text-blue-200 mt-1">Submit repairs with intelligent duplicate alerts, track status, and submit 5-star ratings.</p>
        </div>

        <button onclick="openModal('newComplaintModal')" class="px-5 py-3 rounded-xl bg-white text-blue-900 hover:bg-blue-50 font-bold text-xs shadow-md transition flex items-center gap-2 flex-shrink-0">
            <i data-lucide="plus-circle" class="w-4 h-4 text-blue-600"></i> Submit New Complaint
        </button>
    </div>

    <!-- Filter & Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Service Requests & History</h3>
                <p class="text-xs text-slate-500 mt-0.5">Total: <?= count($my_complaints) ?> tickets filed</p>
            </div>

            <!-- Filters -->
            <form method="GET" action="my_complaints.php" class="flex flex-wrap items-center gap-2 text-xs">
                <select name="cat" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white">
                    <option value="">All Categories</option>
                    <option value="Electricity" <?= $filter_category === 'Electricity' ? 'selected' : '' ?>>Electricity</option>
                    <option value="Plumbing" <?= $filter_category === 'Plumbing' ? 'selected' : '' ?>>Plumbing</option>
                    <option value="Wi-Fi" <?= $filter_category === 'Wi-Fi' ? 'selected' : '' ?>>Wi-Fi</option>
                    <option value="Cleaning" <?= $filter_category === 'Cleaning' ? 'selected' : '' ?>>Cleaning</option>
                    <option value="Furniture" <?= $filter_category === 'Furniture' ? 'selected' : '' ?>>Furniture</option>
                </select>

                <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>

                <?php if ($filter_category || $filter_status): ?>
                    <a href="my_complaints.php" class="p-1.5 text-slate-400 hover:text-slate-600" title="Reset filter">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($my_complaints)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5">Ticket ID</th>
                            <th class="px-6 py-3.5">Category</th>
                            <th class="px-6 py-3.5">Priority</th>
                            <th class="px-6 py-3.5">Subject & Location</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($my_complaints as $c): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    #<?= htmlspecialchars($c['ticket_id']) ?>
                                    <?php if ($c['is_escalated']): ?>
                                        <span class="block text-[10px] text-red-600 font-bold mt-0.5">⚠️ ESCALATED</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-800"><?= htmlspecialchars($c['category']) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="badge-status <?= $c['priority'] === 'High' ? 'badge-high' : ($c['priority'] === 'Medium' ? 'badge-medium' : 'badge-low') ?>">
                                        <?= htmlspecialchars($c['priority']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="font-semibold text-slate-900 truncate"><?= htmlspecialchars($c['title']) ?></p>
                                    <p class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($c['location_details'] ?: 'Room ' . $student['room_number']) ?></p>
                                    <?php if ($c['parent_duplicate_id']): ?>
                                        <span class="inline-block px-1.5 py-0.5 text-[9px] bg-amber-100 text-amber-800 rounded font-semibold mt-1">
                                            Duplicate of #<?= htmlspecialchars($c['parent_ticket_id'] ?? 'Parent') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="badge-status <?= $c['status'] === 'Resolved' ? 'badge-resolved' : ($c['status'] === 'In Progress' ? 'badge-progress' : 'badge-pending') ?>">
                                        <?= htmlspecialchars($c['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($c['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <button onclick="openModal('viewTicketModal_<?= $c['id'] ?>')" class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 transition">
                                        Details
                                    </button>

                                    <?php if ($c['status'] === 'Resolved'): ?>
                                        <?php if ($c['rating']): ?>
                                            <span class="inline-flex items-center text-amber-500 font-bold" title="<?= htmlspecialchars($c['feedback_comment']) ?>">
                                                <?= str_repeat('★', $c['rating']) ?>
                                            </span>
                                        <?php else: ?>
                                            <button onclick="openModal('feedbackModal_<?= $c['id'] ?>')" class="px-2.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-semibold transition">
                                                ★ Rate Resolution
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Details Modal -->
                            <div id="viewTicketModal_<?= $c['id'] ?>" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
                                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl modal-content space-y-4">
                                    <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                                        <div>
                                            <h3 class="font-bold text-slate-900 text-base">Ticket #<?= htmlspecialchars($c['ticket_id']) ?></h3>
                                            <p class="text-xs text-slate-500 mt-0.5">Category: <?= htmlspecialchars($c['category']) ?> • Priority: <?= htmlspecialchars($c['priority']) ?></p>
                                        </div>
                                        <button onclick="closeModal('viewTicketModal_<?= $c['id'] ?>')" class="text-slate-400 hover:text-slate-600">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                        </button>
                                    </div>

                                    <div class="space-y-3 text-xs">
                                        <div>
                                            <span class="font-semibold text-slate-700">Subject:</span>
                                            <p class="text-slate-900 font-medium mt-0.5"><?= htmlspecialchars($c['title']) ?></p>
                                        </div>
                                        <div>
                                            <span class="font-semibold text-slate-700">Description:</span>
                                            <p class="text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 mt-1 leading-relaxed"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                                        </div>

                                        <?php if ($c['staff_notes']): ?>
                                            <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                                                <span class="font-bold text-blue-900">Hostel Staff / Technician Notes:</span>
                                                <p class="text-blue-800 mt-1"><?= nl2br(htmlspecialchars($c['staff_notes'])) ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($c['rating']): ?>
                                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-100">
                                                <span class="font-bold text-amber-900">Your Submitted Feedback:</span>
                                                <div class="text-amber-500 font-bold text-sm mt-0.5"><?= str_repeat('★', $c['rating']) ?> (<?= $c['rating'] ?>/5)</div>
                                                <p class="text-amber-800 mt-1 italic">"<?= htmlspecialchars($c['feedback_comment']) ?>"</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                                        <button onclick="closeModal('viewTicketModal_<?= $c['id'] ?>')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 5-Star Feedback Modal -->
                            <?php if ($c['status'] === 'Resolved' && !$c['rating']): ?>
                                <div id="feedbackModal_<?= $c['id'] ?>" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
                                    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl modal-content">
                                        <div class="flex justify-between items-start border-b border-slate-100 pb-3 mb-4">
                                            <div>
                                                <h3 class="font-bold text-slate-900 text-base">Rate Service Resolution</h3>
                                                <p class="text-xs text-slate-500 mt-0.5">Ticket #<?= htmlspecialchars($c['ticket_id']) ?> • <?= htmlspecialchars($c['title']) ?></p>
                                            </div>
                                            <button onclick="closeModal('feedbackModal_<?= $c['id'] ?>')" class="text-slate-400 hover:text-slate-600">
                                                <i data-lucide="x" class="w-5 h-5"></i>
                                            </button>
                                        </div>

                                        <form method="POST" action="my_complaints.php">
                                            <input type="hidden" name="form_action" value="submit_feedback">
                                            <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">

                                            <div class="text-center py-2">
                                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">How satisfied are you with the repair turnaround?</label>
                                                <div class="star-rating justify-center mb-2">
                                                    <input type="radio" id="star5_<?= $c['id'] ?>" name="rating" value="5" checked><label for="star5_<?= $c['id'] ?>">★</label>
                                                    <input type="radio" id="star4_<?= $c['id'] ?>" name="rating" value="4"><label for="star4_<?= $c['id'] ?>">★</label>
                                                    <input type="radio" id="star3_<?= $c['id'] ?>" name="rating" value="3"><label for="star3_<?= $c['id'] ?>">★</label>
                                                    <input type="radio" id="star2_<?= $c['id'] ?>" name="rating" value="2"><label for="star2_<?= $c['id'] ?>">★</label>
                                                    <input type="radio" id="star1_<?= $c['id'] ?>" name="rating" value="1"><label for="star1_<?= $c['id'] ?>">★</label>
                                                </div>
                                                <p class="rating-text text-xs text-amber-600 font-semibold">5 Stars - Excellent / Prompt Resolution</p>
                                            </div>

                                            <div class="mt-4">
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Feedback Comments / Remarks</label>
                                                <textarea name="comment" rows="3" placeholder="Describe your experience with the technician and whether the issue is fully solved..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                                            </div>

                                            <div class="mt-5 flex justify-end gap-2">
                                                <button type="button" onclick="closeModal('feedbackModal_<?= $c['id'] ?>')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">Cancel</button>
                                                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-semibold shadow-sm">Submit Feedback</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center text-xs text-slate-500">
                No complaints recorded. Click "Submit New Complaint" to file a maintenance issue.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Submit Complaint with Dynamic Duplicate Warning (FR-10, FR-18) -->
<div id="newComplaintModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl modal-content max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start border-b border-slate-100 pb-3 mb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-slate-900 text-base">Submit Hostel Maintenance Complaint</h3>
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700 rounded">FR-10</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Online maintenance dispatch with real-time duplicate detection</p>
            </div>
            <button onclick="closeModal('newComplaintModal')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="my_complaints.php" class="space-y-4">
            <input type="hidden" name="form_action" value="submit_complaint">
            <input type="hidden" id="complaint_room" value="<?= $student['room_id'] ?? '' ?>">

            <!-- Real-Time Duplicate Complaint Alert Container (FR-18) -->
            <div id="duplicate_alert_box" class="hidden p-3.5 rounded-xl bg-amber-50 border border-amber-300">
                <div id="duplicate_alert_content"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Complaint Category * (FR-11)</label>
                    <select id="complaint_category" name="category" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">-- Select Category --</option>
                        <option value="Electricity">Electricity</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Wi-Fi">Wi-Fi</option>
                        <option value="Cleaning">Cleaning</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Other">Other Hostel-related issues</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Priority Urgency * (FR-13)</label>
                    <select name="priority" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="Low">Low (General convenience / 72h SLA)</option>
                        <option value="Medium" selected>Medium (Standard maintenance / 48h SLA)</option>
                        <option value="High">High (Urgent / Safety Hazard / 24h SLA)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Specific Location / Area</label>
                <input type="text" id="complaint_location" name="location_details" value="<?= $student['room_number'] ? 'Room ' . htmlspecialchars($student['room_number']) . ', ' . htmlspecialchars($student['block']) : '' ?>" placeholder="e.g. Room 102 Washroom, or 1st Floor Corridor" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Complaint Title / Summary *</label>
                <input type="text" name="title" required placeholder="e.g. Wi-Fi router dropping connection in room" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Detailed Description of Problem *</label>
                <textarea name="description" required rows="4" placeholder="Explain the exact fault, when it started, and steps you already tried..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('newComplaintModal')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i> Submit Complaint
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
