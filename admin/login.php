<?php

// admin login page

require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();
\Core\Security::headers();

$baseUrl = defined('APP_URL') ? APP_URL : '/banking';
$adminBase = $baseUrl . '/admin';

if (\Core\Session::isAdmin()) {
    header('Location: ' . $adminBase . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        $error = 'Invalid request. Please try again.';
    } elseif (!\Core\Security::checkRateLimit('admin_login')) {
        $error = 'Too many attempts. Please wait a few minutes.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $admin = \Models\Admin::findByEmail($email);
        if ($admin && \Models\Admin::verifyPassword($admin, $password)) {
            session_regenerate_id(true);
            \Core\Session::setAdmin($admin['id'], $admin['email']);
            \Models\Admin::updateLastLogin($admin['id']);
            \Models\AuditLog::log('admin:' . $admin['email'], 'admin_login', 'Admin login');
            \Core\Security::resetRateLimit('admin_login');
            header('Location: ' . $adminBase . '/dashboard.php');
            exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= htmlspecialchars(APP_NAME ?? 'Banking') ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/assets/css/style.css">
    <script>if(localStorage.getItem('darkMode')==='true')document.documentElement.classList.add('dark');</script>
</head>
<body class="bg-[#f5f3ff] dark:bg-[#0f0a2e] min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm mx-4">
        <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-8">
            <h1 class="text-xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Admin Login</h1>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <?= \Core\Security::csrfField() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
