<?php
/**
 * Smart Hostel Complaint Management System
 * Landing Page & System Showcase
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$db = get_db();

// Fetch summary metrics
$total_rooms = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$available_beds = $db->query("SELECT SUM(capacity - occupied_beds) FROM rooms WHERE status = 'Available'")->fetchColumn() ?: 0;
$total_complaints = $db->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$resolved_complaints = $db->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
$avg_rating = $db->query("SELECT AVG(rating) FROM complaint_feedback")->fetchColumn() ?: 5.0;

// Fetch sample available rooms
$rooms = $db->query("SELECT * FROM rooms ORDER BY block, room_number LIMIT 6")->fetchAll();

$page_title = 'Welcome';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<div class="relative bg-gradient-to-b from-blue-900 via-indigo-900 to-slate-900 text-white py-12 sm:py-16 flex-shrink-0">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px] pointer-events-none"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-400/30 mb-4 backdrop-blur-sm">
            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
            Software Engineering Laboratory Project • Version 1.0
        </div>
        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
            Smart Hostel Complaint &<br class="hidden sm:inline">
            <span class="inline-block text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-emerald-400">
                Residence Management System
            </span>
        </h1>
        <p class="mt-4 sm:mt-6 text-sm sm:text-base text-slate-300 max-w-3xl mx-auto leading-relaxed">
            Eliminating paper complaint registers through automated SLA ticket escalations, intelligent duplicate issue detection, seamless room allocation, and digital fee receipt generation.
        </p>

        <!-- CTA Buttons -->
        <div class="mt-6 sm:mt-8 flex flex-wrap items-center justify-center gap-3.5">
            <?php if (is_logged_in()): ?>
                <a href="<?= current_user_role() ?>_dashboard.php" class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm shadow-lg shadow-blue-500/30 transition flex items-center gap-2">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    Go to Your Dashboard (<?= ucfirst(current_user_role()) ?>)
                </a>
            <?php else: ?>
                <a href="login.php" class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm shadow-lg shadow-blue-500/30 transition flex items-center gap-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    Sign In to Portal
                </a>
                <a href="register.php" class="px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm border border-white/20 transition flex items-center gap-2 backdrop-blur-sm">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Student Registration
                </a>
            <?php endif; ?>
        </div>

        <!-- Metric Badges -->
        <div class="mt-10 sm:mt-12 grid grid-cols-2 sm:grid-cols-4 gap-3.5 max-w-4xl mx-auto">
            <div class="p-3.5 sm:p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm text-center">
                <div class="text-2xl sm:text-3xl font-black text-white"><?= $available_beds ?></div>
                <div class="text-xs text-blue-200 mt-1">Available Beds</div>
            </div>
            <div class="p-3.5 sm:p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm text-center">
                <div class="text-2xl sm:text-3xl font-black text-white"><?= $total_complaints ?></div>
                <div class="text-xs text-blue-200 mt-1">Complaints Logged</div>
            </div>
            <div class="p-3.5 sm:p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm text-center">
                <div class="text-2xl sm:text-3xl font-black text-emerald-400"><?= $resolved_complaints ?></div>
                <div class="text-xs text-blue-200 mt-1">Issues Resolved</div>
            </div>
            <div class="p-3.5 sm:p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm text-center">
                <div class="text-2xl sm:text-3xl font-black text-amber-300"><?= number_format($avg_rating, 1) ?> ★</div>
                <div class="text-xs text-blue-200 mt-1">Satisfaction Rating</div>
            </div>
        </div>
    </div>
</div>

<!-- 1-Click Evaluation Roles Bar -->
<div class="bg-slate-100 border-y border-slate-200 py-6 sm:py-8 flex-shrink-0">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-5">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700">Lab Evaluation: Test All 4 Roles in One Click</h3>
            <p class="text-xs text-slate-500">Click any card below to log in as that persona and inspect role permissions instantly.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Student -->
            <a href="api.php?action=quick_login&role=student" class="p-4 rounded-xl bg-white border border-slate-200 hover:border-blue-500 hover:shadow-md transition group text-left">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-900">Student Portal</h4>
                        <p class="text-xs text-slate-500">Yana Sareen (Reg: 2427030570)</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 mt-2.5">File complaints with duplicate warnings, apply for room, submit 5-star ratings, view fee receipts.</p>
            </a>

            <!-- Warden -->
            <a href="api.php?action=quick_login&role=warden" class="p-4 rounded-xl bg-white border border-slate-200 hover:border-emerald-500 hover:shadow-md transition group text-left">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-900">Hostel Warden</h4>
                        <p class="text-xs text-slate-500">Dr. R. K. Sharma</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 mt-2.5">Approve applications, allocate vacant rooms, record check-in/out, resolve complaints, monitor escalations.</p>
            </a>

            <!-- Accountant -->
            <a href="api.php?action=quick_login&role=accountant" class="p-4 rounded-xl bg-white border border-slate-200 hover:border-amber-500 hover:shadow-md transition group text-left">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center group-hover:bg-amber-600 group-hover:text-white transition">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-900">Accountant</h4>
                        <p class="text-xs text-slate-500">Mr. Alok Verma</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 mt-2.5">Record payments across cash/bank/UPI, generate formal receipts with unique numbers, fee ledger.</p>
            </a>

            <!-- Admin -->
            <a href="api.php?action=quick_login&role=admin" class="p-4 rounded-xl bg-white border border-slate-200 hover:border-purple-500 hover:shadow-md transition group text-left">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-900">Administrator</h4>
                        <p class="text-xs text-slate-500">System Command</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 mt-2.5">System-wide analytics, room inventory control, student record administration, printable activity reports.</p>
            </a>
        </div>
    </div>
</div>

<!-- What Makes It "Smart"? (Key SRS Differentiators) -->
<section id="features" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-14">
            <span class="text-xs font-bold uppercase tracking-wider text-blue-600">SRS Differentiators</span>
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Why is this a "Smart" Hostel System?</h2>
            <p class="text-sm text-slate-600 mt-3 leading-relaxed">
                Conventional hostel portals are merely static registers. The Smart Hostel Complaint Management System introduces proactive automation to solve real-world hostel operational bottlenecks.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1: Duplicate Detection -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50/50 card-hover">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center mb-4">
                    <i data-lucide="copy-check" class="w-6 h-6"></i>
                </div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-base text-slate-900">Duplicate Issue Detection</h3>
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 rounded">FR-18</span>
                </div>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    Automatically checks if a complaint in the same room and category is already In Progress. Alerts the student in real time to avoid dispatching multiple technicians for the same broken tap or router.
                </p>
            </div>

            <!-- Feature 2: Automated SLA Escalation -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50/50 card-hover">
                <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center mb-4">
                    <i data-lucide="timer-reset" class="w-6 h-6"></i>
                </div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-base text-slate-900">Automated SLA Escalation</h3>
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-800 rounded">FR-19</span>
                </div>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    High priority safety tickets (e.g. sparking switchboards) escalate automatically after 24 hours without resolution, triggering visual alerts on the Warden and Administrator dashboards.
                </p>
            </div>

            <!-- Feature 3: Resolution & Feedback Loop -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50/50 card-hover">
                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4">
                    <i data-lucide="star" class="w-6 h-6"></i>
                </div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-base text-slate-900">5-Star Resolution Verification</h3>
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 rounded">FR-20</span>
                </div>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    Staff cannot unilaterally close a ticket without student feedback. Once marked Resolved, the resident rates the turnaround (1-5 stars) and leaves remarks, ensuring genuine accountability.
                </p>
            </div>

            <!-- Feature 4: Room Allocation & Capacity Control -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50/50 card-hover">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                    <i data-lucide="bed-double" class="w-6 h-6"></i>
                </div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-base text-slate-900">Enforced Capacity Allocation</h3>
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 rounded">BR-02</span>
                </div>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    Students submit preferences; the Warden reviews and approves applications. Only rooms with available capacity can be allocated, preventing overbooking. Check-in and check-out dates are tracked.
                </p>
            </div>

            <!-- Feature 5: Digital Receipts & Audit Trail -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50/50 card-hover">
                <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-600 flex items-center justify-center mb-4">
                    <i data-lucide="file-check-2" class="w-6 h-6"></i>
                </div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-base text-slate-900">Instant Printable Receipts</h3>
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-cyan-100 text-cyan-800 rounded">FR-23</span>
                </div>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    Accountants record payments across cash, UPI, and bank transfer. Official university hostel receipts with unique serials and verification stamps can be printed and downloaded instantly.
                </p>
            </div>

            <!-- Feature 6: In-App Notifications & Analytics -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50/50 card-hover">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center mb-4">
                    <i data-lucide="bell-ring" class="w-6 h-6"></i>
                </div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-base text-slate-900">Real-Time Notification Feed</h3>
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-purple-100 text-purple-800 rounded">FR-17</span>
                </div>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    Instant in-app alerts notify students of complaint status transitions, room allocation approvals, and receipt generation, keeping everyone continuously informed.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Available Rooms Section (FR-08) -->
<section id="rooms" class="py-16 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Living Spaces</span>
                <h2 class="text-2xl font-bold text-slate-900">Hostel Room Inventory & Availability</h2>
            </div>
            <a href="register.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                Apply for a room <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($rooms as $r): ?>
                <?php 
                    $vacant = $r['capacity'] - $r['occupied_beds'];
                    $is_available = $r['status'] === 'Available' && $vacant > 0;
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm card-hover">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?= htmlspecialchars($r['block']) ?></span>
                            <h3 class="text-lg font-bold text-slate-900">Room <?= htmlspecialchars($r['room_number']) ?></h3>
                        </div>
                        <span class="badge-status <?= $r['status'] === 'Available' && $vacant > 0 ? 'badge-resolved' : ($r['status'] === 'Under Maintenance' ? 'badge-escalated' : 'badge-pending') ?>">
                            <?= $r['status'] === 'Available' && $vacant > 0 ? 'Available' : htmlspecialchars($r['status']) ?>
                        </span>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Room Type:</span>
                            <strong class="text-slate-800"><?= htmlspecialchars($r['room_type']) ?></strong>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Floor:</span>
                            <strong class="text-slate-800"><?= $r['floor'] ?> Floor</strong>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Capacity / Vacancy:</span>
                            <strong class="text-slate-800"><?= $r['capacity'] ?> Beds (<?= $vacant ?> Vacant)</strong>
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
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- About Project / SE Requirements (SRS Alignment) -->
<section id="about" class="py-16 bg-white border-t border-slate-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-8 rounded-2xl bg-gradient-to-br from-slate-900 to-indigo-950 text-white shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-6 mb-6">
                <div>
                    <h3 class="text-xl font-bold">Software Engineering Laboratory Project</h3>
                    <p class="text-xs text-blue-300 mt-1">Smart Hostel Complaint Management System • Version 1.0</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-xs text-slate-400">Student Developer</p>
                    <p class="font-bold text-white text-sm">Yana Sareen</p>
                    <p class="text-xs text-slate-400">Registration Number: <strong class="text-blue-300">2427030570</strong></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-300">
                <div class="flex items-start gap-2.5">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5"></i>
                    <span><strong>Role-Based Access Control:</strong> Strict separation of concerns across Student, Warden, Accountant, and Admin.</span>
                </div>
                <div class="flex items-start gap-2.5">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5"></i>
                    <span><strong>Complaint Lifecycle:</strong> Pending → In Progress → Resolved + 1-5 Star Student Feedback rating.</span>
                </div>
                <div class="flex items-start gap-2.5">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5"></i>
                    <span><strong>Duplicate Ticket Filter:</strong> Identifies potential duplicate complaints for same room & category.</span>
                </div>
                <div class="flex items-start gap-2.5">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5"></i>
                    <span><strong>Fee Management:</strong> Recording payments and generating official printable receipts with unique IDs.</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
