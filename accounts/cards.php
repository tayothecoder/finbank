<?php

// cards page

$pageTitle = 'Cards';
require_once __DIR__ . '/layout/header.php';

$internetId = $user['internet_id'];
$cards = \Models\Card::getByUser($internetId);
$error = '';
$success = '';

// card actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'request') {
        $result = \Services\CardService::requestCard(
            $internetId,
            $_POST['card_type'] ?? '',
            $_POST['card_name'] ?? ''
        );
        if ($result['ok']) {
            $success = 'Card requested successfully. Your CVV is: ' . $result['cvv'] . ' - save it now, it will not be shown again.';
            $cards = \Models\Card::getByUser($internetId);
            $user = \Models\Account::findByInternetId($internetId);
        } else {
            $error = $result['error'];
        }
    } elseif ($action === 'freeze' || $action === 'unfreeze') {
        $cardId = (int) ($_POST['card_id'] ?? 0);
        $card = \Models\Card::findById($cardId);
        if ($card && $card['internet_id'] === $internetId) {
            $newStatus = $action === 'freeze' ? 'frozen' : 'active';
            \Models\Card::updateStatus($cardId, $newStatus);
            $cards = \Models\Card::getByUser($internetId);
            $success = 'Card ' . ($action === 'freeze' ? 'frozen' : 'unfrozen') . ' successfully.';
        }
    }
}
?>

<div class="mb-6">
    <h1 class="text-xl font-medium tracking-tighter text-gray-900 dark:text-white">Cards</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your virtual cards</p>
</div>

<?php if ($error): ?>
<div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- existing cards -->
<?php if (!empty($cards)): ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <?php foreach ($cards as $card): ?>
    <div class="bg-[#1a1a2e] rounded-2xl p-6 text-white relative overflow-hidden" style="min-height:200px">
        <div class="flex justify-between items-start mb-8">
            <span class="text-xs uppercase tracking-wider opacity-60"><?= htmlspecialchars($card['card_type']) ?></span>
            <span class="text-xs px-2 py-1 rounded-full <?= $card['status'] === 'active' ? 'bg-green-500/20 text-green-300' : ($card['status'] === 'frozen' ? 'bg-blue-500/20 text-blue-300' : ($card['status'] === 'pending' ? 'bg-amber-500/20 text-amber-300' : 'bg-red-500/20 text-red-300')) ?>"><?= ucfirst($card['status']) ?></span>
        </div>
        <div class="text-lg font-mono tracking-widest mb-6">
            **** **** **** <?= substr($card['card_number'], -4) ?>
        </div>
        <div class="flex justify-between items-end">
            <div>
                <p class="text-xs opacity-50 mb-1">Card Holder</p>
                <p class="text-sm"><?= htmlspecialchars($card['card_name']) ?></p>
            </div>
            <div>
                <p class="text-xs opacity-50 mb-1">Expires</p>
                <p class="text-sm"><?= htmlspecialchars($card['expiry_date']) ?></p>
            </div>
        </div>
        <?php if ($card['status'] === 'active'): ?>
        <form method="POST" class="mt-4">
            <?= \Core\Security::csrfField() ?>
            <input type="hidden" name="action" value="freeze">
            <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
            <button type="submit" class="text-xs px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/20">Freeze Card</button>
        </form>
        <?php elseif ($card['status'] === 'frozen'): ?>
        <form method="POST" class="mt-4">
            <?= \Core\Security::csrfField() ?>
            <input type="hidden" name="action" value="unfreeze">
            <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
            <button type="submit" class="text-xs px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/20">Unfreeze Card</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-8 text-center mb-8">
    <p class="text-sm text-gray-500 dark:text-gray-400">You have no cards yet. Request one below.</p>
</div>
<?php endif; ?>

<!-- request new card -->
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Request New Card</h2>
    <form method="POST" class="space-y-4">
        <?= \Core\Security::csrfField() ?>
        <input type="hidden" name="action" value="request">
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Card Type</label>
            <select name="card_type" required class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white text-sm">
                <option value="visa">Visa</option>
                <option value="mastercard">Mastercard</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Name on Card</label>
            <input type="text" name="card_name" required maxlength="200" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white text-sm" placeholder="As you want it printed">
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500">Card fee: $10.00 (deducted from checking balance)</p>
        <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Request Card</button>
    </form>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
