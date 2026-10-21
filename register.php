<?php
/**
 * Smart Hostel Complaint Management System
 * Student Registration (FR-01, FR-04, NFR-09)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (is_logged_in()) {
    $role = current_user_role();
    header("Location: {$role}_dashboard.php");
    exit;
}

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $reg_no = trim($_POST['reg_no'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $year = trim($_POST['year'] ?? '1st Year');
    $gender = trim($_POST['gender'] ?? 'Female');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validate required fields (FR-04)
    if (empty($name) || empty($email) || empty($reg_no) || empty($phone) || empty($department) || empty($password)) {
        $error = 'Please fill in all required fields marked with an asterisk (*).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid university email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long for security.';
    } elseif ($password !== $password_confirm) {
        $error = 'Password confirmation does not match.';
    } else {
        $db = get_db();

        // Check if email or reg_no already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR reg_no = ?");
        $stmt->execute([$email, $reg_no]);
        if ($stmt->fetch()) {
            $error = 'An account with this email address or registration number is already registered.';
        } else {
            try {
                $db->beginTransaction();

                // 1. Create User account
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, phone, reg_no) VALUES (?, ?, ?, 'student', ?, ?)");
                $stmt->execute([$name, $email, $hashed_password, $phone, $reg_no]);
                $user_id = $db->lastInsertId();

                // 2. Create Student profile
                $stmt_st = $db->prepare("INSERT INTO students (user_id, reg_no, department, year, gender, emergency_contact, status) VALUES (?, ?, ?, ?, ?, ?, 'Registered')");
                $stmt_st->execute([$user_id, $reg_no, $department, $year, $gender, $emergency_contact]);

                // 3. Welcome Notification
                create_notification($user_id, 'Welcome to SmartHostel!', 'Your account has been registered. You can now apply for room accommodation or file hostel maintenance requests.', 'success', 'student_dashboard.php');

                $db->commit();

                // Log the student in
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user;

                set_flash('success', "Registration successful! Welcome to the Smart Hostel portal, $name.");
                header('Location: student_dashboard.php');
                exit;
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'Registration could not be completed: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Student Registration';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="max-w-2xl mx-auto px-4 py-12">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center text-white mx-auto shadow-md shadow-blue-500/30 mb-3">
                <i data-lucide="user-plus" class="w-6 h-6"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Student Registration</h2>
            <p class="text-xs text-slate-500 mt-1">Create your Smart Hostel resident account (SRS FR-01)</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="e.g. Yana Sareen" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Registration Number *</label>
                    <input type="text" name="reg_no" required value="<?= htmlspecialchars($_POST['reg_no'] ?? '') ?>" placeholder="e.g. 2427030570" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address *</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="student@university.edu" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone Number *</label>
                    <input type="tel" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+91 98765 00000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Department *</label>
                    <select name="department" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="Computer Science & Engineering">CSE</option>
                        <option value="Information Technology">IT</option>
                        <option value="Electronics & Communication">ECE</option>
                        <option value="Mechanical Engineering">Mechanical</option>
                        <option value="Civil Engineering">Civil</option>
                        <option value="Biotechnology">Biotech</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Year of Study</label>
                    <select name="year" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year" selected>2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Gender</label>
                    <select name="gender" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="Female" selected>Female</option>
                        <option value="Male">Male</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Emergency Contact (Parent / Guardian Name & Phone)</label>
                <input type="text" name="emergency_contact" value="<?= htmlspecialchars($_POST['emergency_contact'] ?? '') ?>" placeholder="e.g. Mr. R. Sareen (+91 98111 22334)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password *</label>
                    <input type="password" name="password" required placeholder="Minimum 6 characters" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Confirm Password *</label>
                    <input type="password" name="password_confirm" required placeholder="Re-enter password" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Complete Registration
                </button>
            </div>
        </form>

        <div class="mt-6 text-center text-xs text-slate-500">
            Already registered?
            <a href="login.php" class="font-semibold text-blue-600 hover:underline">Sign in to your account</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
