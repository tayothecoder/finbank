<?php

// admin dashboard

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;

// gather stats
$totalUsers = (int) $db->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
$activeUsers = (int) $db->query("SELECT COUNT(*) FROM accounts WHERE status = 'active'")->fetchColumn();
$totalDeposits = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit' AND status = 'completed'")->fetchColumn();
$totalWithdrawals = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'withdrawal' AND status = 'completed'")->fetchColumn();
$pendingTxns = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn();
$pendingKyc = (int) $db->query("SELECT COUNT(*) FROM accounts WHERE kyc_status = 'pending'")->fetchColumn();
$openTickets = (int) $db->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open', 'processing')")->fetchColumn();

$recentLogs = \Models\AuditLog::getRecent(20);

$stats = [
    ['Total Users', number_format($totalUsers), 'text-[#1e0e62] dark:text-indigo-300'],
    ['Active Users', number_format($activeUsers), 'text-green-600 dark:text-green-400'],
    ['Total Deposits', '$' . number_format($totalDeposits, 2), 'text-blue-600 dark:text-blue-400'],
    ['Total Withdrawals', '$' . number_format($totalWithdrawals, 2), 'text-amber-600 dark:text-amber-400'],
    ['Pending Transactions', number_format($pendingTxns), 'text-amber-600 dark:text-amber-400'],
    ['Pending KYC', number_format($pendingKyc), 'text-orange-600 dark:text-orange-400'],
    ['Open Tickets', number_format($openTickets), 'text-red-600 dark:text-red-400'],
];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Dashboard</h1>

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
    <?php foreach ($stats as $s): ?>
        <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= $s[0] ?></p>
            <p class="text-2xl font-light <?= $s[2] ?> mt-1"><?= $s[1] ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
    <h2 class="text-lg font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-4">Recent Activity</h2>
    <div class="space-y-2 max-h-96 overflow-y-auto">
        <?php if (empty($recentLogs)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity.</p>
        <?php else: ?>
            <?php foreach ($recentLogs as $log): ?>
                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-white/5 last:border-0">
                    <div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($log['action']) ?></span>
                        <span class="text-xs text-gray-400 ml-2"><?= htmlspecialchars($log['internet_id'] ?? '-') ?></span>
                        <?php if ($log['details']): ?>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-0.5"><?= htmlspecialchars($log['details']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap ml-4"><?= date('M j, g:ia', strtotime($log['created_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
