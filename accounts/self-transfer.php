<?php

// self transfer between own accounts

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

        if (isset($_POST['step1_submit'])) {
            $amount = (float) ($_POST['amount'] ?? 0);
            $from = $_POST['from_account'] ?? 'checking';
            $to = $_POST['to_account'] ?? 'savings';

            if ($from === $to) {
                \Core\Session::flash('transfer_error', 'Source and destination must be different');
            } else {
                $result = \Services\TransferService::validateTransfer($internetId, $amount, $from);
                if (!$result['ok']) {
                    \Core\Session::flash('transfer_error', $result['error']);
                } else {
                    \Core\Session::set('self_data', [
                        'from_account' => $from,
                        'to_account'   => $to,
                        'amount'       => $amount,
                        'description'  => trim($_POST['description'] ?? ''),
                    ]);
                    \Core\Session::set('self_step', 2);
                    \Core\Session::set('pending_transfer_type', 'self');
                }
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        if (isset($_POST['back_to_form'])) {
            \Core\Session::set('self_step', 1);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // pin check
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
            $data = \Core\Session::get('self_data');
            $result = ['ok' => false, 'error' => 'No pending transfer'];
            if ($data) {
                $data['internet_id'] = $postUser['internet_id'];
                $result = \Services\TransferService::selfTransfer($data);
            }

            if ($result['ok']) {
                \Core\Session::set('transfer_result', [
                    'type' => 'self',
                    'reference_id' => $result['reference_id'],
                    'transaction_id' => $result['transaction_id'],
                ]);
                \Core\Session::set('self_data', null);
                \Core\Session::set('self_step', null);
                \Core\Session::set('pending_transfer_type', null);
                $baseUrl = defined('APP_URL') ? APP_URL : '/banking';
                header('Location: ' . $baseUrl . '/accounts/transfer-success.php');
                exit;
            }

            \Core\Session::flash('transfer_error', $result['error']);
            \Core\Session::set('self_step', 1);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$pageTitle = 'Self Transfer';
require_once __DIR__ . '/layout/header.php';

$data = \Core\Session::get('self_data') ?? [];
$step = (int) (\Core\Session::get('self_step') ?? 1);
$error = \Core\Session::getFlash('transfer_error');
$internetId = $user['internet_id'];
$checking = (float) $user['checking_balance'];
$savings = (float) $user['savings_balance'];
$inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1e0e62]';

$totalSteps = 2;
?>

<div class="mb-6 max-w-lg mx-auto">
    <h1 class="text-xl font-medium tracking-tighter text-gray-900 dark:text-white mb-4">Self Transfer</h1>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Account</label>
                <select name="from_account" id="from_account" class="<?= $inputClass ?>" onchange="updateToAccount()">
                    <option value="checking">Checking (<?= \Helpers\Format::currency($checking, $currency) ?>)</option>
                    <option value="savings">Savings (<?= \Helpers\Format::currency($savings, $currency) ?>)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Account</label>
                <select name="to_account" id="to_account" class="<?= $inputClass ?>">
                    <option value="savings">Savings (<?= \Helpers\Format::currency($savings, $currency) ?>)</option>
                    <option value="checking">Checking (<?= \Helpers\Format::currency($checking, $currency) ?>)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                <input type="number" name="amount" step="0.01" min="1" value="<?= htmlspecialchars((string) ($data['amount'] ?? '')) ?>" required class="<?= $inputClass ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <input type="text" name="description" value="<?= htmlspecialchars($data['description'] ?? '') ?>" placeholder="Optional" class="<?= $inputClass ?>">
            </div>
        </div>
        <button type="submit" name="step1_submit" class="mt-6 w-full py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Continue</button>
    </form>
</div>

<?php $pageScripts = '<script>function updateToAccount(){var f=document.getElementById("from_account"),t=document.getElementById("to_account");t.value=f.value==="checking"?"savings":"checking";}</script>'; ?>

<?php elseif ($step === 2): ?>
    <?php include __DIR__ . '/pin-verify.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
