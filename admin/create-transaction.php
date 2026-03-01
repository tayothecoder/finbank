<?php

// create transaction

$pageTitle = 'Create Transaction';
require_once __DIR__ . '/includes/auth.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        $error = 'Invalid request.';
    } else {
        $internetId = trim($_POST['internet_id'] ?? '');
        $type = $_POST['type'] ?? '';
        $amount = (float) ($_POST['amount'] ?? 0);
        $status = $_POST['status'] ?? 'completed';
        $description = trim($_POST['description'] ?? '');
        $transType = $_POST['trans_type'] ?? '';

        $account = \Models\Account::findByInternetId($internetId);
        if (!$account) {
            $error = 'User not found.';
        } elseif ($amount <= 0) {
            $error = 'Amount must be greater than zero.';
        } elseif (!in_array($transType, ['credit', 'debit'], true)) {
            $error = 'Invalid transaction direction.';
        } else {
            $db = $conn;
            $db->beginTransaction();
            try {
                $refId = 'TXN' . strtoupper(bin2hex(random_bytes(8)));
                \Models\Transaction::create([
                    'internet_id' => $internetId,
                    'type' => $type,
                    'amount' => $amount,
                    'status' => $status,
                    'reference_id' => $refId,
                    'description' => $description,
                    'trans_type' => $transType,
                ]);

                // update bal
                if ($status === 'completed') {
                    $delta = $transType === 'credit' ? $amount : -$amount;
                    $stmt = $db->prepare('UPDATE accounts SET checking_balance = checking_balance + :d WHERE internet_id = :iid');
                    $stmt->execute(['d' => $delta, 'iid' => $internetId]);
                }

                $db->commit();
                \Models\AuditLog::log('admin:' . $adminUser['email'], 'create_transaction', "Created {$transType} {$type} of \${$amount} for {$internetId}");
                $success = 'Transaction created successfully.';
            } catch (\Exception $e) {
                $db->rollBack();
                $error = 'Failed to create transaction.';
                error_log('create-transaction error: ' . $e->getMessage());
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">Create Transaction</h1>

<?php if ($success): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= $error ?></div>
<?php endif; ?>

<form method="POST" class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
    <?= \Core\Security::csrfField() ?>
    <div class="grid md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Internet ID</label>
            <input type="text" name="internet_id" required value="<?= htmlspecialchars($_POST['internet_id'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
            <select name="type" required class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <?php foreach (['domestic','wire','self','inter','deposit','withdrawal','loan','card','funding'] as $t): ?>
                    <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Amount</label>
            <input type="number" step="0.01" name="amount" required class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Direction</label>
            <select name="trans_type" required class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <option value="credit">Credit</option>
                <option value="debit">Debit</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <?php foreach (['completed','pending','processing','failed'] as $s): ?>
                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description</label>
            <input type="text" name="description" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>" class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none">
        </div>
    </div>
    <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Create Transaction</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
