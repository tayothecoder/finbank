<?php

// view user

$pageTitle = 'View User';
require_once __DIR__ . '/includes/auth.php';

$userId = (int) ($_GET['id'] ?? 0);
$user = \Models\Account::findById($userId);
if (!$user) {
    header('Location: ' . $adminBase . '/users.php');
    exit;
}

// status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['active', 'hold', 'pending', 'blocked'], true)) {
        \Models\Account::update($userId, ['status' => $newStatus]);
        \Models\AuditLog::log('admin:' . $adminUser['email'], 'user_status_change', "User {$user['internet_id']} status changed to {$newStatus}");
        header('Location: ' . $adminBase . '/view-user.php?id=' . $userId . '&msg=updated');
        exit;
    }
}

$user = \Models\Account::findById($userId);
$transactions = \Models\Transaction::getByUser($user['internet_id'], 10);
$statusColors = ['active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'hold' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'pending' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'blocked' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
    <a href="<?= $adminBase ?>/edit-user.php?id=<?= $userId ?>" class="px-4 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Edit User</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm">User updated.</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
        <h2 class="text-base font-medium text-[#1e0e62] dark:text-white mb-4">Account Details</h2>
        <?php
        $fields = [
            'Internet ID' => $user['internet_id'],
            'Email' => $user['email'],
            'Phone' => $user['phone'] ?: '-',
            'Status' => '<span class="px-2 py-0.5 rounded-full text-xs font-medium ' . ($statusColors[$user['status']] ?? '') . '">' . ucfirst($user['status']) . '</span>',
            'KYC Status' => ucfirst($user['kyc_status'] ?? 'none'),
            'Transfer Enabled' => $user['transfer_enabled'] ? 'Yes' : 'No',
            'Manager' => ($user['manager_name'] ?: '-'),
            'Created' => date('M j, Y g:ia', strtotime($user['created_at'])),
        ];
        foreach ($fields as $label => $val): ?>
            <div class="flex justify-between py-2 border-b border-gray-50 dark:border-white/5 last:border-0">
                <span class="text-sm text-gray-500 dark:text-gray-400"><?= $label ?></span>
                <span class="text-sm text-gray-800 dark:text-gray-200"><?= $val ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
        <h2 class="text-base font-medium text-[#1e0e62] dark:text-white mb-4">Balances</h2>
        <?php foreach (['checking_balance' => 'Checking', 'savings_balance' => 'Savings', 'loan_balance' => 'Loan'] as $col => $label): ?>
            <div class="flex justify-between py-2 border-b border-gray-50 dark:border-white/5 last:border-0">
                <span class="text-sm text-gray-500 dark:text-gray-400"><?= $label ?></span>
                <span class="text-lg font-light text-gray-800 dark:text-gray-200">$<?= number_format((float) $user[$col], 2) ?></span>
            </div>
        <?php endforeach; ?>

        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-6 mb-2">Change Status</h3>
        <form method="POST" class="flex gap-2">
            <?= \Core\Security::csrfField() ?>
            <select name="status" class="flex-1 px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <?php foreach (['active','hold','pending','blocked'] as $s): ?>
                    <option value="<?= $s ?>" <?= $user['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Update</button>
        </form>
    </div>
</div>

<?php
// kyc docs
$kycDocs = ['id_front' => 'ID Front', 'id_back' => 'ID Back', 'proof_of_address' => 'Proof of Address'];
$hasKyc = !empty($user['id_front']) || !empty($user['id_back']) || !empty($user['proof_of_address']);
if ($hasKyc): ?>
<div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6 mb-6">
    <h2 class="text-base font-medium text-[#1e0e62] dark:text-white mb-4">KYC Documents</h2>
    <div class="grid sm:grid-cols-3 gap-4">
        <?php foreach ($kycDocs as $field => $label): ?>
            <?php if (!empty($user[$field])): ?>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2"><?= $label ?></p>
                    <?php
                    $ext = strtolower(pathinfo($user[$field], PATHINFO_EXTENSION));
                    $filePath = $baseUrl . '/uploads/kyc/' . $user[$field];
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                        <img src="<?= htmlspecialchars($filePath) ?>" alt="<?= $label ?>" class="rounded-xl max-h-48 w-full object-cover border border-gray-200 dark:border-white/10">
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($filePath) ?>" target="_blank" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline">View PDF</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
    <h2 class="text-base font-medium text-[#1e0e62] dark:text-white mb-4">Recent Transactions</h2>
    <?php if (empty($transactions)): ?>
        <p class="text-sm text-gray-400">No transactions yet.</p>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($transactions as $tx): ?>
                <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-white/5 last:border-0">
                    <div>
                        <span class="text-sm text-gray-800 dark:text-gray-200"><?= ucfirst($tx['type']) ?></span>
                        <span class="text-xs text-gray-400 ml-2"><?= htmlspecialchars($tx['reference_id']) ?></span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm <?= $tx['trans_type'] === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                            <?= $tx['trans_type'] === 'credit' ? '+' : '-' ?>$<?= number_format((float) $tx['amount'], 2) ?>
                        </span>
                        <span class="text-xs text-gray-400 ml-2"><?= ucfirst($tx['status']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
