<?php

// user list

$pageTitle = 'Users';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;
$perPage = 25;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = '1=1';
$params = [];

if ($search !== '') {
    $where .= ' AND (first_name LIKE :s OR last_name LIKE :s2 OR email LIKE :s3 OR internet_id LIKE :s4)';
    $like = '%' . $search . '%';
    $params['s'] = $like; $params['s2'] = $like; $params['s3'] = $like; $params['s4'] = $like;
}
if ($statusFilter !== '') {
    $where .= ' AND status = :st';
    $params['st'] = $statusFilter;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM accounts WHERE {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $db->prepare("SELECT id, internet_id, email, first_name, last_name, status, checking_balance, kyc_status, created_at FROM accounts WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$users = $stmt->fetchAll();

$statusColors = ['active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'hold' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'pending' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'blocked' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Users</h1>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name, email, or internet ID" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
                <option value="">All</option>
                <?php foreach (['active','hold','pending','blocked'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Filter</button>
    </form>
</div>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-white/10">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Name</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Email</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Internet ID</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Balance</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">KYC</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Created</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $i => $u): ?>
                    <tr class="border-b border-gray-50 dark:border-white/5 <?= $i % 2 ? 'bg-gray-50/50 dark:bg-white/[0.02]' : '' ?>">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs"><?= htmlspecialchars($u['internet_id']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$u['status']] ?? '' ?>"><?= ucfirst($u['status']) ?></span></td>
                        <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">$<?= number_format((float) $u['checking_balance'], 2) ?></td>
                        <td class="px-4 py-3"><span class="text-xs <?= $u['kyc_status'] === 'approved' ? 'text-green-600 dark:text-green-400' : ($u['kyc_status'] === 'pending' ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400') ?>"><?= ucfirst($u['kyc_status'] ?? 'none') ?></span></td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= $adminBase ?>/view-user.php?id=<?= $u['id'] ?>" class="text-indigo-600 dark:text-indigo-400 text-xs hover:underline mr-2">View</a>
                            <a href="<?= $adminBase ?>/edit-user.php?id=<?= $u['id'] ?>" class="text-indigo-600 dark:text-indigo-400 text-xs hover:underline">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 flex items-center justify-between border-t border-gray-100 dark:border-white/10">
            <span class="text-xs text-gray-500 dark:text-gray-400"><?= $total ?> total</span>
            <div class="flex gap-1">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="px-3 py-1 rounded-lg text-xs <?= $p === $page ? 'bg-[#1e0e62] text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
