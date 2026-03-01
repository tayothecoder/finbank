<?php

// view ticket page - display ticket details and admin reply

// check ticket ownership before any output (redirect if invalid)
require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();

$sessionUser = \Core\Session::getUser();
if ($sessionUser) {
    $conn = \Core\Database::connect();
    $checkUser = \Models\Account::findByInternetId($sessionUser['internet_id']);
    $ticketId = (int) ($_GET['id'] ?? 0);
    $ticket = \Models\Ticket::findById($ticketId);
    $baseUrl = \Core\Env::get('BASE_URL', '');

    // ensure ticket exists and belongs to user
    if (!$ticket || $ticket['internet_id'] !== $checkUser['internet_id']) {
        header('Location: ' . $baseUrl . '/accounts/helpdesk.php');
        exit;
    }
}

$pageTitle = 'View Ticket';
require_once __DIR__ . '/layout/header.php';

$internetId = $user['internet_id'];

// re-fetch ticket using the header-provided user context if needed
if (!isset($ticket)) {
    $ticketId = (int) ($_GET['id'] ?? 0);
    $ticket = \Models\Ticket::findById($ticketId);
    if (!$ticket || $ticket['internet_id'] !== $internetId) {
        echo '<p>Ticket not found.</p>';
        require_once __DIR__ . '/layout/footer.php';
        exit;
    }
}

$statusColors = [
    'open'       => 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
    'processing' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
    'resolved'   => 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
    'closed'     => 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400',
];
?>

<div class="mb-6">
    <a href="<?= $baseUrl ?>/accounts/helpdesk.php" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white">Back to Helpdesk</a>
</div>

<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 mb-6">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h1 class="text-lg font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($ticket['subject']) ?></h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                <?= ucfirst($ticket['type']) ?> - Submitted <?= \Helpers\Format::dateTime($ticket['created_at']) ?>
            </p>
        </div>
        <span class="text-xs px-2 py-1 rounded-full <?= $statusColors[$ticket['status']] ?? $statusColors['open'] ?>"><?= ucfirst($ticket['status']) ?></span>
    </div>

    <div class="border-t border-gray-100 dark:border-white/10 pt-4">
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($ticket['message']) ?></p>
    </div>

    <?php if (!empty($ticket['attachment'])): ?>
    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/10">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Attachment</p>
        <a href="<?= $baseUrl ?>/accounts/serve-file.php?file=<?= urlencode($ticket['attachment']) ?>&type=ticket" class="text-sm text-[#1e0e62] dark:text-indigo-400 hover:underline">View attachment</a>
    </div>
    <?php endif; ?>
</div>

<!-- admin reply -->
<?php if (!empty($ticket['admin_reply'])): ?>
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6">
    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Admin Reply</h2>
    <div class="bg-gray-50 dark:bg-white/5 rounded-xl p-4">
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($ticket['admin_reply']) ?></p>
    </div>
    <?php if ($ticket['updated_at']): ?>
    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2"><?= \Helpers\Format::dateTime($ticket['updated_at']) ?></p>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-6 text-center">
    <p class="text-sm text-gray-500 dark:text-gray-400">No reply yet. We will respond to your ticket soon.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
