<?php

// admin header

$activePage = basename($_SERVER['SCRIPT_NAME'], '.php');

$navItems = [
    ['dashboard',    'Dashboard',     $adminBase . '/dashboard.php'],
    ['users',        'Users',         $adminBase . '/users.php'],
    ['transactions', 'Transactions',  $adminBase . '/transactions.php'],
    ['deposits',     'Deposits',      $adminBase . '/deposits.php'],
    ['cards',        'Cards',         $adminBase . '/cards.php'],
    ['kyc-review',   'KYC Review',    $adminBase . '/kyc-review.php'],
    ['tickets',      'Tickets',       $adminBase . '/tickets.php'],
    ['messages',     'Messages',      $adminBase . '/messages.php'],
    ['bank-details', 'Bank Details',  $adminBase . '/bank-details.php'],
    ['settings',     'Settings',      $adminBase . '/settings.php'],
    ['smtp-settings','SMTP Settings', $adminBase . '/smtp-settings.php'],
];
?>
<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin' ?> - <?= htmlspecialchars(APP_NAME ?? 'Banking') ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/assets/css/style.css">
    <script>if(localStorage.getItem('darkMode')==='true')document.documentElement.classList.add('dark');</script>
</head>
<body class="bg-[#f5f3ff] dark:bg-[#0f0a2e] min-h-screen">

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

<aside id="sidebar" class="fixed left-0 top-0 h-full w-60 bg-[#1e0e62] text-white z-40 transform -translate-x-full lg:translate-x-0 transition-transform">
    <div class="p-5 border-b border-white/10">
        <span class="text-base font-medium tracking-tighter">Admin Panel</span>
    </div>
    <nav class="p-3 space-y-0.5 overflow-y-auto h-[calc(100%-130px)]">
        <?php foreach ($navItems as $item): ?>
            <a href="<?= $item[2] ?>" class="block px-3 py-2 rounded-lg text-sm font-medium <?= $activePage === $item[0] ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' ?>"><?= $item[1] ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="p-3 border-t border-white/10">
        <a href="<?= $adminBase ?>/logout.php" class="block px-3 py-2 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/10">Logout</a>
    </div>
</aside>

<div class="lg:ml-60 min-h-screen">
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
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200"><?= $adminName ?></span>
        </div>
    </header>
    <main class="p-4 lg:p-8">
