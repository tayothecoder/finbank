<?php

// user dashboard - balances, recent transactions, quick actions

$pageTitle = 'Dashboard';
require_once __DIR__ . '/layout/header.php';

$internetId = $user['internet_id'];
$checking = (float) $user['checking_balance'];
$savings = (float) $user['savings_balance'];
$loan = (float) $user['loan_balance'];
$total = $checking + $savings;

$stats = \Models\Transaction::getMonthlyStats($internetId);
$netThis = $stats['credits_this'] - $stats['debits_this'];
$netLast = $stats['credits_last'] - $stats['debits_last'];

// percentage change helper
function pctChange(float $current, float $previous): string {
    if ($previous == 0) return $current > 0 ? '+100%' : '0%';
    $pct = (($current - $previous) / abs($previous)) * 100;
    $sign = $pct >= 0 ? '+' : '';
    return $sign . number_format($pct, 1) . '%';
}

$monthlyChange = pctChange($netThis, $netLast);
$transactions = \Models\Transaction::getRecent($internetId, 10);

// status badge helper
function statusBadge(string $status): string {
    $colors = [
        'completed'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'pending'    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'failed'     => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    ];
    $c = $colors[$status] ?? 'bg-gray-100 text-gray-700';
    return '<span class="rounded-full px-3 py-1 text-xs font-medium ' . $c . '">' . ucfirst($status) . '</span>';
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-medium tracking-tighter text-gray-900 dark:text-white">Welcome back, <?= htmlspecialchars($user['first_name']) ?></h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Account status:
        <span class="<?= $user['status'] === 'active' ? 'text-green-600' : 'text-amber-600' ?> font-medium"><?= ucfirst($user['status']) ?></span>
    </p>
</div>

<!-- balance cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php
    $cards = [
        ['Total Balance', $total, $monthlyChange],
        ['Checking', $checking, null],
        ['Savings', $savings, null],
        ['Loan Balance', $loan, null],
    ];
    foreach ($cards as [$label, $amount, $change]): ?>
    <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= $label ?></p>
        <p class="text-3xl font-light tracking-tighter text-gray-900 dark:text-white mt-2"><?= \Helpers\Format::currency($amount, $currency) ?></p>
        <?php if ($change): ?>
            <p class="text-xs mt-2 <?= str_starts_with($change, '+') ? 'text-green-600' : 'text-red-600' ?>"><?= $change ?> this month</p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- quick actions -->
<div class="flex flex-wrap gap-3 mb-8">
    <?php
    $actions = [
        ['Transfer', $baseUrl . '/accounts/domestic-transfer.php'],
        ['Deposit', $baseUrl . '/accounts/deposit.php'],
        ['Cards', $baseUrl . '/accounts/cards.php'],
        ['Loans', $baseUrl . '/accounts/loan.php'],
    ];
    foreach ($actions as [$label, $href]): ?>
    <a href="<?= $href ?>" class="px-5 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- recent transactions -->
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm">
    <div class="p-6 border-b border-gray-100 dark:border-white/10 flex items-center justify-between">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Transactions</h2>
        <a href="<?= $baseUrl ?>/accounts/history.php" class="text-sm text-[#1e0e62] dark:text-indigo-300">View all</a>
    </div>
    <?php if (empty($transactions)): ?>
        <p class="p-6 text-sm text-gray-500 dark:text-gray-400">No transactions yet.</p>
    <?php else: ?>
        <div class="divide-y divide-gray-100 dark:divide-white/10">
        <?php foreach ($transactions as $tx): ?>
            <div class="px-6 py-4 flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?= htmlspecialchars($tx['description'] ?? ucfirst($tx['type']) . ' transfer') ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= \Helpers\Format::timeAgo($tx['created_at']) ?></p>
                </div>
                <div class="text-right flex items-center gap-3">
                    <span class="text-sm font-medium <?= $tx['trans_type'] === 'credit' ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $tx['trans_type'] === 'credit' ? '+' : '-' ?><?= \Helpers\Format::currency($tx['amount'], $tx['currency'] ?? $currency) ?>
                    </span>
                    <?= statusBadge($tx['status']) ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
