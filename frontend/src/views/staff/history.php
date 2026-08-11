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
        .page-content { padding: 28px 32px; }

        .history-filters {
            display: flex;
            gap: 12px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .history-filters input,
        .history-filters select {
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--heading);
            font-size: 14px;
        }

        .table-container { overflow-x: auto; }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
        }
        .history-table th,
        .history-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .history-table th {
            font-size: 12px;
            letter-spacing: 0.5px;
            color: var(--text);
            font-weight: 600;
        }
        .history-table td {
            font-size: 14px;
            color: var(--heading);
        }

        .sync-badge.synced {
            background: rgba(156, 176, 122, 0.15);
            color: #9CB07A;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .empty-row {
            text-align: center;
            color: var(--text);
            padding: 40px !important;
        }

        .missing {
            color: #e67e22;
            font-style: italic;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body>

<div x-data="historyApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'history'; include __DIR__ . '/staff-sidebar.php'; ?>

        <main class="main-content">
            <?php if (file_exists(__DIR__ . '/../partials/top-nav.php')) include __DIR__ . '/../partials/top-nav.php'; ?>

            <div class="page-content">
                <h1>Attendance History</h1>
                <p>Your past sign-in and sign-out activity.</p>

                <div class="history-filters">
                    <input type="text" x-model="searchQuery" placeholder="Search by date (YYYY-MM-DD)…">
                    <select x-model="filterType">
                        <option value="all">All Types</option>
                        <option value="sign_in">Sign In</option>
                        <option value="sign_out">Sign Out</option>
                    </select>
                </div>

                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>DATE</th>
                                <th>Sign In</th>
                                <th>Sign Out</th>
                                <th>TOTAL HOURS</th>
                                <th>SYNC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="day in groupedRecords" :key="day.date">
                                <tr>
                                    <td x-text="day.date"></td>
                                    <td x-text="day.clockIn || '--:--'"></td>
                                    <td>
                                        <span x-text="day.clockOut || '--:--'"
                                              :class="{ 'missing': !day.clockOut && day.clockIn }"></span>
                                    </td>
                                    <td x-text="day.totalHours || '--'"></td>
                                    <td><span class="sync-badge synced">Synced</span></td>
                                </tr>
                            </template>
                            <tr x-show="groupedRecords.length === 0">
                                <td colspan="5" class="empty-row">No records found.</td>
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
        records: [],
        searchQuery: '',
        filterType: 'all',

        get filteredRecords() {
            let list = this.records;

            if (this.filterType !== 'all') {
                list = list.filter(r => {
                    return r.type === this.filterType ||
                           r.type === this.filterType.replace('_', '-');
                });
            }

            if (this.searchQuery.trim()) {
                const q = this.searchQuery.trim().toLowerCase();
                list = list.filter(r => r.date.toLowerCase().includes(q));
            }

            return list;
        },

        get groupedRecords() {
            // Group by date – earliest sign-in + latest sign-out (handles duplicates)
            const map = {};

            this.filteredRecords.forEach(r => {
                if (!map[r.date]) {
                    map[r.date] = {
                        date: r.date,
                        clockInTs: null,
                        clockOutTs: null,
                        clockIn: null,
                        clockOut: null
                    };
                }

                const entry = map[r.date];
                const ts = new Date(r.timestamp).getTime();

                const isIn  = r.type === 'sign_in'  || r.type === 'sign-in';
                const isOut = r.type === 'sign_out' || r.type === 'sign-out';

                if (isIn) {
                    if (entry.clockInTs === null || ts < entry.clockInTs) {
                        entry.clockInTs = ts;
                        entry.clockIn = r.time;
                    }
                }

                if (isOut) {
                    if (entry.clockOutTs === null || ts > entry.clockOutTs) {
                        entry.clockOutTs = ts;
                        entry.clockOut = r.time;
                    }
                }
            });

            const result = Object.values(map).sort((a, b) => b.date.localeCompare(a.date));

            result.forEach(day => {
                if (day.clockInTs && day.clockOutTs) {
                    let diffMs = day.clockOutTs - day.clockInTs;
                    // Overnight / midnight safety
                    if (diffMs < 0) {
                        diffMs += 24 * 3600 * 1000;
                    }
                    day.totalHours = (diffMs / 3600000).toFixed(1) + ' h';
                } else if (day.clockInTs && !day.clockOutTs) {
                    day.totalHours = 'Missing clock-out';
                } else {
                    day.totalHours = '--';
                }
            });

            return result;
        },

        async init() {
            window.themeManager?.initTheme();
            await this.loadHistory();
        },

        async loadHistory() {
            try {
                // Correct route – no user_id (backend uses session)
                const res = await fetch(route_url('/attendance/history'));
                const data = await res.json();
                const history = data.data || [];

                this.records = history.map(r => {
                    const ts = r.timestamp || r.created_at || '';
                    return {
                        id: r.id,
                        date: r.date || (ts ? String(ts).substring(0, 10) : ''),
                        timestamp: ts,
                        time: ts
                            ? new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                            : '--:--',
                        type: r.type,
                        location: r.location || null
                    };
                });
            } catch (e) {
                console.error('History load failed', e);
                window.appUtils?.showToast('Could not load attendance history', 'error');
            }
        }
    };
}
</script>
</body>
</html>