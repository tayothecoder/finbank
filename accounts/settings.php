<?php

// settings

// handle post
require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

$postError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        $postError = 'Invalid request. Please try again.';
    } else {
        $sessionUser = \Core\Session::getUser();
        if ($sessionUser) {
            $conn = \Core\Database::connect();
            $postUser = \Models\Account::findByInternetId($sessionUser['internet_id']);
            $action = $_POST['action'] ?? '';
            $baseUrl = \Core\Env::get('BASE_URL', '');

            if ($action === 'change_password') {
                $current = $_POST['current_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';

                if (!$current || !$new || !$confirm) {
                    $postError = 'All password fields are required.';
                } elseif ($new !== $confirm) {
                    $postError = 'New passwords do not match.';
                } elseif (strlen($new) < 8) {
                    $postError = 'Password must be at least 8 characters.';
                } elseif (!\Models\Account::verifyPassword($postUser, $current)) {
                    $postError = 'Current password is incorrect.';
                } else {
                    \Models\Account::updatePassword($postUser['id'], password_hash($new, PASSWORD_DEFAULT));
                    \Core\Session::flash('success', 'Password updated.');
                    header('Location: ' . $baseUrl . '/accounts/settings.php');
                    exit;
                }
            }

            if ($action === 'change_pin') {
                $currentPin = $_POST['current_pin'] ?? '';
                $newPin = $_POST['new_pin'] ?? '';
                $confirmPin = $_POST['confirm_pin'] ?? '';

                if (!$currentPin || !$newPin || !$confirmPin) {
                    $postError = 'All PIN fields are required.';
                } elseif ($newPin !== $confirmPin) {
                    $postError = 'New PINs do not match.';
                } elseif (!preg_match('/^\d{4,6}$/', $newPin)) {
                    $postError = 'PIN must be 4-6 digits.';
                } elseif (!\Models\Account::verifyPin($postUser, $currentPin)) {
                    $postError = 'Current PIN is incorrect.';
                } else {
                    \Models\Account::updatePin($postUser['id'], password_hash($newPin, PASSWORD_DEFAULT));
                    \Core\Session::flash('success', 'PIN updated.');
                    header('Location: ' . $baseUrl . '/accounts/settings.php');
                    exit;
                }
            }

            if ($action === 'toggle_2fa') {
                $enabled = $postUser['two_fa_enabled'] ? 0 : 1;
                $db = \Core\Database::connect();
                $stmt = $db->prepare('UPDATE accounts SET two_fa_enabled = :e WHERE id = :id');
                $stmt->execute(['e' => $enabled, 'id' => $postUser['id']]);
                \Core\Session::flash('success', $enabled ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.');
                header('Location: ' . $baseUrl . '/accounts/settings.php');
                exit;
            }
        }
    }

    // flash err
    if ($postError) {
        \Core\Session::flash('error', $postError);
    }
}

$pageTitle = 'Settings';
require_once __DIR__ . '/layout/header.php';

$success = \Core\Session::getFlash('success');
$error = \Core\Session::getFlash('error');
?>

<h1 class="text-2xl font-medium tracking-tighter text-gray-900 dark:text-white mb-6">Settings</h1>

<?php if ($success): ?>
    <div class="mb-4 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="space-y-6">
    <!-- change password -->
    <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Change Password</h2>
        <form method="POST" class="space-y-4 max-w-md">
            <?= \Core\Security::csrfField() ?>
            <input type="hidden" name="action" value="change_password">
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Password</label>
                <input type="password" name="current_password" required class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">New Password</label>
                <input type="password" name="new_password" required minlength="8" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirm New Password</label>
                <input type="password" name="confirm_password" required class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-5 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium">Update Password</button>
        </form>
    </div>

    <!-- change pin -->
    <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Change PIN</h2>
        <form method="POST" class="space-y-4 max-w-md">
            <?= \Core\Security::csrfField() ?>
            <input type="hidden" name="action" value="change_pin">
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Current PIN</label>
                <input type="password" name="current_pin" required maxlength="6" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">New PIN</label>
                <input type="password" name="new_pin" required maxlength="6" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirm New PIN</label>
                <input type="password" name="confirm_pin" required maxlength="6" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-5 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium">Update PIN</button>
        </form>
    </div>

    <!-- two-factor auth -->
    <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Two-Factor Authentication</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Status: <span class="font-medium <?= $user['two_fa_enabled'] ? 'text-green-600' : 'text-gray-700 dark:text-gray-300' ?>"><?= $user['two_fa_enabled'] ? 'Enabled' : 'Disabled' ?></span>
        </p>
        <form method="POST">
            <?= \Core\Security::csrfField() ?>
            <input type="hidden" name="action" value="toggle_2fa">
            <button type="submit" class="px-5 py-2 rounded-full <?= $user['two_fa_enabled'] ? 'bg-red-600 hover:bg-red-700' : 'bg-[#1e0e62] hover:bg-[#2a1280]' ?> text-white text-sm font-medium">
                <?= $user['two_fa_enabled'] ? 'Disable 2FA' : 'Enable 2FA' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
