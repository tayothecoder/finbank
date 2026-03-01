<?php

// admin transactions

$pageTitle = 'Transactions';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;
$perPage = 25;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$userFilter = trim($_GET['user'] ?? '');

// status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $txId = (int) ($_POST['tx_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    if ($txId && in_array($newStatus, ['pending', 'processing', 'completed', 'failed'], true)) {
        \Models\Transaction::updateStatus($txId, $newStatus);
        \Models\AuditLog::log('admin:' . $adminUser['email'], 'tx_status_change', "Transaction #{$txId} -> {$newStatus}");
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$where = '1=1';
$params = [];
if ($typeFilter) { $where .= ' AND type = :type'; $params['type'] = $typeFilter; }
if ($statusFilter) { $where .= ' AND status = :status'; $params['status'] = $statusFilter; }
if ($dateFrom) { $where .= ' AND created_at >= :df'; $params['df'] = $dateFrom . ' 00:00:00'; }
if ($dateTo) { $where .= ' AND created_at <= :dt'; $params['dt'] = $dateTo . ' 23:59:59'; }
if ($userFilter) { $where .= ' AND internet_id = :uid'; $params['uid'] = $userFilter; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $db->prepare("SELECT * FROM transactions WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$txns = $stmt->fetchAll();

$statusColors = ['completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Transactions</h1>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
            <select name="type" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <option value="">All</option>
                <?php foreach (['domestic','wire','self','inter','deposit','withdrawal','loan','card','funding'] as $t): ?>
                    <option value="<?= $t ?>" <?= $typeFilter === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <option value="">All</option>
                <?php foreach (['pending','processing','completed','failed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">User ID</label>
            <input type="text" name="user" value="<?= htmlspecialchars($userFilter) ?>" placeholder="Internet ID" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white w-32">
        </div>
        <button type="submit" class="px-4 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Filter</button>
    </form>
</div>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-white/10">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Ref</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">User</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Type</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Amount</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Direction</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($txns as $i => $tx): ?>
                    <tr class="border-b border-gray-50 dark:border-white/5 <?= $i % 2 ? 'bg-gray-50/50 dark:bg-white/[0.02]' : '' ?>">
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400"><?= htmlspecialchars($tx['reference_id']) ?></td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($tx['internet_id']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= ucfirst($tx['type']) ?></td>
                        <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">$<?= number_format((float) $tx['amount'], 2) ?></td>
                        <td class="px-4 py-3 text-xs <?= $tx['trans_type'] === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= ucfirst($tx['trans_type']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$tx['status']] ?? '' ?>"><?= ucfirst($tx['status']) ?></span></td>
                        <td class="px-4 py-3 text-xs text-gray-400"><?= date('M j, g:ia', strtotime($tx['created_at'])) ?></td>
                        <td class="px-4 py-3">
                            <form method="POST" class="flex gap-1">
                                <?= \Core\Security::csrfField() ?>
                                <input type="hidden" name="tx_id" value="<?= $tx['id'] ?>">
                                <select name="new_status" class="px-2 py-1 rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-xs text-gray-900 dark:text-white">
                                    <?php foreach (['pending','processing','completed','failed'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $tx['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="px-2 py-1 rounded-lg bg-[#1e0e62] text-white text-xs hover:bg-[#2a1578]">Set</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($txns)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 flex items-center justify-between border-t border-gray-100 dark:border-white/10">
            <span class="text-xs text-gray-500 dark:text-gray-400"><?= $total ?> total</span>
            <div class="flex gap-1">
                <?php for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++): ?>
                    <a href="?page=<?= $p ?>&type=<?= urlencode($typeFilter) ?>&status=<?= urlencode($statusFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&user=<?= urlencode($userFilter) ?>" class="px-3 py-1 rounded-lg text-xs <?= $p === $page ? 'bg-[#1e0e62] text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
