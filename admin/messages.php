<?php

// admin messages

$pageTitle = 'Messages';
require_once __DIR__ . '/includes/auth.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $internetId = trim($_POST['internet_id'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $account = \Models\Account::findByInternetId($internetId);
    if (!$account) {
        $error = 'User not found.';
    } elseif ($message === '') {
        $error = 'Message cannot be empty.';
    } else {
        \Models\AuditLog::log($internetId, 'admin_message', $message);
        \Models\AuditLog::log('admin:' . $adminUser['email'], 'send_message', "Message sent to {$internetId}");
        $success = 'Message sent to ' . htmlspecialchars($internetId) . '.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Send Message</h1>

<?php if ($success): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
    <?= \Core\Security::csrfField() ?>
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Internet ID</label>
            <input type="text" name="internet_id" required value="<?= htmlspecialchars($_POST['internet_id'] ?? '') ?>" placeholder="Enter user internet ID" class="w-full max-w-sm px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Message</label>
            <textarea name="message" rows="5" required placeholder="Compose your message..." class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none resize-none"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Send Message</button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
