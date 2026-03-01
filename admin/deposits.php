<?php

// admin deposits

$pageTitle = 'Deposits';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;

// handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $txId = (int) ($_POST['tx_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $tx = \Models\Transaction::findById($txId);

    if ($tx && $tx['type'] === 'deposit' && $tx['status'] === 'pending') {
        if ($action === 'approve') {
            $db->beginTransaction();
            try {
                \Models\Transaction::updateStatus($txId, 'completed');
                $stmt = $db->prepare('UPDATE accounts SET checking_balance = checking_balance + :amt WHERE internet_id = :iid');
                $stmt->execute(['amt' => $tx['amount'], 'iid' => $tx['internet_id']]);
                $db->commit();
                \Models\AuditLog::log('admin:' . $adminUser['email'], 'deposit_approved', "Deposit #{$txId} for {$tx['internet_id']} - \${$tx['amount']}");
            } catch (\Exception $e) {
                $db->rollBack();
                error_log('deposit approve error: ' . $e->getMessage());
            }
        } elseif ($action === 'reject') {
            \Models\Transaction::updateStatus($txId, 'failed');
            \Models\AuditLog::log('admin:' . $adminUser['email'], 'deposit_rejected', "Deposit #{$txId} for {$tx['internet_id']}");
        }
    }
    header('Location: ' . $adminBase . '/deposits.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM transactions WHERE type = 'deposit' ORDER BY FIELD(status, 'pending', 'processing', 'completed', 'failed'), created_at DESC LIMIT 100");
$stmt->execute();
$deposits = $stmt->fetchAll();

$statusColors = ['completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'];

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Deposits</h1>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-white/10">
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">User</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Amount</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Reference</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deposits as $i => $d): ?>
                    <tr class="border-b border-gray-50 dark:border-white/5 <?= $i % 2 ? 'bg-gray-50/50 dark:bg-white/[0.02]' : '' ?>">
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($d['internet_id']) ?></td>
                        <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">$<?= number_format((float) $d['amount'], 2) ?></td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($d['reference_id']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$d['status']] ?? '' ?>"><?= ucfirst($d['status']) ?></span></td>
                        <td class="px-4 py-3 text-xs text-gray-400"><?= date('M j, g:ia', strtotime($d['created_at'])) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($d['status'] === 'pending'): ?>
                                <form method="POST" class="flex gap-1">
                                    <?= \Core\Security::csrfField() ?>
                                    <input type="hidden" name="tx_id" value="<?= $d['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="px-2 py-1 rounded-lg bg-green-600 text-white text-xs hover:bg-green-700">Approve</button>
                                    <button type="submit" name="action" value="reject" class="px-2 py-1 rounded-lg bg-red-600 text-white text-xs hover:bg-red-700">Reject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($deposits)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No deposits found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
