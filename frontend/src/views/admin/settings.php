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
            width: 120px;
        }
        
        .setting-row input:focus {
            outline: none;
            border-color: var(--olive-green);
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
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
            background-color: #ccc;
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
            background-color: var(--olive-green);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .connection-status {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .connection-status.connected {
            background: var(--olive-green-soft);
            color: var(--olive-green);
        }
        
        .connection-status.disconnected {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }
        
        .connection-status.checking {
            background: rgba(245, 158, 11, 0.1);
            color: #F59E0B;
        }
        
        .btn-primary {
            background: var(--olive-green);
            color: var(--sidebar-blue);
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            width: 100%;
            margin-top: 8px;
        }
        
        .btn-primary:hover {
            background: var(--olive-green-bright);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            width: 100%;
            margin-top: 8px;
        }
        
        .btn-danger:hover {
            background: #DC2626;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--heading);
            border: 1px solid var(--border-color);
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
            width: 100%;
            margin-top: 8px;
        }
        
        .btn-outline:hover {
            background: var(--olive-green-soft);
            border-color: var(--olive-green);
        }
        
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .status-dot.online {
            background: var(--olive-green);
        }
        
        .status-dot.offline {
            background: #EF4444;
        }
        
        .status-dot.checking {
            background: #F59E0B;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .last-sync-time {
            font-size: 12px;
            color: var(--muted);
        }
        
        .settings-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        
        .settings-actions button {
            flex: 1;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
                            <span class="status-badge">
                                <span class="status-dot" :class="sheetsStatus"></span>
                                <span class="connection-status" :class="sheetsStatus" x-text="sheetsStatus === 'connected' ? 'Connected' : (sheetsStatus === 'checking' ? 'Checking...' : 'Disconnected')"></span>
                            </span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Last Sync</span>
                            <span class="setting-value last-sync-time" x-text="lastSync || 'Never'"></span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Total Records</span>
                            <span class="setting-value" x-text="totalRecords || '0'"></span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Cache Age</span>
                            <span class="setting-value last-sync-time" x-text="cacheAge || 'Unknown'"></span>
                        </div>
                        <div class="settings-actions">
                            <button class="btn-primary" @click="testConnection()" :disabled="sheetsStatus === 'checking'">
                                <span x-show="sheetsStatus !== 'checking'">🔄 Test Connection</span>
                                <span x-show="sheetsStatus === 'checking'">⏳ Testing...</span>
                            </button>
                            <button class="btn-danger" @click="clearCache()">🗑️ Clear Cache</button>
                        </div>
                        <button class="btn-outline" @click="refreshData()" style="margin-top:8px;">🔄 Refresh Data</button>
                    </div>

                    <!-- Security -->
                    <div class="settings-card">
                        <h3>🔒 Security</h3>
                        <p>Session management, encryption, and access control.</p>
                        <div class="setting-row">
                            <span class="setting-label">Session timeout (minutes)</span>
                            <input type="number" x-model="settings.sessionTimeout" @change="saveSetting('sessionTimeout')" min="5" max="1440">
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Data encryption</span>
                            <span class="setting-value">✅ Enabled (SSL/TLS)</span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Role-based access (RBAC)</span>
                            <span class="setting-value">✅ Enforced</span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Force HTTPS</span>
                            <label class="switch">
                                <input type="checkbox" x-model="settings.forceHTTPS" @change="saveSetting('forceHTTPS')">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <button class="btn-primary" @click="applySecuritySettings()">🔐 Apply Security Settings</button>
                    </div>

                    <!-- Data Retention -->
                    <div class="settings-card">
                        <h3>🗄️ Data Retention</h3>
                        <p>Configure how long attendance data is kept.</p>
                        <div class="setting-row">
                            <span class="setting-label">Retention period (days)</span>
                            <input type="number" x-model="settings.retentionDays" @change="saveSetting('retentionDays')" min="7" max="365">
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Auto-archive old data</span>
                            <label class="switch">
                                <input type="checkbox" x-model="settings.autoArchive" @change="saveSetting('autoArchive')">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Records stored</span>
                            <span class="setting-value" x-text="totalRecords || '0'"></span>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Oldest record</span>
                            <span class="setting-value" x-text="oldestRecord || 'N/A'"></span>
                        </div>
                        <button class="btn-danger" @click="clearOldData()" x-show="totalRecords > 0">
                            🗑️ Delete Records Older Than <span x-text="settings.retentionDays"></span> Days
                        </button>
                    </div>

                    <!-- Location Settings -->
                    <div class="settings-card">
                        <h3>📍 Location Settings</h3>
                        <p>Configure sign-in location requirements.</p>
                        <div class="setting-row">
                            <span class="setting-label">Max distance (meters)</span>
                            <input type="number" x-model="settings.maxDistance" @change="saveSetting('maxDistance')" min="10" max="500">
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Require GPS for sign-in</span>
                            <label class="switch">
                                <input type="checkbox" x-model="settings.requireGPS" @change="saveSetting('requireGPS')">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Default location</span>
                            <input type="text" x-model="settings.defaultLocation" @change="saveSetting('defaultLocation')" style="width:150px;">
                        </div>
                        <div class="setting-row">
                            <span class="setting-label">Enable geofencing</span>
                            <label class="switch">
                                <input type="checkbox" x-model="settings.enableGeofencing" @change="saveSetting('enableGeofencing')">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <button class="btn-primary" @click="saveAllSettings()">💾 Save All Settings</button>
                    </div>
                </div>

                <!-- Hidden file input for import -->
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
        oldestRecord: 'N/A',
        cacheAge: 'Unknown',
        settings: {
            sessionTimeout: 30,
            retentionDays: 90,
            maxDistance: 100,
            requireGPS: false,
            forceHTTPS: false,
            autoArchive: true,
            defaultLocation: 'HQ',
            enableGeofencing: false
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
            // Save individual setting
            localStorage.setItem('spysee_settings', JSON.stringify(this.settings));
            window.appUtils.showToast('Setting saved!', 'success');
            
            // If session timeout changed, update PHP
            if (key === 'sessionTimeout') {
                this.updateSessionTimeout();
            }
        },
        
        saveAllSettings() {
            localStorage.setItem('spysee_settings', JSON.stringify(this.settings));
            window.appUtils.showToast('All settings saved successfully!', 'success');
            this.updateSessionTimeout();
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
                
                // Get oldest record
                const historyResponse = await fetch('/api/attendance-logs?_=' + Date.now());
                const historyData = await historyResponse.json();
                if (historyData.success && historyData.data && historyData.data.length > 0) {
                    const last = historyData.data[historyData.data.length - 1];
                    if (last && last.timestamp) {
                        this.oldestRecord = new Date(last.timestamp).toLocaleDateString();
                    }
                }
            } catch (err) {
                console.error('Error loading stats:', err);
            }
        },
        
        async clearCache() {
            if (!confirm('Are you sure you want to clear the cache and refresh data from Google Sheets?')) {
                return;
            }
            
            try {
                const response = await fetch('/api/clear-cache', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    window.appUtils.showToast('✅ Cache cleared and refreshed!', 'success');
                    this.sheetsStatus = 'checking';
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
                const response = await fetch('/api/refresh-data', {
                    method: 'POST'
                });
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
        
        async clearOldData() {
            if (!confirm(`Are you sure you want to delete all records older than ${this.settings.retentionDays} days?`)) {
                return;
            }
            
            try {
                const response = await fetch('/api/clear-old-data', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        days: this.settings.retentionDays
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    window.appUtils.showToast(`✅ ${data.deleted || 0} old records deleted!`, 'success');
                    await this.loadStats();
                } else {
                    window.appUtils.showToast('❌ Failed to delete old data: ' + data.error, 'error');
                }
            } catch (err) {
                window.appUtils.showToast('❌ Error: ' + err.message, 'error');
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
            event.target.value = ''; // Reset input
        },
        
        applySecuritySettings() {
            // Apply security settings
            window.appUtils.showToast('🔐 Security settings applied!', 'success');
            
            // If force HTTPS is enabled, redirect to HTTPS if not already
            if (this.settings.forceHTTPS && window.location.protocol !== 'https:') {
                if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    window.location.href = window.location.href.replace('http://', 'https://');
                }
            }
        }
    }
}
</script>
</body>
</html>