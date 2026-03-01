<?php

// smtp settings

$pageTitle = 'SMTP Settings';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;
$success = '';
$error = '';

// load settings
$stmt = $db->query('SELECT * FROM smtp_settings WHERE id = 1');
$smtp = $stmt->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'test') {
        // test email
        $testTo = trim($_POST['test_email'] ?? $adminUser['email']);
        try {
            $result = \Services\EmailService::send($testTo, 'SMTP Test', '<p>This is a test email from the admin panel.</p>');
            $success = $result ? 'Test email sent to ' . htmlspecialchars($testTo) . '.' : 'Failed to send test email.';
        } catch (\Exception $e) {
            $error = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $data = [
            'host' => trim($_POST['host'] ?? ''),
            'port' => (int) ($_POST['port'] ?? 587),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'from_email' => trim($_POST['from_email'] ?? ''),
            'from_name' => trim($_POST['from_name'] ?? ''),
            'encryption' => $_POST['encryption'] ?? 'tls',
        ];

        if (empty($smtp)) {
            $stmt = $db->prepare('INSERT INTO smtp_settings (id, host, port, username, password, from_email, from_name, encryption) VALUES (1, :h, :p, :u, :pw, :fe, :fn, :enc)');
        } else {
            $stmt = $db->prepare('UPDATE smtp_settings SET host = :h, port = :p, username = :u, password = :pw, from_email = :fe, from_name = :fn, encryption = :enc WHERE id = 1');
        }

        $ok = $stmt->execute([
            'h' => $data['host'], 'p' => $data['port'], 'u' => $data['username'],
            'pw' => $data['password'], 'fe' => $data['from_email'], 'fn' => $data['from_name'],
            'enc' => $data['encryption'],
        ]);

        if ($ok) {
            \Models\AuditLog::log('admin:' . $adminUser['email'], 'smtp_updated', 'SMTP settings updated');
            $success = 'SMTP settings saved.';
            $smtp = $data;
        } else {
            $error = 'Failed to save SMTP settings.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">SMTP Settings</h1>

<?php if ($success): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="bg-white dark:bg-[#1a1045] rounded-3xl p-6 mb-6">
    <?= \Core\Security::csrfField() ?>
    <input type="hidden" name="action" value="save">
    <div class="grid md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">SMTP Host</label>
            <input type="text" name="host" value="<?= htmlspecialchars($smtp['host'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Port</label>
            <input type="number" name="port" value="<?= (int) ($smtp['port'] ?? 587) ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($smtp['username'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Password</label>
            <input type="password" name="password" value="<?= htmlspecialchars($smtp['password'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From Email</label>
            <input type="email" name="from_email" value="<?= htmlspecialchars($smtp['from_email'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From Name</label>
            <input type="text" name="from_name" value="<?= htmlspecialchars($smtp['from_name'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Encryption</label>
            <select name="encryption" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <?php foreach (['tls', 'ssl', 'none'] as $enc): ?>
                    <option value="<?= $enc ?>" <?= ($smtp['encryption'] ?? 'tls') === $enc ? 'selected' : '' ?>><?= strtoupper($enc) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Save SMTP Settings</button>
</form>

<form method="POST" class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
    <?= \Core\Security::csrfField() ?>
    <input type="hidden" name="action" value="test">
    <h3 class="text-base font-medium text-[#1e0e62] dark:text-white mb-3">Test Email</h3>
    <div class="flex gap-3 items-end">
        <div class="flex-1">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Send To</label>
            <input type="email" name="test_email" value="<?= htmlspecialchars($adminUser['email']) ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <button type="submit" class="px-4 py-2 rounded-full border border-gray-200 dark:border-white/10 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5">Send Test</button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
