<?php

// deposit page - crypto addresses and bank deposit instructions

$pageTitle = 'Deposit';
require_once __DIR__ . '/layout/header.php';

$db = \Core\Database::connect();
$stmt = $db->prepare('SELECT * FROM digital_payments WHERE enabled = 1 ORDER BY name ASC');
$stmt->execute();
$cryptos = $stmt->fetchAll() ?: [];

$settings = \Models\Setting::get();
?>

<div class="mb-6">
    <h1 class="text-xl font-medium tracking-tighter text-gray-900 dark:text-white">Deposit Funds</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose a deposit method below</p>
</div>

<!-- crypto deposits -->
<?php if (!empty($cryptos)): ?>
<div class="mb-8">
    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Cryptocurrency</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($cryptos as $crypto): ?>
        <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <?php if ($crypto['icon']): ?>
                    <img src="<?= $baseUrl ?>/public/assets/images/<?= htmlspecialchars($crypto['icon']) ?>" alt="" class="w-8 h-8">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-white/10 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-400"><?= strtoupper(substr($crypto['name'], 0, 2)) ?></div>
                <?php endif; ?>
                <span class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($crypto['name']) ?></span>
            </div>
            <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Wallet Address</p>
                <div class="flex items-center gap-2">
                    <code id="addr-<?= $crypto['id'] ?>" class="text-xs text-gray-900 dark:text-white break-all flex-1"><?= htmlspecialchars($crypto['wallet_address']) ?></code>
                    <button onclick="copyAddr(<?= $crypto['id'] ?>)" class="shrink-0 px-3 py-1 rounded-lg bg-[#1e0e62] text-white text-xs hover:bg-[#2a1280]">Copy</button>
                </div>
            </div>
            <!-- qr placeholder -->
            <div class="mt-3 w-24 h-24 mx-auto bg-gray-100 dark:bg-white/10 rounded-xl flex items-center justify-center">
                <span class="text-xs text-gray-400 dark:text-gray-500">QR Code</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- bank deposit instructions -->
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Bank Deposit</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Transfer funds directly to your account using the bank details below. Contact your account manager for assistance.</p>
    <div class="divide-y divide-gray-100 dark:divide-white/10 text-sm">
        <div class="flex justify-between py-3">
            <span class="text-gray-500 dark:text-gray-400">Account Holder</span>
            <span class="text-gray-900 dark:text-white font-medium"><?= $fullName ?></span>
        </div>
        <div class="flex justify-between py-3">
            <span class="text-gray-500 dark:text-gray-400">Checking Account</span>
            <span class="text-gray-900 dark:text-white font-mono text-xs"><?= htmlspecialchars($user['checking_acct_no'] ?? '-') ?></span>
        </div>
        <div class="flex justify-between py-3">
            <span class="text-gray-500 dark:text-gray-400">Savings Account</span>
            <span class="text-gray-900 dark:text-white font-mono text-xs"><?= htmlspecialchars($user['savings_acct_no'] ?? '-') ?></span>
        </div>
        <?php if (!empty($settings['site_name'])): ?>
        <div class="flex justify-between py-3">
            <span class="text-gray-500 dark:text-gray-400">Bank Name</span>
            <span class="text-gray-900 dark:text-white"><?= htmlspecialchars($settings['site_name']) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$pageScripts = '<script>function copyAddr(id){var el=document.getElementById("addr-"+id);navigator.clipboard.writeText(el.textContent).then(function(){var btn=el.nextElementSibling;btn.textContent="Copied";setTimeout(function(){btn.textContent="Copy"},2000)})}</script>';
require_once __DIR__ . '/layout/footer.php';
?>
