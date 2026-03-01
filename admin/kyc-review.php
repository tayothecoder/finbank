<?php

// kyc review

$pageTitle = 'KYC Review';
require_once __DIR__ . '/includes/auth.php';

$db = $conn;

// handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && \Core\Security::verifyCsrf()) {
    $internetId = $_POST['internet_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($internetId && in_array($action, ['approve', 'reject'], true)) {
        if ($action === 'approve') {
            \Services\KycService::approve($internetId);
            \Models\AuditLog::log('admin:' . $adminUser['email'], 'kyc_approved', "KYC approved for {$internetId}");
        } else {
            \Services\KycService::reject($internetId);
            \Models\AuditLog::log('admin:' . $adminUser['email'], 'kyc_rejected', "KYC rejected for {$internetId}");
        }
    }
    header('Location: ' . $adminBase . '/kyc-review.php');
    exit;
}

$stmt = $db->prepare("SELECT id, internet_id, first_name, last_name, email, kyc_status, id_front, id_back, id_number, proof_of_address, created_at FROM accounts WHERE kyc_status = 'pending' ORDER BY created_at ASC");
$stmt->execute();
$pending = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="text-2xl font-medium text-[#1e0e62] dark:text-white tracking-tighter mb-6">KYC Review</h1>

<?php if (empty($pending)): ?>
    <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-8 text-center">
        <p class="text-gray-400">No pending KYC submissions.</p>
    </div>
<?php endif; ?>

<?php foreach ($pending as $p): ?>
    <div class="bg-white dark:bg-[#1a1045] rounded-3xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-medium text-[#1e0e62] dark:text-white"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></h2>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($p['internet_id']) ?> - <?= htmlspecialchars($p['email']) ?></p>
                <?php if ($p['id_number']): ?>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ID Number: <?= htmlspecialchars($p['id_number']) ?></p>
                <?php endif; ?>
            </div>
            <form method="POST" class="flex gap-2">
                <?= \Core\Security::csrfField() ?>
                <input type="hidden" name="internet_id" value="<?= htmlspecialchars($p['internet_id']) ?>">
                <button type="submit" name="action" value="approve" class="px-3 py-1.5 rounded-full bg-green-600 text-white text-sm font-medium hover:bg-green-700">Approve</button>
                <button type="submit" name="action" value="reject" class="px-3 py-1.5 rounded-full bg-red-600 text-white text-sm font-medium hover:bg-red-700">Reject</button>
            </form>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <?php
            $docs = ['id_front' => 'ID Front', 'id_back' => 'ID Back', 'proof_of_address' => 'Proof of Address'];
            foreach ($docs as $field => $label):
                if (empty($p[$field])) continue;
                $ext = strtolower(pathinfo($p[$field], PATHINFO_EXTENSION));
                $filePath = $baseUrl . '/uploads/kyc/' . $p[$field];
            ?>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2"><?= $label ?></p>
                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                        <img src="<?= htmlspecialchars($filePath) ?>" alt="<?= $label ?>" class="rounded-xl max-h-48 w-full object-cover border border-gray-200 dark:border-white/10">
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($filePath) ?>" target="_blank" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline">View PDF</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
