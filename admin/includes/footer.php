    </main>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.toggle('hidden');
}
function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
}
</script>
<?php if (isset($pageScripts)) echo $pageScripts; ?>
</body>
</html>
