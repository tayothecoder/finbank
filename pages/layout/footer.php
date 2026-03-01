<?php
// footer
$appName = defined('APP_NAME') ? APP_NAME : 'Offshore Private Union Bank';
$baseUrl = defined('APP_URL') ? APP_URL : '/banking';
?>
<footer class="mt-auto border-t border-indigo/5 dark:border-white/5">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <div class="grid md:grid-cols-3 gap-8 text-sm">
            <div>
                <p class="font-medium mb-3"><?= htmlspecialchars($appName) ?></p>
                <p class="opacity-60 leading-relaxed">Private banking services for discerning clients worldwide. Licensed and regulated.</p>
            </div>
            <div>
                <p class="font-medium mb-3">Quick Links</p>
                <ul class="space-y-2 opacity-60">
                    <li><a href="<?= $baseUrl ?>/public/" class="hover:opacity-100">Home</a></li>
                    <li><a href="<?= $baseUrl ?>/pages/login.php" class="hover:opacity-100">Internet Banking</a></li>
                    <li><a href="<?= $baseUrl ?>/pages/register.php" class="hover:opacity-100">Open Account</a></li>
                </ul>
            </div>
            <div>
                <p class="font-medium mb-3">Contact</p>
                <ul class="space-y-2 opacity-60">
                    <li>support@offshoreprivateunion.com</li>
                    <li>+1 (800) 555-0199</li>
                </ul>
            </div>
        </div>
        <div class="mt-10 pt-6 border-t border-indigo/5 dark:border-white/5 text-center text-xs opacity-40">
            2026 <?= htmlspecialchars($appName) ?>. All rights reserved.
        </div>
    </div>
</footer>
<script src="<?= $baseUrl ?>/public/assets/js/app.js"></script>
</body>
</html>
