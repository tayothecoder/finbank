<?php

// helpdesk page - list support tickets

$pageTitle = 'Helpdesk';
require_once __DIR__ . '/layout/header.php';

$internetId = $user['internet_id'];
$tickets = \Models\Ticket::getByUser($internetId);

$statusColors = [
    'open'       => 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
    'processing' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
    'resolved'   => 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
    'closed'     => 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-400',
];
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-medium tracking-tighter text-gray-900 dark:text-white">Helpdesk</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Support tickets and inquiries</p>
    </div>
    <a href="<?= $baseUrl ?>/accounts/create-ticket.php" class="px-5 py-2.5 rounded-full bg-[#1e0e62] text-white text-sm font-medium hover:bg-[#2a1280]">New Ticket</a>
</div>

<?php if (empty($tickets)): ?>
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-8 text-center">
    <p class="text-sm text-gray-500 dark:text-gray-400">No tickets yet. Create one if you need help.</p>
</div>
<?php else: ?>
<div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm divide-y divide-gray-100 dark:divide-white/10">
    <?php foreach ($tickets as $ticket): ?>
    <a href="<?= $baseUrl ?>/accounts/view-ticket.php?id=<?= $ticket['id'] ?>" class="block p-5 hover:bg-gray-50 dark:hover:bg-white/5 first:rounded-t-3xl last:rounded-b-3xl">
        <div class="flex justify-between items-start">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?= htmlspecialchars($ticket['subject']) ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= ucfirst($ticket['type']) ?> - <?= \Helpers\Format::timeAgo($ticket['created_at']) ?></p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full ml-3 shrink-0 <?= $statusColors[$ticket['status']] ?? $statusColors['open'] ?>"><?= ucfirst($ticket['status']) ?></span>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 truncate"><?= htmlspecialchars(\Helpers\Format::truncate($ticket['message'], 100)) ?></p>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
