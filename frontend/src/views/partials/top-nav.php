<div class="top-nav">
    <button class="menu-btn" @click="sidebarOpen = !sidebarOpen" type="button">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
    <div style="flex:1;"></div>
    <!-- Theme toggle removed -->
    <div class="admin-badge"><?= ucfirst($_SESSION['user_role'] ?? 'Staff') ?></div>
    <div id="toast-container" class="toast-container"></div>
</div>