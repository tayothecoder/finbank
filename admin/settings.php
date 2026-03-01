<?php

// site settings

$pageTitle = 'Settings';
require_once __DIR__ . '/includes/auth.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $data = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_email' => trim($_POST['site_email'] ?? ''),
        'site_phone' => trim($_POST['site_phone'] ?? ''),
        'site_address' => trim($_POST['site_address'] ?? ''),
        'site_url' => trim($_POST['site_url'] ?? ''),
        'currency' => trim($_POST['currency'] ?? 'USD'),
        'wire_limit' => (float) ($_POST['wire_limit'] ?? 50000),
        'domestic_limit' => (float) ($_POST['domestic_limit'] ?? 10000),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
    ];

    if (\Models\Setting::update($data)) {
        \Models\AuditLog::log('admin:' . $adminUser['email'], 'settings_updated', 'Site settings updated');
        $success = 'Settings saved.';
    } else {
        $error = 'Failed to update settings.';
    }
}

$settings = \Models\Setting::get();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Settings</h1>

<?php if ($success): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
    <?= \Core\Security::csrfField() ?>
    <div class="grid md:grid-cols-2 gap-4 mb-4">
        <?php
        $fields = [
            ['site_name', 'Site Name', 'text'],
            ['site_email', 'Site Email', 'email'],
            ['site_phone', 'Phone', 'text'],
            ['site_url', 'Site URL', 'url'],
            ['currency', 'Currency', 'text'],
            ['wire_limit', 'Wire Transfer Limit', 'number'],
            ['domestic_limit', 'Domestic Transfer Limit', 'number'],
        ];
        foreach ($fields as $f): ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= $f[1] ?></label>
                <input type="<?= $f[2] ?>" name="<?= $f[0] ?>" value="<?= htmlspecialchars($settings[$f[0]] ?? '') ?>" <?= $f[2] === 'number' ? 'step="0.01"' : '' ?> class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
            </div>
        <?php endforeach; ?>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Address</label>
            <textarea name="site_address" rows="2" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none resize-none"><?= htmlspecialchars($settings['site_address'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mb-4">
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?> class="rounded">
            Maintenance Mode
        </label>
    </div>
    <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Save Settings</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
