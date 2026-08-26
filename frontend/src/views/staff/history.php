<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header('Location: ' . route_url('/login'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Attendance History' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        .history-container {
            padding: 28px 32px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .history-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
        }
        
        .history-header p {
            color: var(--text);
            font-size: 14px;
            margin-top: 4px;
        }
        
        .history-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .history-filters input,
        .history-filters select {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--heading);
            font-size: 14px;
            min-width: 180px;
        }
        
        .history-filters input:focus,
        .history-filters select:focus {
            outline: none;
            border-color: var(--olive-green);
        }
        
        .table-container {
            overflow-x: auto;
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .history-table th {
            padding: 16px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text);
            border-bottom: 2px solid var(--border-color);
            background: var(--background);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .history-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--heading);
        }
        
        .history-table tr:last-child td {
            border-bottom: none;
        }
        
        .history-table tr:hover td {
            background: var(--olive-green-soft);
        }
        
        .sync-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: var(--olive-green-soft);
            color: var(--olive-green);
        }
        
        .empty-row td {
            text-align: center;
            padding: 40px;
            color: var(--muted);
            font-size: 14px;
        }
        
        .hours-positive {
            color: var(--olive-green);
            font-weight: 600;
        }
        
        .hours-negative {
            color: #F87171;
            font-weight: 600;
        }
        
        .hours-zero {
            color: var(--muted);
        }
        
        .entry-count {
            font-size: 12px;
            color: var(--muted);
            margin-left: 8px;
        }
        
        .clickable-row {
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }
        
        .clickable-row .toggle-icon {
            display: inline-block;
            transition: transform 0.2s ease;
            font-size: 12px;
            color: var(--muted);
            margin-right: 8px;
        }
        
        .clickable-row .toggle-icon.expanded {
            transform: rotate(90deg);
        }
        
        .clickable-row:hover td {
            background: var(--olive-green-soft);
        }
        
        .clickable-row.expanded td {
            background: var(--olive-green-soft);
            border-bottom-color: transparent;
        }
        
        .detail-row td {
            padding: 8px 16px 8px 48px;
            font-size: 13px;
            color: var(--text);
            background: var(--background);
        }
        
        .detail-row .detail-time {
            font-weight: 500;
            color: var(--heading);
        }
        
        .detail-row .detail-type {
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
            display: inline-block;
        }
        
        .detail-row .detail-type.in {
            background: var(--olive-green-soft);
            color: var(--olive-green);
        }
        
        .detail-row .detail-type.out {
            background: rgba(248, 113, 113, 0.12);
            color: #F87171;
        }
        
        .detail-row .detail-location {
            color: var(--muted);
            font-size: 11px;
            margin-left: 8px;
        }
        
        .detail-row .detail-method {
            color: var(--muted);
            font-size: 11px;
            margin-left: 8px;
            background: var(--card-bg);
            padding: 0 8px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .row-group {
            border-bottom: 1px solid var(--border-color);
        }
        
        .row-group:last-child {
            border-bottom: none;
        }
        
        .row-group .clickable-row td {
            border-bottom: none;
        }
        
        @media (max-width: 600px) {
            .history-container {
                padding: 16px;
            }
            
            .history-filters {
                flex-direction: column;
            }
            
            .history-filters input,
            .history-filters select {
                width: 100%;
                min-width: unset;
            }
            
            .history-table th,
            .history-table td {
                padding: 10px 12px;
                font-size: 12px;
            }
            
            .detail-row td {
                padding: 6px 12px 6px 24px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>

<div x-data="historyApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'history'; include __DIR__ . '/staff-sidebar.php'; ?>
        
        <main class="main-content">
            <?php if (file_exists(__DIR__ . '/../partials/top-nav.php')) include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="history-container">
                <div class="history-header">
                    <div>
                        <h1>Attendance History</h1>
                        <p>Your past sign-in and sign-out activity.</p>
                    </div>
                    <button class="btn-outline" @click="exportCSV()" style="height:44px; padding:0 20px; border-radius:12px; border:1px solid var(--border-color); background:transparent; color:var(--heading); cursor:pointer;">
                        📥 Export CSV
                    </button>
                </div>

                <div class="history-filters">
                    <input type="text" x-model="searchQuery" placeholder="Search by date (e.g. 2026-08-11)...">
                    <select x-model="filterType">
                        <option value="all">All Types</option>
                        <option value="sign-in">Sign In</option>
                        <option value="sign-out">Sign Out</option>
                    </select>
                    <select x-model="sortOrder">
                        <option value="desc">Newest First</option>
                        <option value="asc">Oldest First</option>
                    </select>
                </div>

                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>DATE</th>
                                <th>First In</th>
                                <th>Last Out</th>
                                <th>TOTAL HOURS</th>
                                <th>ENTRIES</th>
                                <th>SYNC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="day in groupedRecords" :key="day.date">
                                <tbody class="row-group">
                                    <tr class="clickable-row" @click="day.expanded = !day.expanded" :class="{ 'expanded': day.expanded }">
                                        <td>
                                            <span class="toggle-icon" :class="{ 'expanded': day.expanded }">▶</span>
                                            <span x-text="day.date"></span>
                                        </td>
                                        <td x-text="day.firstIn || '--:--'"></td>
                                        <td x-text="day.lastOut || '--:--'"></td>
                                        <td>
                                            <span x-show="day.totalHours !== null" 
                                                  :class="day.totalHours > 0 ? 'hours-positive' : (day.totalHours < 0 ? 'hours-negative' : 'hours-zero')"
                                                  x-text="day.totalHours !== null ? day.totalHours.toFixed(1) + 'h' : '--'">
                                            </span>
                                            <span x-show="day.totalHours === null" class="hours-zero">--</span>
                                        </td>
                                        <td>
                                            <span class="entry-count" x-text="day.records.length + ' entries'"></span>
                                        </td>
                                        <td><span class="sync-badge synced">Synced</span></td>
                                    </tr>
                                    <template x-for="record in day.records" :key="record.id">
                                        <tr class="detail-row" x-show="day.expanded">
                                            <td></td>
                                            <td colspan="5">
                                                <span class="detail-time" x-text="record.time"></span>
                                                <span class="detail-type" :class="record.type" x-text="record.type === 'sign-in' ? 'IN' : 'OUT'"></span>
                                                <span class="detail-location" x-text="'📍 ' + record.location"></span>
                                                <span class="detail-method" x-text="'📱 ' + (record.method || 'QR')"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </template>
                            <tr x-show="groupedRecords.length === 0">
                                <td colspan="6" class="empty-row">No records found for the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function historyApp() {
    return {
        sidebarOpen: false,
        user: { id: '', name: '', email: '' },
        records: [],
        searchQuery: '',
        filterType: 'all',
        sortOrder: 'desc',
        
        get filteredRecords() {
            let filtered = this.records;
            
            if (this.filterType !== 'all') {
                filtered = filtered.filter(r => r.type === this.filterType);
            }
            
            if (this.searchQuery) {
                filtered = filtered.filter(r => r.date.includes(this.searchQuery));
            }
            
            filtered = [...filtered].sort((a, b) => {
                const dateA = new Date(a.timestamp);
                const dateB = new Date(b.timestamp);
                return this.sortOrder === 'desc' ? dateB - dateA : dateA - dateB;
            });
            
            return filtered;
        },
        
        get groupedRecords() {
            const grouped = {};
            
            this.filteredRecords.forEach(record => {
                if (!grouped[record.date]) {
                    grouped[record.date] = {
                        date: record.date,
                        firstIn: null,
                        lastOut: null,
                        totalHours: null,
                        records: [],
                        expanded: false
                    };
                }
                grouped[record.date].records.push(record);
            });
            
            const result = Object.values(grouped);
            const today = new Date().toISOString().split('T')[0];
            
            result.forEach(day => {
                day.records.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
                
                let firstIn = null;
                let lastOut = null;
                let totalSeconds = 0;
                let lastCheckIn = null;
                let isToday = day.date === today;
                let hasValidPair = false;
                
                for (const record of day.records) {
                    if (record.type === 'sign-in') {
                        if (lastCheckIn && !isToday) {
                            lastCheckIn = null;
                        }
                        lastCheckIn = record;
                        if (!firstIn) {
                            firstIn = record;
                        }
                    } else if (record.type === 'sign-out' && lastCheckIn) {
                        const inTime = new Date(lastCheckIn.timestamp);
                        const outTime = new Date(record.timestamp);
                        
                        if (outTime > inTime) {
                            const diffMs = outTime - inTime;
                            totalSeconds += diffMs / 1000;
                            hasValidPair = true;
                        }
                        
                        lastCheckIn = null;
                        lastOut = record;
                    }
                }
                
                if (lastCheckIn && isToday) {
                    const inTime = new Date(lastCheckIn.timestamp);
                    const now = new Date();
                    if (inTime < now) {
                        const diffMs = now - inTime;
                        totalSeconds += diffMs / 1000;
                        hasValidPair = true;
                    }
                }
                
                day.firstIn = firstIn ? firstIn.time : null;
                day.lastOut = lastOut ? lastOut.time : null;
                
                if (hasValidPair && totalSeconds > 0) {
                    day.totalHours = Math.round((totalSeconds / 3600) * 10) / 10;
                } else if (day.records.length > 0) {
                    day.totalHours = 0;
                } else {
                    day.totalHours = null;
                }
            });
            
            return result;
        },
        
        async init() {
            if (window.themeManager) window.themeManager.initTheme();

            const userData = <?php echo json_encode($user ?? ['id' => '', 'name' => 'Staff']); ?>;
            this.user = userData;
            
            await this.loadHistory();
        },
        
        async loadHistory() {
            // Live attendance data from the backend. No user id is sent in the
            // request — the backend identifies the logged-in employee from the
            // shared session cookie ('credentials: include'), so this page can
            // only ever show the current user's own records.
            try {
                const response = await fetch('<?= route_url('/api/user-history') ?>', {
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!data.success) {
                    this.records = [];
                    return;
                }

                const history = data.data || [];
                this.records = history.map(r => ({
                    id: r.id,
                    date: r.date,
                    time: r.timestamp
                        ? new Date(r.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                        : '',
                    timestamp: r.timestamp,
                    type: r.type,
                    location: r.location || 'Office',
                    method: r.method || 'QR'
                }));
            } catch (err) {
                console.error('Error loading history:', err);
                this.records = [];
            }
        },

        exportCSV() {
            if (this.groupedRecords.length === 0) {
                window.appUtils.showToast('No records to export.', 'error');
                return;
            }

            let csv = 'Date,First In,Last Out,Total Hours,Entry Count\n';
            this.groupedRecords.forEach(day => {
                csv += `${day.date},${day.firstIn || ''},${day.lastOut || ''},${day.totalHours ?? ''},${day.records.length}\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `attendance_history_${this.user.id}_${new Date().toISOString().split('T')[0]}.csv`;
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