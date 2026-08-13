<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . route_url('/login'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Settings' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body>

<div x-data="settingsApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'settings'; include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <div>
                        <h1>Settings</h1>
                        <p>Security, integrations, and data retention.</p>
                    </div>
                </div>

                <div class="settings-grid">
                    <div class="settings-card">
                        <h3>Google Sheets Integration</h3>
                        <p>Two-way sync of attendance data with auto field mapping.</p>
                        <div class="setting-row">
                            <span class="setting-label">Connection Status</span>
                            <span class="connection-status" x-show="!sheetsConnected" style="color:#EF4444">Not connected</span>
                            <span class="connection-status" x-show="sheetsConnected" style="color:#728C47">Connected</span>
                        </div>
                        <button class="btn-primary" style="width:100%; margin-top:16px" @click="sheetsConnected = !sheetsConnected">
                            <span x-show="!sheetsConnected">Connect Google Sheet</span>
                            <span x-show="sheetsConnected">Disconnect</span>
                        </button>
                    </div>

                    <div class="settings-card">
                        <h3>Security</h3>
                        <p>Session management, encryption, and access control.</p>
                        <div class="setting-row">
                            <span class="setting-label">Session timeout (minutes)</span>
                            <input type="number" x-model="settings.sessionTimeout" style="width:80px; padding:8px; border-radius:8px; border:1px solid var(--border-color)">
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Data encryption (in transit & at rest)</span>
                            <span class="setting-value">Recommended for compliance</span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Role-based access (RBAC)</span>
                            <span class="setting-value">Always enforced. Staff cannot access admin areas.</span>
                        </div>
                    </div>

                    <div class="settings-card">
                        <h3>Data Retention</h3>
                        <p>Configure how long attendance data is kept.</p>
                        <div class="setting-row">
                            <span class="setting-label">Retention period (days)</span>
                            <input type="number" x-model="settings.retentionDays" style="width:80px; padding:8px; border-radius:8px; border:1px solid var(--border-color)">
                        </div>
                        <button class="btn-outline" style="width:100%; margin-top:16px" @click="saveSettings()">Save Settings</button>
                    </div>

                    <div class="settings-card">
                        <h3>Location Settings</h3>
                        <p>Configure sign-in location requirements.</p>
                        <div class="setting-row">
                            <span class="setting-label">Max sign-in distance (meters)</span>
                            <input type="number" x-model="settings.maxDistance" style="width:80px; padding:8px; border-radius:8px; border:1px solid var(--border-color)">
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Require GPS for sign-in</span>
                            <label class="switch">
                                <input type="checkbox" x-model="settings.requireGPS">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function settingsApp() {
    return {
        sidebarOpen: false,
        sheetsConnected: false,
        settings: {
            sessionTimeout: 30,
            retentionDays: 90,
            maxDistance: 100,
            requireGPS: false
        },
        
        init() {
            this.loadSettings();
        },
        
        loadSettings() {
            const saved = localStorage.getItem('appSettings');
            if (saved) {
                this.settings = JSON.parse(saved);
            }
        },
        
        saveSettings() {
            localStorage.setItem('appSettings', JSON.stringify(this.settings));
            window.appUtils.showToast('Settings saved successfully!', 'success');
        }
    }
}
</script>
</body>
</html>
