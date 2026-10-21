<?php
/**
 * Smart Hostel Complaint Management System
 * Role-Aware Navigation Bar
 */
$role = current_user_role();
$active_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Brand -->
            <div class="flex items-center">
                <a href="<?= is_logged_in() ? $role . '_dashboard.php' : 'index.php' ?>" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-md shadow-blue-500/30 group-hover:bg-blue-700 transition">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-lg text-slate-900 tracking-tight">SmartHostel</span>
                            <span class="px-1.5 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700 rounded uppercase">v1.0</span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">Apex University Smart Residence</p>
                    </div>
                </a>
            </div>

            <!-- Role-based Navigation Links -->
            <div class="hidden md:flex md:items-center md:space-x-1 lg:space-x-2">
                <?php if ($role === 'student'): ?>
                    <a href="student_dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'student_dashboard.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50' ?>">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 text-slate-500"></i> Dashboard
                    </a>
                    <a href="apply_room.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'apply_room.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50' ?>">
                        <i data-lucide="file-plus" class="w-4 h-4 text-slate-500"></i> Apply Room
                    </a>
                    <a href="available_rooms.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'available_rooms.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50' ?>">
                        <i data-lucide="door-open" class="w-4 h-4 text-slate-500"></i> Available Rooms
                    </a>
                    <a href="my_complaints.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'my_complaints.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50' ?>">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-slate-500"></i> My Complaints
                    </a>
                    <a href="my_fees.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'my_fees.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700 hover:text-blue-600 hover:bg-slate-50' ?>">
                        <i data-lucide="receipt" class="w-4 h-4 text-slate-500"></i> Fees & Receipts
                    </a>
                <?php elseif ($role === 'warden'): ?>
                    <a href="warden_dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'warden_dashboard.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-50' ?>">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 text-slate-500"></i> Overview
                    </a>
                    <a href="warden_applications.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'warden_applications.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-50' ?>">
                        <i data-lucide="file-check" class="w-4 h-4 text-slate-500"></i> Applications
                    </a>
                    <a href="warden_allocation.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'warden_allocation.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-50' ?>">
                        <i data-lucide="bed" class="w-4 h-4 text-slate-500"></i> Room Allocation
                    </a>
                    <a href="warden_checkin.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'warden_checkin.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-50' ?>">
                        <i data-lucide="log-in" class="w-4 h-4 text-slate-500"></i> Check-in/Out
                    </a>
                    <a href="warden_complaints.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'warden_complaints.php' ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-700 hover:text-emerald-600 hover:bg-slate-50' ?>">
                        <i data-lucide="wrench" class="w-4 h-4 text-slate-500"></i> Complaints
                    </a>
                <?php elseif ($role === 'accountant'): ?>
                    <a href="accountant_dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'accountant_dashboard.php' ? 'bg-amber-50 text-amber-700 font-bold' : 'text-slate-700 hover:text-amber-600 hover:bg-slate-50' ?>">
                        <i data-lucide="wallet" class="w-4 h-4 text-slate-500"></i> Overview
                    </a>
                    <a href="accountant_record_payment.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'accountant_record_payment.php' ? 'bg-amber-50 text-amber-700 font-bold' : 'text-slate-700 hover:text-amber-600 hover:bg-slate-50' ?>">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-slate-500"></i> Record Payment
                    </a>
                    <a href="accountant_receipts.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'accountant_receipts.php' ? 'bg-amber-50 text-amber-700 font-bold' : 'text-slate-700 hover:text-amber-600 hover:bg-slate-50' ?>">
                        <i data-lucide="receipt" class="w-4 h-4 text-slate-500"></i> Receipts
                    </a>
                <?php elseif ($role === 'admin'): ?>
                    <a href="admin_dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'admin_dashboard.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-700 hover:text-purple-600 hover:bg-slate-50' ?>">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-slate-500"></i> Analytics
                    </a>
                    <a href="admin_students.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'admin_students.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-700 hover:text-purple-600 hover:bg-slate-50' ?>">
                        <i data-lucide="users" class="w-4 h-4 text-slate-500"></i> Student Records
                    </a>
                    <a href="admin_rooms.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'admin_rooms.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-700 hover:text-purple-600 hover:bg-slate-50' ?>">
                        <i data-lucide="door-closed" class="w-4 h-4 text-slate-500"></i> Rooms
                    </a>
                    <a href="admin_reports.php" class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 <?= $active_page === 'admin_reports.php' ? 'bg-purple-50 text-purple-700 font-bold' : 'text-slate-700 hover:text-purple-600 hover:bg-slate-50' ?>">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-500"></i> Reports
                    </a>
                <?php else: ?>
                    <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:text-blue-600 transition">Home</a>
                    <a href="index.php#features" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:text-blue-600 transition">Smart Features</a>
                    <a href="index.php#rooms" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:text-blue-600 transition">Rooms</a>
                    <a href="index.php#about" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:text-blue-600 transition">About Project</a>
                <?php endif; ?>
            </div>

            <!-- Right: Notifications & Profile / Login -->
            <div class="flex items-center gap-3">
                <?php if (is_logged_in()): ?>
                    <!-- Notification Bell Dropdown (FR-17) -->
                    <div class="relative" id="notifDropdownContainer">
                        <button type="button" onclick="document.getElementById('notifDropdown').classList.toggle('hidden')" class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-full transition relative">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <?php if ($unread_notifs > 0): ?>
                                <span class="absolute top-1 right-1 w-4 h-4 bg-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center animate-pulse">
                                    <?= $unread_notifs ?>
                                </span>
                            <?php endif; ?>
                        </button>

                        <!-- Notification Dropdown Panel -->
                        <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="font-semibold text-sm text-slate-900">Notifications</h3>
                                <span class="text-xs text-slate-500"><?= $unread_notifs ?> unread</span>
                            </div>
                            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
                                <?php if (!empty($latest_notifs)): ?>
                                    <?php foreach ($latest_notifs as $nt): ?>
                                        <div class="p-3 hover:bg-slate-50 transition flex items-start gap-3 <?= $nt['is_read'] ? 'opacity-70' : 'bg-blue-50/40' ?>">
                                            <div class="p-1.5 rounded-lg <?= $nt['type'] === 'warning' ? 'bg-amber-100 text-amber-600' : ($nt['type'] === 'success' ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600') ?>">
                                                <i data-lucide="<?= $nt['type'] === 'warning' ? 'alert-triangle' : ($nt['type'] === 'success' ? 'check-circle' : 'info') ?>" class="w-4 h-4"></i>
                                            </div>
                                            <div class="flex-1 text-xs">
                                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($nt['title']) ?></p>
                                                <p class="text-slate-600 mt-0.5 leading-relaxed"><?= htmlspecialchars($nt['message']) ?></p>
                                                <div class="flex items-center justify-between mt-1.5 text-[10px] text-slate-400">
                                                    <span><?= time_ago($nt['created_at']) ?></span>
                                                    <?php if (!$nt['is_read']): ?>
                                                        <button onclick="markNotificationRead(<?= $nt['id'] ?>, '<?= $nt['link'] ?? '' ?>')" class="text-blue-600 hover:underline font-medium">Mark Read</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-xs text-slate-500">No notifications yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown / Logout -->
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                            <?= substr($user['name'] ?? 'U', 0, 1) ?>
                        </div>
                        <div class="hidden sm:block text-left text-xs leading-tight">
                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="text-slate-500 capitalize"><?= $role ?></div>
                        </div>
                        <a href="logout.php" class="p-2 text-slate-400 hover:text-red-600 transition" title="Log out">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-medium text-slate-700 hover:text-blue-600 transition">Log in</a>
                    <a href="register.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition">
                        Register Student
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Global Flash Message Container -->
<?php $flash = get_flash(); if ($flash): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 auto-dismiss-alert">
        <div class="p-4 rounded-xl border flex items-center justify-between gap-3 shadow-sm <?= $flash['type'] === 'danger' ? 'bg-red-50 border-red-200 text-red-800' : ($flash['type'] === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : ($flash['type'] === 'info' ? 'bg-blue-50 border-blue-200 text-blue-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800')) ?>">
            <div class="flex items-center gap-2.5 text-sm font-medium">
                <i data-lucide="<?= $flash['type'] === 'danger' ? 'alert-circle' : ($flash['type'] === 'warning' ? 'alert-triangle' : ($flash['type'] === 'info' ? 'info' : 'check-circle')) ?>" class="w-5 h-5 flex-shrink-0"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
<?php endif; ?>
