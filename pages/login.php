<?php

require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();
\Core\Security::headers();

$baseUrl = defined('APP_URL') ? APP_URL : '/banking';
$error = '';
$showPin = false;

// pin verify
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    if (!\Core\Security::verifyCsrf()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $result = \Services\AuthService::verifyPin($_POST['pin']);
        if ($result['ok']) {
            header('Location: ' . $baseUrl . '/accounts/dashboard.php');
            exit;
        }
        $error = $result['error'];
        $showPin = true;
    }
}

// handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['identifier']) && !isset($_POST['pin'])) {
    if (!\Core\Security::verifyCsrf()) {
        $error = 'Invalid request. Please try again.';
    } elseif (!\Core\Security::checkRateLimit('login')) {
        $error = 'Too many attempts. Please wait a few minutes.';
    } else {
        $result = \Services\AuthService::login($_POST['identifier'], $_POST['password']);
        if ($result['ok']) {
            $showPin = true;
        } else {
            $error = $result['error'];
        }
    }
}

$flash = \Core\Session::getFlash('success');
$pageTitle = 'Sign In - Offshore Private Union Bank';
require_once __DIR__ . '/layout/header.php';
?>

<main class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="w-full max-w-md">
        <h1 class="text-3xl font-light tracking-tighter mb-2">Sign in</h1>
        <p class="text-sm opacity-50 mb-8">Access your private banking account</p>

        <?php if ($flash): ?>
        <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-sm rounded-2xl px-5 py-3 mb-6"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm rounded-2xl px-5 py-3 mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- login form -->
        <form method="POST" id="loginForm" class="<?= $showPin ? 'hidden' : '' ?>">
            <?= \Core\Security::csrfField() ?>
            <div class="space-y-4">
                <div>
                    <label class="font-medium text-sm block mb-1.5">Email or Internet ID</label>
                    <input type="text" name="identifier" required autocomplete="username"
                        class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                </div>
                <div>
                    <label class="font-medium text-sm block mb-1.5">Password</label>
                    <input type="password" name="password" required autocomplete="current-password"
                        class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                </div>
            </div>
            <button type="submit" class="w-full rounded-full bg-indigo dark:bg-white dark:text-indigo text-white px-6 py-3 font-medium text-sm mt-6">Sign In</button>
            <div class="flex justify-between mt-4 text-sm opacity-50">
                <a href="<?= $baseUrl ?>/pages/forgot-password.php" class="hover:opacity-100">Forgot password?</a>
                <a href="<?= $baseUrl ?>/pages/register.php" class="hover:opacity-100">Create account</a>
            </div>
        </form>

        <!-- pin verification modal -->
        <div id="pinModal" class="<?= $showPin ? '' : 'hidden' ?>">
            <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-8 text-center">
                <h2 class="text-xl font-medium mb-2">Enter Your PIN</h2>
                <p class="text-sm opacity-50 mb-6">4-digit security PIN</p>
                <form method="POST">
                    <?= \Core\Security::csrfField() ?>
                    <div id="pinDisplay" class="flex justify-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-2xl border border-indigo/10 dark:border-white/10 flex items-center justify-center text-lg font-medium pin-dot"></div>
                        <div class="w-12 h-12 rounded-2xl border border-indigo/10 dark:border-white/10 flex items-center justify-center text-lg font-medium pin-dot"></div>
                        <div class="w-12 h-12 rounded-2xl border border-indigo/10 dark:border-white/10 flex items-center justify-center text-lg font-medium pin-dot"></div>
                        <div class="w-12 h-12 rounded-2xl border border-indigo/10 dark:border-white/10 flex items-center justify-center text-lg font-medium pin-dot"></div>
                    </div>
                    <input type="hidden" name="pin" id="pinInput" value="">
                    <div id="pinPad" class="grid grid-cols-3 gap-2 max-w-xs mx-auto">
                        <?php for ($i = 1; $i <= 9; $i++): ?>
                        <button type="button" class="pin-key rounded-2xl py-3 text-lg font-medium hover:bg-indigo/5 dark:hover:bg-white/5" data-key="<?= $i ?>"><?= $i ?></button>
                        <?php endfor; ?>
                        <button type="button" class="pin-key rounded-2xl py-3 text-sm font-medium hover:bg-indigo/5 dark:hover:bg-white/5" data-key="clear">Clear</button>
                        <button type="button" class="pin-key rounded-2xl py-3 text-lg font-medium hover:bg-indigo/5 dark:hover:bg-white/5" data-key="0">0</button>
                        <button type="button" class="pin-key rounded-2xl py-3 text-sm font-medium hover:bg-indigo/5 dark:hover:bg-white/5" data-key="back">Del</button>
                    </div>
                    <button type="submit" id="pinSubmit" class="w-full rounded-full bg-indigo dark:bg-white dark:text-indigo text-white px-6 py-3 font-medium text-sm mt-6 opacity-40" disabled>Verify PIN</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
