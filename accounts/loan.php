<?php

// loans

$pageTitle = 'Loans';
require_once __DIR__ . '/layout/header.php';

$internetId = $user['internet_id'];
$error = '';
$success = '';

// handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $amount = (float) ($_POST['amount'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');
    $duration = (int) ($_POST['duration'] ?? 0);

    if ($amount < 100) {
        $error = 'Minimum loan amount is $100.';
    } elseif (empty($purpose)) {
        $error = 'Please provide a purpose for the loan.';
    } elseif ($duration < 1 || $duration > 60) {
        $error = 'Duration must be between 1 and 60 months.';
    } else {
        $ref = 'LOAN-' . strtoupper(bin2hex(random_bytes(6)));
        $txId = \Models\Transaction::create([
            'internet_id'  => $internetId,
            'type'         => 'loan',
            'amount'       => $amount,
            'fee'          => 0,
            'status'       => 'pending',
            'reference_id' => $ref,
            'description'  => $purpose . ' (' . $duration . ' months)',
            'trans_type'   => 'credit',
        ]);
        if ($txId) {
            $success = 'Loan request submitted for review. Reference: ' . $ref;
        } else {
            $error = 'Failed to submit loan request.';
        }
    }
}

// loan history
$loans = \Models\Transaction::getByUser($internetId, 50, 0, ['type' => 'loan']);
$loanBalance = (float) ($user['loan_balance'] ?? 0);
?>

<div class="mb-6">
    <h1 class="text-xl font-medium tracking-tighter text-gray-900 dark:text-white">Loans</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Request and manage loans</p>
</div>

<?php if ($error): ?>
<div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- loan balance -->
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Current Loan Balance</p>
    <p class="text-2xl font-light text-gray-900 dark:text-white"><?= \Helpers\Format::currency($loanBalance, $currency) ?></p>
</div>

<!-- request form -->
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 mb-8">
    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Request a Loan</h2>
    <form method="POST" class="space-y-4">
        <?= \Core\Security::csrfField() ?>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Amount (USD)</label>
            <input type="number" name="amount" min="100" step="0.01" required class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Purpose</label>
            <textarea name="purpose" rows="3" required maxlength="500" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white text-sm resize-none"></textarea>
        </div>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Duration (months)</label>
            <select name="duration" required class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white text-sm">
                <?php foreach ([6, 12, 24, 36, 48, 60] as $m): ?>
                <option value="<?= $m ?>"><?= $m ?> months</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Submit Request</button>
    </form>
</div>

<!-- loan history -->
<?php if (!empty($loans)): ?>
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Loan History</h2>
    <div class="divide-y divide-gray-100 dark:divide-white/10">
        <?php foreach ($loans as $loan): ?>
        <div class="py-3 flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-900 dark:text-white"><?= \Helpers\Format::currency($loan['amount'], $currency) ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars(\Helpers\Format::truncate($loan['description'] ?? '', 60)) ?></p>
            </div>
            <div class="text-right">
                <span class="text-xs px-2 py-1 rounded-full <?= $loan['status'] === 'completed' ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400' : ($loan['status'] === 'pending' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400') ?>"><?= ucfirst($loan['status']) ?></span>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= \Helpers\Format::date($loan['created_at']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
