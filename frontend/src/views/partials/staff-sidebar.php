/** side bar component */

<?php
$activePage = $activePage ?? 'dashboard';
?>
<aside id="sidebar" class="sidebar" :class="{ 'open': sidebarOpen }">
    <div class="sidebar-logo">
        <h2>Clock It</h2>
        <p>Staff</p>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= route_url('/staff-dashboard') ?>" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Dashboard
        </a>
        <a href="<?= route_url('/scan-qr') ?>" class="<?= $activePage === 'scan' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
            Scan QR
        </a>
        <a href="<?= route_url('/history') ?>" class="<?= $activePage === 'history' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            History
        </a>
        <a href="<?= route_url('/profile') ?>" class="<?= $activePage === 'profile' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Profile
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'S', 0, 1)) ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Staff User') ?></div>
                <div class="user-email"><?= htmlspecialchars($_SESSION['user_email'] ?? 'staff@clockit.app') ?></div>
            </div>
        </div>
        <button class="logout-btn" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='<?= route_url('/logout') ?>'">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Sign out
        </button>
    </div>
</aside>
<div id="sidebarOverlay" class="sidebar-overlay" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false"></div>