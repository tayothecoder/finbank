<?php

// layout header

require_once __DIR__ . '/../../src/Core/Env.php';
\Core\Env::load();
\Core\Session::start();
\Core\Security::headers();

$baseUrl = defined('APP_URL') ? APP_URL : '/banking';

// auth check
if (!\Core\Session::isLoggedIn()) {
    header('Location: ' . $baseUrl . '/pages/login.php');
    exit;
}
if (!\Core\Session::get('pin_verified')) {
    header('Location: ' . $baseUrl . '/pages/login.php');
    exit;
}

$sessionUser = \Core\Session::getUser();
$conn = \Core\Database::connect();
$user = \Models\Account::findByInternetId($sessionUser['internet_id']);

if (!$user) {
    \Core\Session::destroy();
    header('Location: ' . $baseUrl . '/pages/login.php');
    exit;
}

$currency = $user['currency'] ?? 'USD';
$fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
$activePage = basename($_SERVER['SCRIPT_NAME'], '.php');

// nav items
$navItems = [
    ['dashboard', 'Dashboard', $baseUrl . '/accounts/dashboard.php', null],
    ['transfers', 'Transfers', '#', [
        ['domestic-transfer', 'Domestic', $baseUrl . '/accounts/domestic-transfer.php'],
        ['wire-transfer', 'Wire', $baseUrl . '/accounts/wire-transfer.php'],
        ['self-transfer', 'Self', $baseUrl . '/accounts/self-transfer.php'],
    ]],
    ['deposit', 'Deposit', $baseUrl . '/accounts/deposit.php', null],
    ['withdrawal', 'Withdrawal', $baseUrl . '/accounts/withdrawal.php', null],
    ['cards', 'Cards', $baseUrl . '/accounts/cards.php', null],
    ['loan', 'Loans', $baseUrl . '/accounts/loan.php', null],
    ['history', 'History', $baseUrl . '/accounts/history.php', null],
    ['helpdesk', 'Helpdesk', $baseUrl . '/accounts/helpdesk.php', null],
    ['settings', 'Settings', $baseUrl . '/accounts/settings.php', null],
    ['kyc', 'KYC Verification', $baseUrl . '/accounts/kyc.php', null],
    ['my-account', 'My Account', $baseUrl . '/accounts/my-account.php', null],
];

$transferPages = ['domestic-transfer', 'wire-transfer', 'self-transfer'];
$transferOpen = in_array($activePage, $transferPages);
?>
<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= htmlspecialchars(APP_NAME ?? 'Banking') ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/assets/css/style.css">
    <script>if(localStorage.getItem('darkMode')==='true')document.documentElement.classList.add('dark');</script>
</head>
<body class="bg-[#f5f3ff] dark:bg-[#0f0a2e] min-h-screen">

<!-- mobile overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

<!-- sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-[#1e0e62] text-white z-40 transform -translate-x-full lg:translate-x-0 transition-transform">
    <div class="p-6 border-b border-white/10">
        <span class="text-lg font-medium tracking-tighter"><?= htmlspecialchars(APP_NAME ?? 'Banking') ?></span>
    </div>
    <nav class="p-4 space-y-1 overflow-y-auto h-[calc(100%-80px)]">
        <?php foreach ($navItems as $item): ?>
            <?php [$key, $label, $href, $children] = $item; ?>
            <?php if ($children): ?>
                <div>
                    <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 <?= $transferOpen ? 'bg-white/10 text-white' : '' ?>">
                        <?= $label ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="<?= $transferOpen ? '' : 'hidden' ?> ml-4 mt-1 space-y-1">
                        <?php foreach ($children as $child): ?>
                            <a href="<?= $child[2] ?>" class="block px-3 py-1.5 rounded-lg text-sm <?= $activePage === $child[0] ? 'bg-white/20 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>"><?= $child[1] ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= $href ?>" class="block px-3 py-2 rounded-lg text-sm font-medium <?= $activePage === $key ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' ?>"><?= $label ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>

<!-- main content wrapper -->
<div class="lg:ml-64 min-h-screen">
    <!-- top bar -->
    <header class="sticky top-0 z-20 bg-white/80 dark:bg-[#1a1045]/80 backdrop-blur border-b border-gray-200 dark:border-white/10 px-4 lg:px-8 py-3 flex items-center justify-between">
        <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">
            <svg class="w-6 h-6 text-gray-700 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="flex-1"></div>
        <div class="flex items-center gap-4">
            <button onclick="toggleDarkMode()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg class="w-5 h-5 text-gray-300 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-[#1e0e62] text-white flex items-center justify-center text-sm font-medium">
                    <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200 hidden sm:block"><?= $fullName ?></span>
            </div>
            <a href="<?= $baseUrl ?>/pages/login.php?logout=1" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">Logout</a>
        </div>
    </header>

    <!-- page content -->
    <main class="p-4 lg:p-8">
