<?php

// view ticket

$pageTitle = 'View Ticket';
require_once __DIR__ . '/includes/auth.php';

$ticketId = (int) ($_GET['id'] ?? 0);
$ticket = \Models\Ticket::findById($ticketId);
if (!$ticket) {
    header('Location: ' . $adminBase . '/tickets.php');
    exit;
}

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $reply = trim($_POST['reply'] ?? '');
    $newStatus = $_POST['status'] ?? $ticket['status'];

    $updates = [];
    if ($reply !== '') {
        $updates['admin_reply'] = $reply;
    }
    if (in_array($newStatus, ['open', 'processing', 'resolved', 'closed'], true)) {
        $updates['status'] = $newStatus;
    }

    if (!empty($updates)) {
        \Models\Ticket::update($ticketId, $updates);
        \Models\AuditLog::log('admin:' . $adminUser['email'], 'ticket_reply', "Replied to ticket #{$ticketId}");
        $success = 'Ticket updated.';
        $ticket = \Models\Ticket::findById($ticketId);
    }
}

$statusColors = ['open' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'resolved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'closed' => 'bg-gray-100 text-gray-600 dark:bg-gray-800/30 dark:text-gray-400'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex items-center gap-3 mb-6">
    <a href="<?= $adminBase ?>/tickets.php" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white">Tickets</a>
    <span class="text-gray-300 dark:text-gray-600">/</span>
    <h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter">#<?= $ticketId ?></h1>
</div>

<?php if ($success): ?>
    <div class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm"><?= $success ?></div>
<?php endif; ?>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6 mb-6">
    <div class="flex items-start justify-between mb-4">
        <div>
            <h2 class="text-base font-medium text-[#1e0e62] dark:text-white"><?= htmlspecialchars($ticket['subject']) ?></h2>
            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($ticket['internet_id']) ?> - <?= ucfirst($ticket['type']) ?> - <?= date('M j, Y g:ia', strtotime($ticket['created_at'])) ?></p>
        </div>
        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$ticket['status']] ?? '' ?>"><?= ucfirst($ticket['status']) ?></span>
    </div>

    <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-4 mb-4">
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($ticket['message']) ?></p>
    </div>

    <?php if ($ticket['admin_reply']): ?>
        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 mb-4">
            <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mb-1">Admin Reply</p>
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($ticket['admin_reply']) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($ticket['attachment']): ?>
        <p class="text-xs text-gray-500 dark:text-gray-400">Attachment: <a href="<?= $baseUrl ?>/uploads/kyc/<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline"><?= htmlspecialchars($ticket['attachment']) ?></a></p>
    <?php endif; ?>
</div>

<div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6">
    <h3 class="text-base font-medium text-[#1e0e62] dark:text-white mb-4">Reply</h3>
    <form method="POST" class="space-y-4">
        <?= \Core\Security::csrfField() ?>
        <div>
            <textarea name="reply" rows="4" placeholder="Write your reply..." class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white focus:outline-none resize-none"><?= htmlspecialchars($_POST['reply'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-3">
            <select name="status" class="px-3 py-2 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 text-sm text-gray-900 dark:text-white">
                <?php foreach (['open','processing','resolved','closed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-6 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1578]">Update Ticket</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
