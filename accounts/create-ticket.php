<?php

// create ticket

// handle post
require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\Core\Security::verifyCsrf()) {
        \Core\Session::flash('ticket_error', 'Invalid form submission. Please try again.');
    } else {
        $sessionUser = \Core\Session::getUser();
        if ($sessionUser) {
            $conn = \Core\Database::connect();
            $postUser = \Models\Account::findByInternetId($sessionUser['internet_id']);
            $internetId = $postUser['internet_id'];
            $baseUrl = \Core\Env::get('BASE_URL', '');
            $postError = null;

            $subject = trim($_POST['subject'] ?? '');
            $type = $_POST['type'] ?? 'general';
            $message = trim($_POST['message'] ?? '');
            $validTypes = ['general', 'technical', 'billing', 'complaint'];

            if (empty($subject) || mb_strlen($subject) > 255) {
                $postError = 'Subject is required (max 255 characters).';
            } elseif (!in_array($type, $validTypes, true)) {
                $postError = 'Invalid ticket type.';
            } elseif (empty($message)) {
                $postError = 'Message is required.';
            } else {
                $attachment = null;

                // file upload
                if (!empty($_FILES['attachment']['name'])) {
                    $fileErrors = \Helpers\Validate::fileUpload(
                        $_FILES['attachment'],
                        ['image/jpeg', 'image/png', 'application/pdf'],
                        ['jpg', 'jpeg', 'png', 'pdf'],
                        5242880
                    );
                    if (!empty($fileErrors)) {
                        $postError = 'Attachment: ' . implode(', ', $fileErrors);
                    } else {
                        $safeName = \Helpers\Validate::safeFilename($_FILES['attachment']['name']);
                        $uploadDir = __DIR__ . '/../uploads/tickets';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0750, true);
                        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . '/' . $safeName)) {
                            $attachment = $safeName;
                        } else {
                            $postError = 'Failed to upload attachment.';
                        }
                    }
                }

                if (!$postError) {
                    $ticketId = \Models\Ticket::create([
                        'internet_id' => $internetId,
                        'subject'     => $subject,
                        'message'     => $message,
                        'type'        => $type,
                        'attachment'  => $attachment,
                    ]);
                    if ($ticketId) {
                        header('Location: ' . $baseUrl . '/accounts/helpdesk.php');
                        exit;
                    }
                    $postError = 'Failed to create ticket.';
                }
            }

            if ($postError) {
                \Core\Session::flash('ticket_error', $postError);
            }
        }
    }
}

$pageTitle = 'Create Ticket';
require_once __DIR__ . '/layout/header.php';

$internetId = $user['internet_id'];
$error = \Core\Session::getFlash('ticket_error');
?>

<div class="mb-6">
    <h1 class="text-xl font-medium tracking-tighter text-gray-900 dark:text-white">Create Ticket</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submit a support request</p>
</div>

<?php if ($error): ?>
<div class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <?= \Core\Security::csrfField() ?>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Subject</label>
            <input type="text" name="subject" required maxlength="255" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Type</label>
            <select name="type" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white text-sm">
                <option value="general">General</option>
                <option value="technical">Technical</option>
                <option value="billing">Billing</option>
                <option value="complaint">Complaint</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Message</label>
            <textarea name="message" rows="5" required class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white text-sm resize-none"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Attachment (optional, image or PDF, max 5MB)</label>
            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:bg-gray-100 dark:file:bg-white/10 file:text-gray-700 dark:file:text-gray-300">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">Submit Ticket</button>
            <a href="<?= $baseUrl ?>/accounts/helpdesk.php" class="px-6 py-2.5 rounded-full border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-400 text-sm font-medium hover:bg-gray-50 dark:hover:bg-white/5">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
