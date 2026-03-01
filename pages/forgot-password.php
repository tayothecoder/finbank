<?php

require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();
\Core\Security::headers();

$baseUrl = defined('APP_URL') ? APP_URL : '/banking';
$error = '';
$success = '';

// reset pw
$token = $_GET['token'] ?? null;

if ($token && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        $error = 'Invalid request.';
    } else {
        $result = \Services\AuthService::resetPassword($token, $_POST['password'] ?? '');
        if ($result['ok']) {
            \Core\Session::flash('success', 'Password has been reset. Please sign in.');
            header('Location: ' . $baseUrl . '/pages/login.php');
            exit;
        }
        $error = $result['error'];
    }
}

// forgot pw
if (!$token && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        $error = 'Invalid request.';
    } elseif (!\Core\Security::checkRateLimit('forgot_password')) {
        $error = 'Too many requests. Please wait.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $result = \Services\AuthService::forgotPassword($email);

        if ($result['ok'] && isset($result['token'])) {
            $link = $baseUrl . '/pages/forgot-password.php?token=' . $result['token'];
            \Services\EmailService::sendPasswordReset(
                $result['user']['email'],
                $result['user']['first_name'],
                $link
            );
        }
        // prevent enumeration
        $success = 'If an account with that email exists, a reset link has been sent.';
    }
}

$pageTitle = $token ? 'Reset Password' : 'Forgot Password';
require_once __DIR__ . '/layout/header.php';
?>

<main class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="w-full max-w-md">
        <?php if ($token): ?>
        <h1 class="text-3xl font-light tracking-tighter mb-2">Reset password</h1>
        <p class="text-sm opacity-50 mb-8">Enter your new password</p>

        <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm rounded-2xl px-5 py-3 mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= \Core\Security::csrfField() ?>
            <div class="space-y-4">
                <div>
                    <label class="font-medium text-sm block mb-1.5">New Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                </div>
                <div>
                    <label class="font-medium text-sm block mb-1.5">Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="8"
                        class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                </div>
            </div>
            <button type="submit" class="w-full rounded-full bg-indigo dark:bg-white dark:text-indigo text-white px-6 py-3 font-medium text-sm mt-6">Reset Password</button>
        </form>

        <?php else: ?>
        <h1 class="text-3xl font-light tracking-tighter mb-2">Forgot password</h1>
        <p class="text-sm opacity-50 mb-8">We will send you a reset link</p>

        <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm rounded-2xl px-5 py-3 mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm rounded-2xl px-5 py-3 mb-6"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= \Core\Security::csrfField() ?>
            <div>
                <label class="font-medium text-sm block mb-1.5">Email Address</label>
                <input type="email" name="email" required
                    class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
            </div>
            <button type="submit" class="w-full rounded-full bg-indigo dark:bg-white dark:text-indigo text-white px-6 py-3 font-medium text-sm mt-6">Send Reset Link</button>
            <p class="mt-4 text-sm opacity-50 text-center">
                <a href="<?= $baseUrl ?>/pages/login.php" class="hover:opacity-100">Back to sign in</a>
            </p>
        </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
