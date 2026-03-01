<?php

// kyc document submission page

$pageTitle = 'KYC Verification';
require_once __DIR__ . '/layout/header.php';

$kycStatus = $user['kyc_status'] ?? 'none';
$error = '';
$success = '';

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    if ($kycStatus === 'approved') {
        $error = 'Your identity has already been verified.';
    } else {
        $idNumber = trim($_POST['id_number'] ?? '');

        if (empty($idNumber)) {
            $error = 'ID number is required.';
        } elseif (empty($_FILES['id_front']['name']) || empty($_FILES['id_back']['name']) || empty($_FILES['proof_of_address']['name'])) {
            $error = 'All document uploads are required.';
        } else {
            $result = \Services\KycService::submitKyc(
                $user['internet_id'],
                $_FILES['id_front'],
                $_FILES['id_back'],
                $idNumber,
                $_FILES['proof_of_address']
            );

            if ($result['ok']) {
                $success = 'Documents submitted successfully. Your verification is under review.';
                $kycStatus = 'pending';
            } else {
                $error = $result['error'];
            }
        }
    }
}
?>

<h1 class="text-2xl font-medium tracking-tighter text-gray-900 dark:text-white mb-6">KYC Verification</h1>

<!-- status display -->
<div class="mb-6">
    <?php if ($kycStatus === 'approved'): ?>
        <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Verified</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Your identity has been verified. No further action is needed.</p>
                </div>
            </div>
        </div>
    <?php elseif ($kycStatus === 'pending'): ?>
        <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Pending review</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Your documents have been submitted and are being reviewed. This usually takes 1-2 business days.</p>
                </div>
            </div>
        </div>
    <?php else: ?>

        <?php if ($kycStatus === 'rejected'): ?>
            <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm mb-4">
                Your previous submission was rejected. Please review your documents and resubmit.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="p-4 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- upload form -->
        <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Upload your identity documents to verify your account. Accepted formats: JPG, PNG, PDF. Maximum file size: 5MB.
            </p>

            <form method="POST" enctype="multipart/form-data" class="space-y-6 max-w-lg mx-auto">
                <?= \Core\Security::csrfField() ?>

                <!-- id number -->
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">ID number</label>
                    <input type="text" name="id_number" required maxlength="50"
                           placeholder="Passport or national ID number"
                           class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-white px-3 py-2 text-sm">
                </div>

                <!-- id front -->
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">ID front</label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 mb-2">Passport, driver's license, or national ID card</p>
                    <div class="upload-zone border-2 border-dashed border-gray-200 dark:border-white/10 rounded-xl p-6 text-center cursor-pointer hover:border-gray-300 dark:hover:border-white/20"
                         ondragover="event.preventDefault(); this.classList.add('border-[#1e0e62]', 'dark:border-white/40')"
                         ondragleave="this.classList.remove('border-[#1e0e62]', 'dark:border-white/40')"
                         ondrop="handleDrop(event, this)"
                         onclick="this.querySelector('input').click()">
                        <input type="file" name="id_front" required accept=".jpg,.jpeg,.png,.pdf" class="hidden" onchange="showFile(this)">
                        <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500 file-label">Drag and drop or click to upload</p>
                    </div>
                </div>

                <!-- id back -->
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">ID back</label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 mb-2">Back side of your identification document</p>
                    <div class="upload-zone border-2 border-dashed border-gray-200 dark:border-white/10 rounded-xl p-6 text-center cursor-pointer hover:border-gray-300 dark:hover:border-white/20"
                         ondragover="event.preventDefault(); this.classList.add('border-[#1e0e62]', 'dark:border-white/40')"
                         ondragleave="this.classList.remove('border-[#1e0e62]', 'dark:border-white/40')"
                         ondrop="handleDrop(event, this)"
                         onclick="this.querySelector('input').click()">
                        <input type="file" name="id_back" required accept=".jpg,.jpeg,.png,.pdf" class="hidden" onchange="showFile(this)">
                        <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500 file-label">Drag and drop or click to upload</p>
                    </div>
                </div>

                <!-- proof of address -->
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Proof of address</label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 mb-2">Utility bill or bank statement (issued within 3 months)</p>
                    <div class="upload-zone border-2 border-dashed border-gray-200 dark:border-white/10 rounded-xl p-6 text-center cursor-pointer hover:border-gray-300 dark:hover:border-white/20"
                         ondragover="event.preventDefault(); this.classList.add('border-[#1e0e62]', 'dark:border-white/40')"
                         ondragleave="this.classList.remove('border-[#1e0e62]', 'dark:border-white/40')"
                         ondrop="handleDrop(event, this)"
                         onclick="this.querySelector('input').click()">
                        <input type="file" name="proof_of_address" required accept=".jpg,.jpeg,.png,.pdf" class="hidden" onchange="showFile(this)">
                        <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500 file-label">Drag and drop or click to upload</p>
                    </div>
                </div>

                <button type="submit" class="px-5 py-2 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Submit documents</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function showFile(input) {
    var label = input.parentElement.querySelector('.file-label');
    var zone = input.parentElement;
    if (input.files.length > 0) {
        var file = input.files[0];
        var sizeMb = (file.size / 1048576).toFixed(1);
        label.textContent = file.name + ' (' + sizeMb + ' MB)';
        zone.classList.remove('border-gray-200', 'dark:border-white/10');
        zone.classList.add('border-green-400', 'dark:border-green-600');
    }
}

function handleDrop(e, zone) {
    e.preventDefault();
    zone.classList.remove('border-[#1e0e62]', 'dark:border-white/40');
    var input = zone.querySelector('input[type="file"]');
    if (e.dataTransfer.files.length > 0) {
        input.files = e.dataTransfer.files;
        showFile(input);
    }
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
