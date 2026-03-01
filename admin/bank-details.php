<?php

// bank details

$pageTitle = 'Bank Details';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;
$success = '';
$error = '';

// fields
$bankFields = ['bank_name', 'bank_account_name', 'bank_account_number', 'bank_routing_number', 'bank_swift_code', 'bank_address'];

// load current settings
$stmt = $db->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    // save to json file
    $bankData = [];
    foreach ($bankFields as $f) {
        $bankData[$f] = trim($_POST[$f] ?? '');
    }

    $bankFile = __DIR__ . '/../config/bank_details.json';
    $configDir = dirname($bankFile);
    if (!is_dir($configDir)) mkdir($configDir, 0750, true);

    if (file_put_contents($bankFile, json_encode($bankData, JSON_PRETTY_PRINT))) {
        \Models\AuditLog::log('admin:' . $adminUser['email'], 'bank_details_updated', 'Bank details updated');
        $success = 'Bank details updated.';
    } else {
        $error = 'Failed to save bank details.';
    }
}

// load bank details
$bankFile = __DIR__ . '/../config/bank_details.json';
$bankData = [];
if (file_exists($bankFile)) {
    $bankData = json_decode(file_get_contents($bankFile), true) ?: [];
}

$labels = [
    'bank_name' => 'Bank Name',
    'bank_account_name' => 'Account Name',
    'bank_account_number' => 'Account Number',
    'bank_routing_number' => 'Routing Number',
    'bank_swift_code' => 'SWIFT Code',
    'bank_address' => 'Bank Address',
];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Bank Details</h1>

<?php if ($success): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
    <?= \Core\Security::csrfField() ?>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">These details are shown to users for bank deposit payments.</p>
    <div class="grid md:grid-cols-2 gap-4 mb-4">
        <?php foreach ($labels as $key => $label): ?>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= $label ?></label>
                <?php if ($key === 'bank_address'): ?>
                    <textarea name="<?= $key ?>" rows="2" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none resize-none"><?= htmlspecialchars($bankData[$key] ?? '') ?></textarea>
                <?php else: ?>
                    <input type="text" name="<?= $key ?>" value="<?= htmlspecialchars($bankData[$key] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Save Bank Details</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
