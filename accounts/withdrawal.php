<?php

// withdrawal

// handle post
require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $sessionUser = \Core\Session::getUser();
    if ($sessionUser) {
        $conn = \Core\Database::connect();
        $postUser = \Models\Account::findByInternetId($sessionUser['internet_id']);
        $internetId = $postUser['internet_id'];

        $amount = (float) ($_POST['amount'] ?? 0);
        $method = $_POST['method'] ?? 'bank';
        $paymentAccount = $_POST['payment_account'] ?? 'checking';

        $validation = \Services\TransferService::validateTransfer($internetId, $amount, $paymentAccount);
        if (!$validation['ok']) {
            \Core\Session::flash('withdrawal_error', $validation['error']);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // build desc
        $desc = 'Withdrawal via ' . $method;
        if ($method === 'bank') {
            $bankDetails = trim($_POST['bank_details'] ?? '');
            $desc .= ' - ' . $bankDetails;
        } else {
            $walletAddr = trim($_POST['wallet_address'] ?? '');
            $desc .= ' - ' . $walletAddr;
        }

        $ref = \Services\TransferService::generateReference();
        $txnId = \Models\Transaction::create([
            'internet_id'     => $internetId,
            'type'            => 'withdrawal',
            'amount'          => $amount,
            'currency'        => $postUser['currency'] ?? 'USD',
            'status'          => 'pending',
            'reference_id'    => $ref,
            'description'     => $desc,
            'payment_account' => $paymentAccount,
            'trans_type'      => 'debit',
        ]);

        if ($txnId) {
            \Models\AuditLog::log($internetId, 'withdrawal_request', "amount: {$amount}, ref: {$ref}");
            \Core\Session::flash('withdrawal_success', 'Withdrawal request submitted. Reference: ' . $ref);
        } else {
            \Core\Session::flash('withdrawal_error', 'Failed to submit request. Try again.');
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$pageTitle = 'Withdrawal';
require_once __DIR__ . '/layout/header.php';

$error = \Core\Session::getFlash('withdrawal_error');
$success = \Core\Session::getFlash('withdrawal_success');
$internetId = $user['internet_id'];
$checking = (float) $user['checking_balance'];
$savings = (float) $user['savings_balance'];
$inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1e0e62]';
?>

<div class="mb-6 max-w-lg mx-auto">
    <h1 class="text-xl font-medium tracking-tighter text-gray-900 dark:text-white">Request Withdrawal</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submit a withdrawal request for admin approval</p>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-4 rounded-2xl max-w-lg mx-auto bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-4 rounded-2xl max-w-lg mx-auto bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 max-w-lg mx-auto">
    <form method="POST">
        <?= \Core\Security::csrfField() ?>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                <input type="number" name="amount" step="0.01" min="1" required class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method</label>
                <select name="method" id="method" class="<?= $inputClass ?>" onchange="toggleMethod()">
                    <option value="bank">Bank Transfer</option>
                    <option value="crypto">Cryptocurrency</option>
                </select>
            </div>
            <div id="bank_fields">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Details</label>
                <textarea name="bank_details" rows="3" placeholder="Account number, bank name, routing number..." class="<?= $inputClass ?>"></textarea>
            </div>
            <div id="crypto_fields" class="hidden">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wallet Address</label>
                <input type="text" name="wallet_address" placeholder="Enter your wallet address" class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pay From</label>
                <select name="payment_account" class="<?= $inputClass ?>">
                    <option value="checking">Checking (<?= \Helpers\Format::currency($checking, $currency) ?>)</option>
                    <option value="savings">Savings (<?= \Helpers\Format::currency($savings, $currency) ?>)</option>
                </select>
            </div>
        </div>
        <button type="submit" class="mt-6 w-full py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Submit Request</button>
    </form>
</div>

<?php
$pageScripts = '<script>function toggleMethod(){var m=document.getElementById("method").value;document.getElementById("bank_fields").classList.toggle("hidden",m!=="bank");document.getElementById("crypto_fields").classList.toggle("hidden",m!=="crypto")}</script>';
require_once __DIR__ . '/layout/footer.php';
?>
