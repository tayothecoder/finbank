<?php

// admin edit user form

$pageTitle = 'Edit User';
require_once __DIR__ . '/includes/auth.php';

$userId = (int) ($_GET['id'] ?? 0);
$user = \Models\Account::findById($userId);
if (!$user) {
    header('Location: ' . $adminBase . '/users.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        $error = 'Invalid request.';
    } else {
        $db = $conn;
        $db->beginTransaction();
        try {
            // basic fields
            $updates = [
                'first_name' => trim($_POST['first_name'] ?? $user['first_name']),
                'last_name' => trim($_POST['last_name'] ?? $user['last_name']),
                'phone' => trim($_POST['phone'] ?? ''),
                'status' => $_POST['status'] ?? $user['status'],
                'kyc_status' => $_POST['kyc_status'] ?? $user['kyc_status'],
                'transfer_enabled' => isset($_POST['transfer_enabled']) ? 1 : 0,
                'manager_name' => trim($_POST['manager_name'] ?? ''),
                'manager_email' => trim($_POST['manager_email'] ?? ''),
            ];
            \Models\Account::update($userId, $updates);

            // email update
            $newEmail = strtolower(trim($_POST['email'] ?? ''));
            if ($newEmail !== '' && $newEmail !== $user['email']) {
                $stmt = $db->prepare('UPDATE accounts SET email = :e WHERE id = :id');
                $stmt->execute(['e' => $newEmail, 'id' => $userId]);
            }

            // balance edit
            foreach (['checking_balance', 'savings_balance', 'loan_balance'] as $bal) {
                $newVal = $_POST[$bal] ?? null;
                if ($newVal !== null && $newVal !== '') {
                    $newVal = round((float) $newVal, 2);
                    $stmt = $db->prepare("UPDATE accounts SET {$bal} = :v WHERE id = :id");
                    $stmt->execute(['v' => $newVal, 'id' => $userId]);
                }
            }

            $db->commit();

            // log
            \Models\AuditLog::log('admin:' . $adminUser['email'], 'user_edit', "Edited user {$user['internet_id']}");
            $success = 'User updated successfully.';
            $user = \Models\Account::findById($userId);
        } catch (\Exception $e) {
            $db->rollBack();
            $error = 'Failed to update user.';
            error_log('edit-user error: ' . $e->getMessage());
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Edit User - <?= htmlspecialchars($user['internet_id']) ?></h1>

<?php if ($success): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <?= \Core\Security::csrfField() ?>

    <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
        <h2 class="text-base font-medium text-[#1e0e62] dark:text-white mb-4">Personal Information</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <?php
            $fields = [
                ['first_name', 'First Name', 'text', $user['first_name']],
                ['last_name', 'Last Name', 'text', $user['last_name']],
                ['email', 'Email', 'email', $user['email']],
                ['phone', 'Phone', 'text', $user['phone']],
                ['manager_name', 'Manager Name', 'text', $user['manager_name']],
                ['manager_email', 'Manager Email', 'email', $user['manager_email']],
            ];
            foreach ($fields as $f): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= $f[1] ?></label>
                    <input type="<?= $f[2] ?>" name="<?= $f[0] ?>" value="<?= htmlspecialchars($f[3] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
        <h2 class="text-base font-medium text-[#1e0e62] dark:text-white mb-4">Account Settings</h2>
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                    <?php foreach (['active','hold','pending','blocked'] as $s): ?>
                        <option value="<?= $s ?>" <?= $user['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">KYC Status</label>
                <select name="kyc_status" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                    <?php foreach (['none','pending','approved','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($user['kyc_status'] ?? 'none') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center pt-5">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="transfer_enabled" value="1" <?= $user['transfer_enabled'] ? 'checked' : '' ?> class="rounded">
                    Transfer Enabled
                </label>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
        <h2 class="text-base font-medium text-[#1e0e62] dark:text-white mb-4">Balances</h2>
        <div class="grid md:grid-cols-3 gap-4">
            <?php foreach (['checking_balance' => 'Checking', 'savings_balance' => 'Savings', 'loan_balance' => 'Loan'] as $col => $label): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= $label ?> Balance</label>
                    <input type="number" step="0.01" name="<?= $col ?>" value="<?= number_format((float) $user[$col], 2, '.', '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Save Changes</button>
        <a href="<?= $adminBase ?>/view-user.php?id=<?= $userId ?>" class="px-6 py-2.5 rounded-full border border-gray-200 dark:border-white/10 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
