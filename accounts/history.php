<?php

// tx history

$pageTitle = 'Transaction History';
require_once __DIR__ . '/layout/header.php';

$internetId = $user['internet_id'];
$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// filters
$filters = [];
$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterFrom = $_GET['date_from'] ?? '';
$filterTo = $_GET['date_to'] ?? '';

if ($filterType && $filterType !== 'all') $filters['type'] = $filterType;
if ($filterStatus && $filterStatus !== 'all') $filters['status'] = $filterStatus;
if ($filterFrom) $filters['date_from'] = $filterFrom;
if ($filterTo) $filters['date_to'] = $filterTo;

$total = \Models\Transaction::countByUser($internetId, $filters);
$transactions = \Models\Transaction::getByUser($internetId, $perPage, $offset, $filters);
$totalPages = max(1, (int) ceil($total / $perPage));

// pagination qs
function filterQuery(array $extra = []): string {
    $params = array_filter([
        'type' => $_GET['type'] ?? '', 'status' => $_GET['status'] ?? '',
        'date_from' => $_GET['date_from'] ?? '', 'date_to' => $_GET['date_to'] ?? '',
    ]);
    return http_build_query(array_merge($params, $extra));
}

function statusBadgeH(string $status): string {
    $colors = [
        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    ];
    $c = $colors[$status] ?? 'bg-gray-100 text-gray-700';
    return '<span class="rounded-full px-3 py-1 text-xs font-medium ' . $c . '">' . ucfirst($status) . '</span>';
}
?>

<h1 class="text-2xl font-medium tracking-tighter text-gray-900 dark:text-white mb-6">Transaction History</h1>

<!-- filters -->
<form method="GET" class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</label>
            <select name="type" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
                <?php foreach (['all','domestic','wire','self','deposit','withdrawal'] as $t): ?>
                    <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
            <select name="status" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
                <?php foreach (['all','completed','pending','processing','failed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">From</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filterFrom) ?>" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">To</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filterTo) ?>" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
        </div>
    </div>
    <div class="mt-4 flex gap-3">
        <button type="submit" class="px-5 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium">Filter</button>
        <a href="<?= $baseUrl ?>/accounts/history.php" class="px-5 py-2 rounded-full border border-gray-200 dark:border-white/10 text-sm font-medium text-gray-700 dark:text-gray-300">Clear</a>
    </div>
</form>

<!-- results -->
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm overflow-hidden">
    <?php if (empty($transactions)): ?>
        <p class="p-6 text-sm text-gray-500 dark:text-gray-400">No transactions found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                    <th class="px-6 py-3">Date</th><th class="px-6 py-3">Description</th><th class="px-6 py-3">Type</th>
                    <th class="px-6 py-3">Amount</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Reference</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
            <?php foreach ($transactions as $tx): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300 whitespace-nowrap"><?= \Helpers\Format::date($tx['created_at'], 'M d, Y') ?></td>
                    <td class="px-6 py-4 text-gray-900 dark:text-white"><?= htmlspecialchars($tx['description'] ?? ucfirst($tx['type']) . ' transfer') ?></td>
                    <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300"><?= ucfirst($tx['type']) ?></span></td>
                    <td class="px-6 py-4 font-medium <?= $tx['trans_type'] === 'credit' ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $tx['trans_type'] === 'credit' ? '+' : '-' ?><?= \Helpers\Format::currency($tx['amount'], $tx['currency'] ?? $currency) ?>
                    </td>
                    <td class="px-6 py-4"><?= statusBadgeH($tx['status']) ?></td>
                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">
                        <a href="<?= $baseUrl ?>/accounts/receipt.php?id=<?= $tx['id'] ?>" class="hover:underline"><?= htmlspecialchars($tx['reference_id']) ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>

    <!-- pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-100 dark:border-white/10 flex items-center justify-between">
        <span class="text-sm text-gray-500 dark:text-gray-400">Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> results)</span>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
                <a href="?<?= filterQuery(['page' => $page - 1]) ?>" class="px-3 py-1 rounded-lg border border-gray-200 dark:border-white/10 text-sm text-gray-700 dark:text-gray-300">Previous</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?<?= filterQuery(['page' => $page + 1]) ?>" class="px-3 py-1 rounded-lg border border-gray-200 dark:border-white/10 text-sm text-gray-700 dark:text-gray-300">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
