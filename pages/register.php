<?php

require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();
\Core\Security::headers();

$baseUrl = defined('APP_URL') ? APP_URL : '/banking';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $result = \Services\AuthService::register([
            'first_name'       => $_POST['first_name'] ?? '',
            'last_name'        => $_POST['last_name'] ?? '',
            'email'            => $_POST['email'] ?? '',
            'phone'            => $_POST['phone'] ?? '',
            'password'         => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
        ]);

        if ($result['ok']) {
            \Core\Session::flash('success', 'Account created. Your Internet ID is ' . $result['internet_id'] . '. Please sign in.');
            header('Location: ' . $baseUrl . '/pages/login.php');
            exit;
        }
        $errors = $result['errors'];
    }
}

$pageTitle = 'Create Account - Offshore Private Union Bank';
require_once __DIR__ . '/layout/header.php';
?>

<main class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="w-full max-w-md">
        <h1 class="text-3xl font-light tracking-tighter mb-2">Create account</h1>
        <p class="text-sm opacity-50 mb-8">Open your private banking account</p>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 text-sm rounded-2xl px-5 py-3 mb-6">
            <?php foreach ($errors as $e): ?>
            <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <?= \Core\Security::csrfField() ?>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="font-medium text-sm block mb-1.5">First Name</label>
                        <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                            class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                    </div>
                    <div>
                        <label class="font-medium text-sm block mb-1.5">Last Name</label>
                        <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                            class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                    </div>
                </div>
                <div>
                    <label class="font-medium text-sm block mb-1.5">Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                </div>
                <div>
                    <label class="font-medium text-sm block mb-1.5">Phone</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                        class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                </div>
                <div>
                    <label class="font-medium text-sm block mb-1.5">Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                </div>
                <div>
                    <label class="font-medium text-sm block mb-1.5">Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="8"
                        class="w-full rounded-2xl border border-indigo/10 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo/20">
                </div>
            </div>
            <button type="submit" class="w-full rounded-full bg-indigo dark:bg-white dark:text-indigo text-white px-6 py-3 font-medium text-sm mt-6">Create Account</button>
            <p class="mt-4 text-sm opacity-50 text-center">
                Already have an account? <a href="<?= $baseUrl ?>/pages/login.php" class="hover:opacity-100">Sign in</a>
            </p>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
