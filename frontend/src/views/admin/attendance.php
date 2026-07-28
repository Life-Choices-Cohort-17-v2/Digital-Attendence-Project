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
    <title><?= $title ?? 'Attendance Logs' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body>

<script>window.themeManager.initTheme();</script>
<div x-data="attendanceApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'attendance'; include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <div>
                        <h1>Attendance Logs</h1>
                        <p>Full onsite-event history with override and audit trail.</p>
                    </div>
                    <button class="btn-outline" @click="exportLogs()">📥 Export CSV</button>
                </div>

                <div class="history-filters">
                    <input type="text" x-model="searchQuery" placeholder="Search name or location...">
                    <select x-model="filterType"><option value="all">All staff</option><option value="sign-in">Sign In</option><option value="sign-out">Sign Out</option></select>
                </div>

                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr><th>STAFF</th><th>TYPE</th><th>TIMESTAMP</th><th>DEVICE</th><th>LOCATION</th><th>SYNC</th><th>ACTIONS</th></tr>
                        </thead>
                        <tbody>
                            <template x-for="log in filteredLogs" :key="log.id">
                                <tr>
                                    <td x-text="log.staff"></td>
                                            <td><span class="sync-badge" :class="log.type === 'sign-in' ? 'synced' : ''" x-text="log.type"></span></td>
                                            <td x-text="log.timestamp"></td>
                                            <td x-text="log.device || 'Mobile'"></td>
                                            <td x-text="log.location"></td>
                                            <td><span class="sync-badge synced">Synced</span></td>
                                            <td class="action-icons"><button @click="alert('Edit log ID: ' + log.id)">✏️</button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>
            </div>
        </div>

<script>
function attendanceApp() {
    return {
        sidebarOpen: false,
        logs: [],
        searchQuery: '',
        filterType: 'all',
        async init() {
            window.themeManager.initTheme();
            await this.loadLogs();
        },
        async loadLogs() {
            const res = await fetch('/api/attendance-logs.php');
            const data = await res.json();
            this.logs = data.data || [];
        },
        get filteredLogs() {
            return this.logs.filter(log => {
                const matchesSearch = log.staff.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                      log.location.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesType = this.filterType === 'all' || log.type === this.filterType;
                return matchesSearch && matchesType;
            });
        },
        exportLogs() {
            window.appUtils.showToast('Exporting to CSV...', 'info');
        }
    }
}
</script>
</body>
</html>