<?php

// wire transfer - multi-step flow with pin verification

// handle form submissions before any output
require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

$wireFee = 25.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $sessionUser = \Core\Session::getUser();
    if ($sessionUser) {
        $conn = \Core\Database::connect();
        $postUser = \Models\Account::findByInternetId($sessionUser['internet_id']);
        $internetId = $postUser['internet_id'];

        if (isset($_POST['step1_submit'])) {
            $amount = (float) ($_POST['amount'] ?? 0);
            $total = $amount + $wireFee;
            $result = \Services\TransferService::validateTransfer($internetId, $total, $_POST['payment_account'] ?? 'checking');
            if (!$result['ok']) {
                \Core\Session::flash('transfer_error', $result['error']);
            } else {
                \Core\Session::set('wire_data', [
                    'recipient_name'    => trim($_POST['recipient_name'] ?? ''),
                    'recipient_account' => trim($_POST['recipient_account'] ?? ''),
                    'bank_name'         => trim($_POST['bank_name'] ?? ''),
                    'swift_code'        => trim($_POST['swift_code'] ?? ''),
                    'bank_country'      => trim($_POST['bank_country'] ?? ''),
                    'bank_address'      => trim($_POST['bank_address'] ?? ''),
                    'amount'            => $amount,
                    'fee'               => $wireFee,
                    'description'       => trim($_POST['description'] ?? ''),
                    'payment_account'   => $_POST['payment_account'] ?? 'checking',
                ]);
                \Core\Session::set('wire_step', 2);
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        if (isset($_POST['back_to_form'])) {
            \Core\Session::set('wire_step', 1);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        if (isset($_POST['confirm_submit'])) {
            \Core\Session::set('wire_step', 3);
            \Core\Session::set('pending_transfer_type', 'wire');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // pin verification for step 3
        if (isset($_POST['pin_submit'])) {
            $pinLockUntil = \Core\Session::get('pin_lock_until');
            if ($pinLockUntil && time() < $pinLockUntil) {
                \Core\Session::flash('pin_error', 'Account temporarily locked');
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }

            $pin = trim($_POST['pin'] ?? '');
            $pinAttempts = (int) \Core\Session::get('pin_attempts', 0);
            if (!\Models\Account::verifyPin($postUser, $pin)) {
                $attempts = $pinAttempts + 1;
                \Core\Session::set('pin_attempts', $attempts);
                if ($attempts >= 3) {
                    \Core\Session::set('pin_lock_until', time() + 900);
                    \Core\Session::flash('pin_error', 'Too many failed attempts. Locked for 15 minutes.');
                    \Models\AuditLog::log($postUser['internet_id'], 'pin_locked', 'pin verification locked after 3 attempts');
                } else {
                    \Core\Session::flash('pin_error', 'Invalid PIN. ' . (3 - $attempts) . ' attempt(s) remaining.');
                }
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }

            \Core\Session::set('pin_attempts', 0);
            \Core\Session::set('pin_lock_until', null);

            // pin passed, execute transfer
            $data = \Core\Session::get('wire_data');
            $result = ['ok' => false, 'error' => 'No pending transfer'];
            if ($data) {
                $data['internet_id'] = $postUser['internet_id'];
                $result = \Services\TransferService::wireTransfer($data);
            }

            if ($result['ok']) {
                \Core\Session::set('transfer_result', [
                    'type' => 'wire',
                    'reference_id' => $result['reference_id'],
                    'transaction_id' => $result['transaction_id'],
                ]);
                \Core\Session::set('wire_data', null);
                \Core\Session::set('wire_step', null);
                \Core\Session::set('pending_transfer_type', null);
                $baseUrl = defined('APP_URL') ? APP_URL : '/banking';
                header('Location: ' . $baseUrl . '/accounts/transfer-success.php');
                exit;
            }

            \Core\Session::flash('transfer_error', $result['error']);
            \Core\Session::set('wire_step', 2);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$pageTitle = 'Wire Transfer';
require_once __DIR__ . '/layout/header.php';

$data = \Core\Session::get('wire_data') ?? [];
$step = (int) (\Core\Session::get('wire_step') ?? 1);
$error = \Core\Session::getFlash('transfer_error');
$internetId = $user['internet_id'];
$checking = (float) $user['checking_balance'];
$savings = (float) $user['savings_balance'];
$inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1e0e62]';

$totalSteps = 3;
?>

<div class="mb-6 max-w-lg mx-auto">
    <h1 class="text-xl font-medium tracking-tighter text-gray-900 dark:text-white mb-4">Wire Transfer</h1>
    <div class="flex items-center gap-2 text-sm">
        <?php for ($i = 1; $i <= $totalSteps; $i++): ?>
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium <?= $i <= $step ? 'bg-[#1e0e62] text-white' : 'bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-gray-400' ?>"><?= $i ?></span>
            <?php if ($i < $totalSteps): ?><span class="w-8 h-px <?= $i < $step ? 'bg-[#1e0e62]' : 'bg-gray-200 dark:bg-white/10' ?>"></span><?php endif; ?>
        <?php endfor; ?>
    </div>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-4 rounded-2xl max-w-lg mx-auto bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($step === 1): ?>
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 max-w-lg mx-auto">
    <form method="POST">
        <?= \Core\Security::csrfField() ?>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recipient Name</label>
                <input type="text" name="recipient_name" value="<?= htmlspecialchars($data['recipient_name'] ?? '') ?>" required class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account Number</label>
                <input type="text" name="recipient_account" value="<?= htmlspecialchars($data['recipient_account'] ?? '') ?>" required class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Name</label>
                <input type="text" name="bank_name" value="<?= htmlspecialchars($data['bank_name'] ?? '') ?>" required class="<?= $inputClass ?>">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SWIFT Code</label>
                    <input type="text" name="swift_code" value="<?= htmlspecialchars($data['swift_code'] ?? '') ?>" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Country</label>
                    <input type="text" name="bank_country" value="<?= htmlspecialchars($data['bank_country'] ?? '') ?>" required class="<?= $inputClass ?>">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Address</label>
                <input type="text" name="bank_address" value="<?= htmlspecialchars($data['bank_address'] ?? '') ?>" class="<?= $inputClass ?>">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                    <input type="number" name="amount" step="0.01" min="1" value="<?= htmlspecialchars((string) ($data['amount'] ?? '')) ?>" required class="<?= $inputClass ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wire Fee</label>
                    <input type="text" value="<?= \Helpers\Format::currency($wireFee, $currency) ?>" disabled class="<?= $inputClass ?> opacity-60">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <input type="text" name="description" value="<?= htmlspecialchars($data['description'] ?? '') ?>" class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pay From</label>
                <select name="payment_account" class="<?= $inputClass ?>">
                    <option value="checking">Checking (<?= \Helpers\Format::currency($checking, $currency) ?>)</option>
                    <option value="savings">Savings (<?= \Helpers\Format::currency($savings, $currency) ?>)</option>
                </select>
            </div>
        </div>
        <button type="submit" name="step1_submit" class="mt-6 w-full py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Continue</button>
    </form>
</div>

<?php elseif ($step === 2): ?>
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 max-w-lg mx-auto">
    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Confirm Details</h2>
    <div class="divide-y divide-gray-100 dark:divide-white/10 text-sm">
        <?php
        $rows = [
            'Recipient' => $data['recipient_name'] ?? '',
            'Account Number' => $data['recipient_account'] ?? '',
            'Bank Name' => $data['bank_name'] ?? '',
            'SWIFT Code' => $data['swift_code'] ?? '',
            'Bank Country' => $data['bank_country'] ?? '',
            'Bank Address' => $data['bank_address'] ?? '-',
            'Amount' => \Helpers\Format::currency($data['amount'] ?? 0, $currency),
            'Wire Fee' => \Helpers\Format::currency($data['fee'] ?? 25, $currency),
            'Total' => \Helpers\Format::currency(($data['amount'] ?? 0) + ($data['fee'] ?? 25), $currency),
            'Description' => $data['description'] ?? '-',
            'Pay From' => ucfirst($data['payment_account'] ?? 'checking'),
        ];
        foreach ($rows as $label => $val): ?>
        <div class="flex justify-between py-3">
            <span class="text-gray-500 dark:text-gray-400"><?= $label ?></span>
            <span class="text-gray-900 dark:text-white font-medium"><?= htmlspecialchars($val) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="flex gap-3 mt-6">
        <form method="POST" class="flex-1"><?= \Core\Security::csrfField() ?><button type="submit" name="back_to_form" class="w-full py-2.5 rounded-full border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5">Back</button></form>
        <form method="POST" class="flex-1"><?= \Core\Security::csrfField() ?><button type="submit" name="confirm_submit" class="w-full py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Proceed to PIN</button></form>
    </div>
</div>

<?php elseif ($step === 3): ?>
    <?php include __DIR__ . '/pin-verify.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
