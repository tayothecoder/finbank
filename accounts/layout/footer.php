    </main>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
}
</script>
<?php if (isset($pageScripts)) echo $pageScripts; ?>
</body>
</html>
