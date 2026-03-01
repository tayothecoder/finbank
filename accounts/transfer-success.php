<?php

// transfer success

// check result
require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

$result = \Core\Session::get('transfer_result');
if (!$result) {
    $baseUrl = \Core\Env::get('BASE_URL', '');
    header('Location: ' . $baseUrl . '/accounts/dashboard.php');
    exit;
}

$pageTitle = 'Transfer Complete';
require_once __DIR__ . '/layout/header.php';

$txn = \Models\Transaction::findById((int) $result['transaction_id']);
$typeLabel = ucfirst($result['type'] ?? 'transfer');
?>

<div class="max-w-lg mx-auto">
    <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-8 text-center">
        <!-- checkmark icon -->
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-xl font-medium text-gray-900 dark:text-white mb-1">Transfer Successful</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Your <?= strtolower($typeLabel) ?> transfer has been processed</p>

        <?php if ($txn): ?>
        <div class="bg-gray-50 dark:bg-white/5 rounded-2xl p-4 mb-6">
            <p class="text-3xl font-light tracking-tighter text-gray-900 dark:text-white mb-1">
                <?= \Helpers\Format::currency($txn['amount'], $txn['currency'] ?? $currency) ?>
            </p>
            <?php if ((float) $txn['fee'] > 0): ?>
                <p class="text-xs text-gray-500 dark:text-gray-400">+ <?= \Helpers\Format::currency($txn['fee'], $txn['currency'] ?? $currency) ?> fee</p>
            <?php endif; ?>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-white/10 text-sm text-left">
            <div class="flex justify-between py-3">
                <span class="text-gray-500 dark:text-gray-400">Reference ID</span>
                <span class="text-gray-900 dark:text-white font-mono text-xs"><?= htmlspecialchars($txn['reference_id']) ?></span>
            </div>
            <div class="flex justify-between py-3">
                <span class="text-gray-500 dark:text-gray-400">Type</span>
                <span class="text-gray-900 dark:text-white"><?= $typeLabel ?></span>
            </div>
            <?php if ($txn['recipient_name']): ?>
            <div class="flex justify-between py-3">
                <span class="text-gray-500 dark:text-gray-400">Recipient</span>
                <span class="text-gray-900 dark:text-white"><?= htmlspecialchars($txn['recipient_name']) ?></span>
            </div>
            <?php endif; ?>
            <div class="flex justify-between py-3">
                <span class="text-gray-500 dark:text-gray-400">Status</span>
                <span class="rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><?= ucfirst($txn['status']) ?></span>
            </div>
            <div class="flex justify-between py-3">
                <span class="text-gray-500 dark:text-gray-400">Date</span>
                <span class="text-gray-900 dark:text-white"><?= \Helpers\Format::dateTime($txn['created_at']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex gap-3 mt-6">
            <?php if ($txn): ?>
            <a href="<?= $baseUrl ?>/accounts/receipt.php?id=<?= $txn['id'] ?>" class="flex-1 py-2.5 rounded-full border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5 text-center">View Receipt</a>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>/accounts/domestic-transfer.php" class="flex-1 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280] text-center">New Transfer</a>
        </div>
    </div>
</div>

<?php
// clear session
\Core\Session::set('transfer_result', null);
require_once __DIR__ . '/layout/footer.php';
?>
