<?php

// admin cards

$pageTitle = 'Cards';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;

// handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $cardId = (int) ($_POST['card_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $card = \Models\Card::findById($cardId);

    if ($card && $card['status'] === 'pending') {
        if ($action === 'approve') {
            \Models\Card::updateStatus($cardId, 'active');
            \Models\AuditLog::log('admin:' . $adminUser['email'], 'card_approved', "Card #{$cardId} for {$card['internet_id']}");
        } elseif ($action === 'reject') {
            \Models\Card::updateStatus($cardId, 'cancelled');
            \Models\AuditLog::log('admin:' . $adminUser['email'], 'card_rejected', "Card #{$cardId} for {$card['internet_id']}");
        }
    }
    header('Location: ' . $adminBase . '/cards.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM cards ORDER BY FIELD(status, 'pending', 'active', 'frozen', 'cancelled'), created_at DESC LIMIT 100");
$stmt->execute();
$cards = $stmt->fetchAll();

$statusColors = ['active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'frozen' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Cards</h1>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-white/10">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">User</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Card Name</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Type</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Expiry</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cards as $i => $c): ?>
                    <tr class="border-b border-gray-50 dark:border-white/5 <?= $i % 2 ? 'bg-gray-50/50 dark:bg-white/[0.02]' : '' ?>">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($c['internet_id']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($c['card_name']) ?></td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?= ucfirst($c['card_type']) ?></td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs"><?= htmlspecialchars($c['expiry_date']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$c['status']] ?? '' ?>"><?= ucfirst($c['status']) ?></span></td>
                        <td class="px-4 py-3 text-xs text-gray-400"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($c['status'] === 'pending'): ?>
                                <form method="POST" class="flex gap-1">
                                    <?= \Core\Security::csrfField() ?>
                                    <input type="hidden" name="card_id" value="<?= $c['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="px-2 py-1 rounded-lg bg-green-600 text-white text-xs hover:bg-green-700">Approve</button>
                                    <button type="submit" name="action" value="reject" class="px-2 py-1 rounded-lg bg-red-600 text-white text-xs hover:bg-red-700">Reject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($cards)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No cards found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
