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
    <title>Attendance Logs | SpySee</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        .attendance-filters {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .attendance-filters input,
        .attendance-filters select {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--heading);
            font-size: 14px;
            min-width: 180px;
        }
        .attendance-filters input:focus,
        .attendance-filters select:focus {
            outline: none;
            border-color: var(--accent);
        }
        .attendance-filters input::placeholder {
            color: var(--muted);
        }
        
        .badge-in {
            background: var(--accent-soft);
            color: var(--accent);
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-out {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .log-count {
            color: var(--muted);
            font-size: 13px;
            margin-left: 8px;
        }
    </style>
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
                        <p>Full attendance history with audit trail.</p>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-outline" @click="exportCSV()">📥 Export CSV</button>
                        <button class="btn-outline" @click="refreshLogs()">🔄 Refresh</button>
                    </div>
                </div>

                <div class="attendance-filters">
                    <input type="text" x-model="searchQuery" placeholder="Search staff or location...">
                    <select x-model="filterType">
                        <option value="all">All Types</option>
                        <option value="sign-in">Sign In</option>
                        <option value="sign-out">Sign Out</option>
                    </select>
                    <span class="log-count" x-text="filteredLogs.length + ' records'"></span>
                </div>

                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>STAFF</th>
                                <th>TYPE</th>
                                <th>TIMESTAMP</th>
                                <th>LOCATION</th>
                                <th>METHOD</th>
                                <th>SYNC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="log in filteredLogs" :key="log.id">
                                <tr>
                                    <td x-text="log.staff"></td>
                                    <td>
                                        <span class="badge-in" x-show="log.type === 'sign-in'">IN</span>
                                        <span class="badge-out" x-show="log.type === 'sign-out'">OUT</span>
                                    </td>
                                    <td x-text="formatDateTime(log.timestamp)"></td>
                                    <td x-text="log.location || 'Office'"></td>
                                    <td x-text="log.method || 'QR'"></td>
                                    <td>
                                        <span class="sync-badge synced">Synced</span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredLogs.length === 0">
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                    No attendance records found.
                                </td>
                            </tr>
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
        isLoading: false,
        
        get filteredLogs() {
            let filtered = this.logs;
            
            if (this.filterType !== 'all') {
                filtered = filtered.filter(log => log.type === this.filterType);
            }
            
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(log => 
                    log.staff.toLowerCase().includes(query) ||
                    (log.location && log.location.toLowerCase().includes(query))
                );
            }
            
            return filtered;
        },
        
        formatDateTime(timestamp) {
            if (!timestamp) return '--';
            const date = new Date(timestamp);
            return date.toLocaleString('en-ZA', { 
                day: '2-digit', 
                month: 'short', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        async init() {
            window.themeManager.initTheme();
            await this.loadLogs();
        },
        
        async loadLogs() {
            this.isLoading = true;
            try {
                const response = await fetch('/api/attendance-logs');
                const data = await response.json();
                this.logs = data.data || [];
            } catch (err) {
                console.error('Error loading logs:', err);
                window.appUtils.showToast('Failed to load logs', 'error');
            }
            this.isLoading = false;
        },
        
        async refreshLogs() {
            await this.loadLogs();
            window.appUtils.showToast('Logs refreshed!', 'success');
        },
        
        exportCSV() {
            if (this.filteredLogs.length === 0) {
                window.appUtils.showToast('No records to export.', 'error');
                return;
            }
            
            let csv = 'Staff,Type,Timestamp,Location,Method,Sync\n';
            this.filteredLogs.forEach(log => {
                csv += `"${log.staff}","${log.type}","${log.timestamp}","${log.location || 'Office'}","${log.method || 'QR'}","Synced"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `attendance_logs_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            window.appUtils.showToast('CSV exported successfully!', 'success');
        }
    }
}
</script>

</body>
</html>