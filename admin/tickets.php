<?php

// admin tickets list

$pageTitle = 'Tickets';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;
$statusFilter = $_GET['status'] ?? '';

$where = '1=1';
$params = [];
if ($statusFilter) {
    $where .= ' AND status = :st';
    $params['st'] = $statusFilter;
}

$stmt = $db->prepare("SELECT * FROM tickets WHERE {$where} ORDER BY FIELD(status, 'open', 'processing', 'resolved', 'closed'), created_at DESC LIMIT 100");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$statusColors = ['open' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'resolved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'closed' => 'bg-gray-100 text-gray-600 dark:bg-gray-800/30 dark:text-gray-400'];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Tickets</h1>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl p-4 mb-6">
    <form method="GET" class="flex gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <option value="">All</option>
                <?php foreach (['open','processing','resolved','closed'] as $s): ?>
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
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">#</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">User</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Subject</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Type</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $i => $t): ?>
                    <tr class="border-b border-gray-50 dark:border-white/5 <?= $i % 2 ? 'bg-gray-50/50 dark:bg-white/[0.02]' : '' ?>">
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400"><?= $t['id'] ?></td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($t['internet_id']) ?></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300"><?= htmlspecialchars($t['subject']) ?></td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs"><?= ucfirst($t['type']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$t['status']] ?? '' ?>"><?= ucfirst($t['status']) ?></span></td>
                        <td class="px-4 py-3 text-xs text-gray-400"><?= date('M j, g:ia', strtotime($t['created_at'])) ?></td>
                        <td class="px-4 py-3"><a href="<?= $adminBase ?>/view-ticket.php?id=<?= $t['id'] ?>" class="text-indigo-600 dark:text-indigo-400 text-xs hover:underline">View</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No tickets found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
