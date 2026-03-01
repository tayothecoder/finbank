<?php

require_once __DIR__ . '/../src/Core/Env.php';
\Core\Env::load();
\Core\Security::headers();

$pageTitle = 'Offshore Private Union Bank - Private Banking';
$baseUrl = defined('APP_URL') ? APP_URL : '/banking';
require_once __DIR__ . '/../pages/layout/header.php';
?>

<main class="flex-1">
    <!-- hero -->
    <section class="max-w-6xl mx-auto px-6 py-24 md:py-36">
        <div class="max-w-2xl">
            <p class="text-sm font-medium opacity-50 mb-4">Private Banking</p>
            <h1 class="text-4xl md:text-6xl font-light tracking-tighter leading-tight mb-6">
                Banking without borders
            </h1>
            <p class="text-lg opacity-60 leading-relaxed mb-10 max-w-lg">
                Secure, private wealth management for international clients. Move your money with confidence.
            </p>
            <div class="flex gap-4">
                <a href="<?= $baseUrl ?>/pages/register.php" class="rounded-full bg-indigo dark:bg-white dark:text-indigo text-white px-6 py-3 font-medium text-sm">Open an Account</a>
                <a href="<?= $baseUrl ?>/pages/login.php" class="rounded-full border border-indigo/20 dark:border-white/20 px-6 py-3 font-medium text-sm">Sign In</a>
            </div>
        </div>
    </section>

    <!-- services -->
    <section id="services" class="max-w-6xl mx-auto px-6 py-20">
        <p class="text-sm font-medium opacity-50 mb-3">Services</p>
        <h2 class="text-3xl font-light tracking-tighter mb-12">What we offer</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <?php
            $services = [
                ['Global Transfers', 'Send and receive funds across 180+ countries with competitive rates and fast settlement.'],
                ['Fixed Deposits', 'Grow your wealth with term deposits offering attractive interest rates and flexible tenures.'],
                ['Virtual Cards', 'Instant virtual debit and credit cards for online transactions. Full control from your dashboard.'],
                ['Personal Loans', 'Quick access to credit with transparent terms. Apply online, get approved within hours.'],
                ['Multi-Currency', 'Hold and manage multiple currencies in a single account. Convert at interbank rates.'],
                ['Bank-Grade Security', 'End-to-end encryption, two-factor authentication, and real-time fraud monitoring.'],
            ];
            foreach ($services as $s): ?>
            <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-8">
                <h3 class="font-medium mb-3"><?= $s[0] ?></h3>
                <p class="text-sm opacity-60 leading-relaxed"><?= $s[1] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- about -->
    <section id="about" class="max-w-6xl mx-auto px-6 py-20">
        <div class="bg-white dark:bg-white/5 rounded-3xl shadow-sm p-12 md:p-16">
            <div class="max-w-2xl">
                <p class="text-sm font-medium opacity-50 mb-3">About</p>
                <h2 class="text-3xl font-light tracking-tighter mb-6">Trusted since 2008</h2>
                <p class="opacity-60 leading-relaxed mb-4">
                    Offshore Private Union Bank provides premium financial services to high-net-worth individuals and businesses worldwide. Our commitment to privacy, security, and personalized service has made us a trusted partner for clients across six continents.
                </p>
                <p class="opacity-60 leading-relaxed">
                    Licensed and regulated, we combine traditional banking values with modern technology to deliver a seamless experience.
                </p>
            </div>
        </div>
    </section>

    <!-- cta -->
    <section class="max-w-6xl mx-auto px-6 py-20 mb-12">
        <div class="bg-indigo dark:bg-indigo/80 rounded-3xl p-12 md:p-16 text-white text-center">
            <h2 class="text-3xl font-light tracking-tighter mb-4">Ready to get started?</h2>
            <p class="opacity-70 mb-8 max-w-md mx-auto">Open your private account in minutes. No branch visit required.</p>
            <a href="<?= $baseUrl ?>/pages/register.php" class="inline-block rounded-full bg-white text-indigo px-8 py-3 font-medium text-sm">Create Your Account</a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../pages/layout/footer.php'; ?>
