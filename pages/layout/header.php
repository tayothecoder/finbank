<?php
// header
$appName = defined('APP_NAME') ? APP_NAME : 'Offshore Private Union Bank';
$baseUrl = defined('APP_URL') ? APP_URL : '/banking';
?>
<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $appName) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/assets/css/style.css">
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-lavender dark:bg-dark-bg text-indigo dark:text-gray-200 min-h-screen flex flex-col">
<nav class="w-full px-6 py-4">
    <div class="max-w-6xl mx-auto flex items-center justify-between">
        <a href="<?= $baseUrl ?>/public/" class="text-xl font-medium tracking-tight">
            <?= htmlspecialchars($appName) ?>
        </a>
        <div class="hidden md:flex items-center gap-6 text-sm font-medium">
            <a href="<?= $baseUrl ?>/public/" class="hover:opacity-70">Home</a>
            <a href="<?= $baseUrl ?>/public/#services" class="hover:opacity-70">Services</a>
            <a href="<?= $baseUrl ?>/public/#about" class="hover:opacity-70">About</a>
        </div>
        <div class="flex items-center gap-3">
            <button id="darkToggle" class="p-2 rounded-full hover:bg-indigo/10 dark:hover:bg-white/10" aria-label="Toggle dark mode">
                <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.752 15.002A9.718 9.718 0 0112 21.75 9.75 9.75 0 018.998 2.248 9.718 9.718 0 0021.752 15.002z"/></svg>
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
            </button>
            <button id="mobileMenuBtn" class="md:hidden p-2" aria-label="Menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 9h16.5m-16.5 6.75h16.5"/></svg>
            </button>
            <a href="<?= $baseUrl ?>/pages/login.php" class="hidden md:inline-block text-sm font-medium hover:opacity-70">Sign In</a>
            <a href="<?= $baseUrl ?>/pages/register.php" class="hidden md:inline-block rounded-full bg-indigo dark:bg-white dark:text-indigo text-white px-5 py-2 text-sm font-medium">Get Started</a>
        </div>
    </div>
    <div id="mobileMenu" class="hidden md:hidden mt-4 pb-4 flex flex-col gap-3 text-sm font-medium">
        <a href="<?= $baseUrl ?>/public/">Home</a>
        <a href="<?= $baseUrl ?>/public/#services">Services</a>
        <a href="<?= $baseUrl ?>/public/#about">About</a>
        <a href="<?= $baseUrl ?>/pages/login.php">Sign In</a>
        <a href="<?= $baseUrl ?>/pages/register.php">Get Started</a>
    </div>
</nav>
