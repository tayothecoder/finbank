<?php

// my account

// handle post
require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        \Core\Session::flash('error', 'Invalid request. Please try again.');
    } else {
        $sessionUser = \Core\Session::getUser();
        if ($sessionUser) {
            $conn = \Core\Database::connect();
            $postUser = \Models\Account::findByInternetId($sessionUser['internet_id']);
            $baseUrl = \Core\Env::get('BASE_URL', '');
            $postError = null;

            $updates = [];
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            if ($phone !== '') $updates['phone'] = $phone;
            if ($address !== '') $updates['address'] = $address;

            // avatar
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $file = $_FILES['avatar'];
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowed, true) || $file['size'] > 2 * 1024 * 1024) {
                    $postError = 'Invalid image. JPEG, PNG or WebP under 2MB.';
                } else {
                    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
                    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                    $dest = __DIR__ . '/../uploads/avatars/' . $filename;
                    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $updates['avatar'] = $filename;
                    }
                }
            }

            if (!$postError && !empty($updates)) {
                \Models\Account::update($postUser['id'], $updates);
                \Core\Session::flash('success', 'Profile updated.');
                header('Location: ' . $baseUrl . '/accounts/my-account.php');
                exit;
            }
            if ($postError) {
                \Core\Session::flash('error', $postError);
            }
        }
    }
}

$pageTitle = 'My Account';
require_once __DIR__ . '/layout/header.php';

$success = \Core\Session::getFlash('success');
$error = \Core\Session::getFlash('error');

// refresh user
$user = \Models\Account::findByInternetId($user['internet_id']);
?>

<h1 class="text-2xl font-medium tracking-tighter text-gray-900 dark:text-white mb-6">My Account</h1>

<?php if ($success): ?>
    <div class="mb-4 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- account info -->
    <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Account Information</h2>
        <dl class="space-y-3 text-sm">
            <?php
            $fields = [
                'Name' => htmlspecialchars($user['first_name'] . ' ' . $user['last_name']),
                'Email' => htmlspecialchars($user['email']),
                'Phone' => htmlspecialchars($user['phone'] ?? 'Not set'),
                'Internet ID' => htmlspecialchars($user['internet_id']),
                'Checking Account' => htmlspecialchars($user['checking_acct_no'] ?? 'N/A'),
                'Savings Account' => htmlspecialchars($user['savings_acct_no'] ?? 'N/A'),
                'Currency' => $user['currency'] ?? 'USD',
                'KYC Status' => ucfirst($user['kyc_status'] ?? 'none'),
                'Account Status' => ucfirst($user['status'] ?? 'pending'),
                'Member Since' => \Helpers\Format::date($user['created_at']),
            ];
            foreach ($fields as $label => $value): ?>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-500 dark:text-gray-400"><?= $label ?></dt>
                    <dd class="text-gray-900 dark:text-white"><?= $value ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <?php if ($user['manager_name']): ?>
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/10">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Manager</p>
            <p class="text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($user['manager_name']) ?></p>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($user['manager_email'] ?? '') ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- edit profile -->
    <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Edit Profile</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <?= \Core\Security::csrfField() ?>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</label>
                <textarea name="address" rows="3" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Avatar</label>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-[#1e0e62] file:text-white">
            </div>
            <button type="submit" class="px-5 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium">Update Profile</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
