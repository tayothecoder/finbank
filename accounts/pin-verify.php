<?php

// reusable pin verification display component
// POST handling is done in the parent transfer file before output
// this only renders the pin form UI

$pinError = \Core\Session::getFlash('pin_error');
$pinAttempts = (int) \Core\Session::get('pin_attempts', 0);
$pinLockUntil = \Core\Session::get('pin_lock_until');

if ($pinLockUntil && time() < $pinLockUntil) {
    $remaining = (int) ceil(($pinLockUntil - time()) / 60);
    $pinError = "Too many attempts. Try again in {$remaining} minute(s).";
}
?>

<div class="max-w-sm mx-auto">
    <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 text-center">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Enter Your PIN</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Enter your 4-digit PIN to authorize this transfer</p>

        <?php if ($pinError): ?>
            <div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm"><?= htmlspecialchars($pinError) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= \Core\Security::csrfField() ?>
            <div class="flex justify-center gap-3 mb-6">
                <input type="password" name="pin" maxlength="4" pattern="\d{4}" required autocomplete="off" class="w-40 text-center text-2xl tracking-[0.5em] px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1e0e62]">
            </div>
            <button type="submit" name="pin_submit" class="w-full py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Verify PIN</button>
        </form>
    </div>
</div>
