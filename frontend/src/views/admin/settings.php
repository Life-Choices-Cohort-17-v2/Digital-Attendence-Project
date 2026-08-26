<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header('Location: /index.php/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | SpySee</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        @media (max-width: 900px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .settings-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
        }
        .settings-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--heading);
        }
        .settings-card > p {
            color: var(--text);
            font-size: 13px;
            margin-bottom: 20px;
        }
        
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .setting-row:last-child {
            border-bottom: none;
        }
        .setting-row .setting-label {
            font-weight: 500;
            color: var(--heading);
            font-size: 14px;
        }
        .setting-row .setting-value {
            color: var(--text);
            font-size: 13px;
        }
        .setting-row input[type="number"],
        .setting-row input[type="text"] {
            width: 80px;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--heading);
            font-size: 13px;
        }
        .setting-row input[type="text"] {
            width: 150px;
        }
        .setting-row input:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-color);
            transition: 0.3s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--accent);
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        
        .settings-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .settings-actions button {
            flex: 1;
        }
        
        .connection-status {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .connection-status.connected {
            background: var(--accent-soft);
            color: var(--accent);
        }
        .connection-status.disconnected {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }
        .connection-status.checking {
            background: rgba(245, 158, 11, 0.1);
            color: #F59E0B;
        }
        
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .status-dot.online { background: var(--accent); }
        .status-dot.offline { background: #EF4444; }
        .status-dot.checking { 
            background: #F59E0B;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
    </style>
</head>
<body>

<script>window.themeManager.initTheme();</script>

<div x-data="settingsApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'settings'; include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <div>
                        <h1>Settings</h1>
                        <p>Configure your attendance system preferences.</p>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button class="btn-outline" @click="exportSettings()" style="width:auto; padding:8px 16px; margin:0;">📤 Export</button>
                        <button class="btn-outline" @click="importSettings()" style="width:auto; padding:8px 16px; margin:0;">📥 Import</button>
                    </div>
                </div>

                <div class="settings-grid">
                    <!-- Google Sheets Integration -->
                    <div class="settings-card">
                        <h3>📊 Google Sheets Integration</h3>
                        <p>Connect to Google Sheets for real-time attendance sync.</p>
                        <div class="setting-row">
                            <span class="setting-label">Connection Status</span>
                            <span>
                                <span class="status-dot" :class="sheetsStatus"></span>
                                <span class="connection-status" :class="sheetsStatus" x-text="sheetsStatus === 'connected' ? 'Connected' : (sheetsStatus === 'checking' ? 'Checking...' : 'Disconnected')"></span>
                            </span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Last Sync</span>
                            <span class="setting-value" x-text="lastSync || 'Never'"></span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Total Records</span>
                            <span class="setting-value" x-text="totalRecords || '0'"></span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Cache Age</span>
                            <span class="setting-value" x-text="cacheAge || 'Unknown'"></span>
                        </div>
                        <div class="settings-actions">
                            <button class="btn-primary" @click="testConnection()" :disabled="sheetsStatus === 'checking'">
                                <span x-show="sheetsStatus !== 'checking'">🔄 Test Connection</span>
                                <span x-show="sheetsStatus === 'checking'">⏳ Testing...</span>
                            </button>
                            <button class="btn-outline" @click="clearCache()">🗑️ Clear Cache</button>
                        </div>
                        <button class="btn-primary" @click="refreshData()" style="margin-top:8px;">🔄 Refresh Data</button>
                    </div>

                    <!-- Security -->
                    <div class="settings-card">
                        <h3>🔒 Security</h3>
                        <p>Session management and access control.</p>
                        <div class="setting-row">
                            <span class="setting-label">Session timeout (minutes)</span>
                            <input type="number" x-model="settings.sessionTimeout" @change="saveSetting('sessionTimeout')" min="5" max="1440">
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Force HTTPS</span>
                            <label class="switch">
                                <input type="checkbox" x-model="settings.forceHTTPS" @change="saveSetting('forceHTTPS')">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Location Settings -->
                    <div class="settings-card">
                        <h3>📍 Location Settings</h3>
                        <p>Configure sign-in location requirements.</p>
                        <div class="setting-row">
                            <span class="setting-label">Default Location</span>
                            <input type="text" x-model="settings.defaultLocation" @change="saveSetting('defaultLocation')" style="width:150px;">
                        </div>
                    </div>
                </div>

                <input type="file" id="fileInput" accept=".json" style="display:none;" @change="handleFileImport($event)">
            </div>
        </main>
    </div>
</div>

<script>
function settingsApp() {
    return {
        sidebarOpen: false,
        sheetsStatus: 'checking',
        lastSync: '',
        totalRecords: 0,
        cacheAge: 'Unknown',
        settings: {
            sessionTimeout: 30,
            forceHTTPS: false,
            defaultLocation: 'HQ'
        },
        
        async init() {
            window.themeManager.initTheme();
            this.loadSettings();
            await this.testConnection();
            await this.loadStats();
        },
        
        loadSettings() {
            const saved = localStorage.getItem('spysee_settings');
            if (saved) {
                try {
                    const parsed = JSON.parse(saved);
                    this.settings = { ...this.settings, ...parsed };
                } catch (e) {
                    console.error('Error loading settings:', e);
                }
            }
        },
        
        saveSetting(key) {
            localStorage.setItem('spysee_settings', JSON.stringify(this.settings));
            window.appUtils.showToast('Setting saved!', 'success');
            
            if (key === 'sessionTimeout') {
                this.updateSessionTimeout();
            }
        },
        
        async updateSessionTimeout() {
            try {
                await fetch('/api/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_session_timeout',
                        timeout: this.settings.sessionTimeout
                    })
                });
            } catch (err) {
                console.error('Error updating session timeout:', err);
            }
        },
        
        async testConnection() {
            this.sheetsStatus = 'checking';
            
            try {
                const response = await fetch('/api/test-sheets-connection?_=' + Date.now());
                const data = await response.json();
                
                this.sheetsStatus = data.connected ? 'connected' : 'disconnected';
                this.lastSync = data.last_sync || '';
                this.cacheAge = data.cache_age || 'Unknown';
                
                if (data.connected) {
                    window.appUtils.showToast('✅ Google Sheets is connected!', 'success');
                } else {
                    window.appUtils.showToast('❌ Google Sheets connection failed: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (err) {
                this.sheetsStatus = 'disconnected';
                window.appUtils.showToast('❌ Connection test failed: ' + err.message, 'error');
            }
        },
        
        async loadStats() {
            try {
                const response = await fetch('/api/dashboard-stats?_=' + Date.now());
                const data = await response.json();
                if (data.success && data.data) {
                    this.totalRecords = data.data.totalEventsToday || 0;
                }
            } catch (err) {
                console.error('Error loading stats:', err);
            }
        },
        
        async clearCache() {
            if (!confirm('Clear cache and refresh data from Google Sheets?')) return;
            
            try {
                const response = await fetch('/api/clear-cache', { method: 'POST' });
                const data = await response.json();
                
                if (data.success) {
                    window.appUtils.showToast('✅ Cache cleared and refreshed!', 'success');
                    await this.testConnection();
                    await this.loadStats();
                } else {
                    window.appUtils.showToast('❌ Failed to clear cache: ' + data.error, 'error');
                }
            } catch (err) {
                window.appUtils.showToast('❌ Error clearing cache: ' + err.message, 'error');
            }
        },
        
        async refreshData() {
            try {
                const response = await fetch('/api/refresh-data', { method: 'POST' });
                const data = await response.json();
                
                if (data.success) {
                    window.appUtils.showToast('✅ Data refreshed!', 'success');
                    await this.testConnection();
                    await this.loadStats();
                } else {
                    window.appUtils.showToast('❌ Failed to refresh data: ' + data.error, 'error');
                }
            } catch (err) {
                window.appUtils.showToast('❌ Error refreshing data: ' + err.message, 'error');
            }
        },
        
        exportSettings() {
            const settings = {
                version: '1.0',
                exported: new Date().toISOString(),
                settings: this.settings
            };
            
            const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `spysee_settings_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            window.appUtils.showToast('Settings exported!', 'success');
        },
        
        importSettings() {
            document.getElementById('fileInput').click();
        },
        
        handleFileImport(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = JSON.parse(e.target.result);
                    if (data.settings) {
                        this.settings = { ...this.settings, ...data.settings };
                        localStorage.setItem('spysee_settings', JSON.stringify(this.settings));
                        window.appUtils.showToast('✅ Settings imported successfully!', 'success');
                    } else {
                        window.appUtils.showToast('❌ Invalid settings file', 'error');
                    }
                } catch (err) {
                    window.appUtils.showToast('❌ Failed to import settings: ' + err.message, 'error');
                }
            };
            reader.readAsText(file);
            event.target.value = '';
        }
    }
}
</script>

</body>
</html>