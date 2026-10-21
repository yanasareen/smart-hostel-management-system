<?php
/**
 * Smart Hostel Complaint Management System
 * Shared Footer Component
 */
?>
<footer class="bg-white border-t border-slate-200 mt-auto py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded bg-blue-600 text-white flex items-center justify-center font-bold text-[10px]">
                    SH
                </div>
                <span><strong><?= APP_NAME ?></strong> (Version <?= APP_VERSION ?>)</span>
            </div>
            <div class="text-center md:text-right">
                <p>Software Engineering Laboratory Project</p>
                <p class="text-slate-400 mt-0.5">Designed & Developed by <strong>Yana Sareen</strong> (Reg No: <strong>2427030570</strong>)</p>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
            <p>&copy; <?= date('Y') ?> <?= HOSTEL_NAME ?>. IEEE SRS Compliant Academic System.</p>
            <div class="flex items-center gap-4">
                <a href="index.php" class="hover:text-blue-600 transition">Portal Home</a>
                <a href="setup.php" class="hover:text-blue-600 transition">Database Setup / Reset</a>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="assets/js/app.js"></script>
<script>
    // Re-run lucide icons after page load
    if (window.lucide) {
        window.lucide.createIcons();
    }
</script>
</body>
</html>
