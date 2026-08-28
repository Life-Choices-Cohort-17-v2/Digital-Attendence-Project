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

        /* ── Page Layout ── */
        .attendance-page {
            padding: 28px 32px;
            max-width: 1400px;
            margin: 0 auto;
        }
        @media (max-width: 768px) {
            .attendance-page {
                padding: 16px;
            }
        }

        /* ── Stats Bar ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin: 24px 0;
        }
        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px 20px;
            text-align: center;
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
            line-height: 1.2;
        }
        .stat-card .label {
            font-size: 12px;
            color: var(--text);
            margin-top: 4px;
            font-weight: 500;
        }
        .stat-card .number.green { color: #5DD62C; }
        .stat-card .number.orange { color: #F59E0B; }
        .stat-card .number.blue { color: #60A5FA; }
        .stat-card .number.pink { color: #F472B6; }

        /* ── Filters ── */
        .filters-row {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filters-row .search-input {
            flex: 1;
            min-width: 180px;
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--heading);
            font-size: 14px;
        }
        .filters-row .search-input:focus {
            outline: none;
            border-color: var(--accent);
        }
        .filters-row .search-input::placeholder {
            color: var(--muted);
        }
        .filters-row select {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--heading);
            font-size: 14px;
            min-width: 130px;
        }
        .filters-row select:focus {
            outline: none;
            border-color: var(--accent);
        }
        .filters-row input[type="date"] {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--heading);
            font-size: 14px;
            min-width: 150px;
        }
        .filters-row input[type="date"]:focus {
            outline: none;
            border-color: var(--accent);
        }
        .filters-row .record-badge {
            padding: 8px 16px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 13px;
            color: var(--text);
            white-space: nowrap;
        }

        /* ── Quick Actions ── */
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .quick-btn {
            padding: 8px 18px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            background: var(--card-bg);
            color: var(--text);
            font-size: 13px;
            cursor: pointer;
            transition: 0.2s;
        }
        .quick-btn:hover {
            border-color: var(--accent);
            color: var(--heading);
            background: var(--accent-soft);
        }
        .quick-btn.active {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-soft);
        }

        /* ── Table ── */
        .table-wrapper {
            overflow-x: auto;
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .attendance-table thead {
            background: var(--background);
        }
        .attendance-table th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }
        .attendance-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--heading);
            vertical-align: middle;
        }
        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }
        .attendance-table tbody tr:hover td {
            background: var(--accent-soft);
        }

        /* ── Staff Name ── */
        .staff-name {
            font-weight: 600;
            color: var(--heading);
        }
        .staff-id {
            display: block;
            font-size: 11px;
            color: var(--muted);
            font-weight: 400;
            margin-top: 2px;
        }

        /* ── Status Badges ── */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-status.in {
            background: rgba(93, 214, 44, 0.15);
            color: #5DD62C;
        }
        .badge-status.in::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #5DD62C;
        }
        .badge-status.out {
            background: rgba(239, 68, 68, 0.12);
            color: #EF4444;
        }
        .badge-status.out::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #EF4444;
        }

        /* ── Method Badge ── */
        .badge-method {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            background: var(--border-color);
            color: var(--text);
        }
        .badge-method.qr {
            background: rgba(96, 165, 250, 0.15);
            color: #60A5FA;
        }
        .badge-method.web {
            background: rgba(245, 158, 11, 0.15);
            color: #F59E0B;
        }

        /* ── Sync Badge ── */
        .badge-sync {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            background: rgba(93, 214, 44, 0.12);
            color: #5DD62C;
        }

        /* ── Time Display ── */
        .time-main {
            font-weight: 500;
            color: var(--heading);
            font-size: 14px;
        }
        .time-date {
            display: block;
            font-size: 11px;
            color: var(--muted);
            font-weight: 400;
        }

        /* ── Row Number ── */
        .row-num {
            color: var(--muted);
            font-size: 12px;
            font-weight: 500;
        }

        /* ── Pagination ── */
        .pagination-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .pagination-bar .info {
            color: var(--text);
            font-size: 13px;
        }
        .pagination-bar .info strong {
            color: var(--heading);
        }
        .pagination-controls {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .pagination-controls button {
            padding: 6px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--heading);
            cursor: pointer;
            font-size: 13px;
            transition: 0.2s;
        }
        .pagination-controls button:hover:not(:disabled) {
            border-color: var(--accent);
            background: var(--accent-soft);
        }
        .pagination-controls button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .pagination-controls .page-info {
            padding: 6px 14px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            color: var(--text);
        }

        /* ── Loading ── */
        .loading-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text);
        }
        .loading-state .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid var(--border-color);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ── Empty State ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }
        .empty-state h3 {
            color: var(--heading);
            font-size: 18px;
            margin-bottom: 4px;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .filters-row {
                flex-direction: column;
            }
            .filters-row .search-input,
            .filters-row select,
            .filters-row input[type="date"] {
                width: 100%;
                min-width: unset;
            }
            .filters-row .record-badge {
                width: 100%;
                text-align: center;
            }
            .quick-actions {
                justify-content: center;
            }
            .attendance-table th,
            .attendance-table td {
                padding: 10px 12px;
                font-size: 13px;
            }
            .badge-status {
                font-size: 10px;
                padding: 2px 10px;
            }
            .pagination-bar {
                flex-direction: column;
                align-items: center;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .stat-card {
                padding: 12px;
            }
            .stat-card .number {
                font-size: 22px;
            }
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
            
            <div class="attendance-page">
                <!-- Header -->
                <div class="page-header" style="margin-bottom:0;">
                    <div>
                    <h1>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            style="vertical-align: middle; margin-right: 8px;">
                            <rect width="8" height="4" x="8" y="2" rx="1"/>
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <path d="M12 11h4"/>
                            <path d="M12 16h4"/>
                            <path d="M8 11h.01"/>
                            <path d="M8 16h.01"/>
                        </svg>
                        Attendance Logs
                    </h1>
                        <p style="color:var(--text);font-size:14px;margin-top:4px;">Complete audit trail of all staff check-ins and check-outs</p>
                    </div>
                    <div class="action-buttons">
                <button class="btn-outline" @click="refreshLogs()" style="height:40px;padding:0 18px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        style="vertical-align: middle; margin-right: 6px;">
                        <path d="M21 12a9 9 0 0 0-15.3-6.4L3 8"/>
                        <path d="M3 3v5h5"/>
                        <path d="M3 12a9 9 0 0 0 15.3 6.4L21 16"/>
                        <path d="M21 21v-5h-5"/>
                    </svg>
                    Refresh
                </button>
                <button class="btn-primary" @click="exportCSV()" style="height:40px;padding:0 18px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        style="vertical-align: middle; margin-right: 6px;">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>
                    Export CSV
                </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="number" x-text="stats.total">0</div>
                        <div class="label">Total Events</div>
                    </div>
                    <div class="stat-card">
                        <div class="number green" x-text="stats.checkIns">0</div>
                        <div class="label">Check-Ins</div>
                    </div>
                    <div class="stat-card">
                        <div class="number orange" x-text="stats.checkOuts">0</div>
                        <div class="label">Check-Outs</div>
                    </div>
                    <div class="stat-card">
                        <div class="number blue" x-text="stats.uniqueStaff">0</div>
                        <div class="label">Unique Staff</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-row">
                    <div style="position:relative; flex:1; min-width:180px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none;">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>

                        <input type="text"
                            class="search-input"
                            x-model="searchQuery"
                            placeholder="Search by name, ID, or location..."
                            style="width:100%; padding-left:40px;">
                    </div>
                    <select x-model="filterType">
                        <option value="all">All Types</option>
                        <option value="sign-in">Check-Ins</option>
                        <option value="sign-out">Check-Outs</option>
                    </select>
                    <select x-model="filterMethod">
                        <option value="all">All Methods</option>
                        <option value="QR">QR Scan</option>
                        <option value="web">Web Manual</option>
                    </select>
                    <input type="date" x-model="filterDate">
                    <span class="record-badge" x-text="filteredLogs.length + ' records'"></span>
                </div>

                <!-- Quick Actions -->
                    <button class="quick-btn" @click="todayFilter()" :class="{ active: isTodayFilter }">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            style="vertical-align: middle; margin-right: 5px;">
                            <rect width="18" height="18" x="3" y="4" rx="2"/>
                            <path d="M16 2v4"/>
                            <path d="M8 2v4"/>
                            <path d="M3 10h18"/>
                        </svg>
                        Today
                    </button>
                    <button class="quick-btn" @click="weekFilter()" :class="{ active: isWeekFilter }">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        style="vertical-align: middle; margin-right: 5px;">
                        <rect width="18" height="18" x="3" y="4" rx="2"/>
                        <path d="M16 2v4"/>
                        <path d="M8 2v4"/>
                        <path d="M3 10h18"/>
                        <path d="M8 14h2"/>
                        <path d="M14 14h2"/>
                        <path d="M8 18h2"/>
                        <path d="M14 18h2"/>
                    </svg>
                    This Week
                </button>
                <button class="quick-btn" @click="clearFilters()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        style="vertical-align: middle; margin-right: 5px;">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                    Clear Filters
                </button>
                </div>

                <!-- Loading -->
                <div class="loading-state" x-show="isLoading">
                    <div class="spinner"></div>
                    <p style="margin-top:16px;">Loading attendance records...</p>
                </div>

                <!-- Table -->
                <div class="table-wrapper" x-show="!isLoading">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th style="min-width:140px;">Staff</th>
                                <th style="width:120px;">Type</th>
                                <th style="min-width:160px;">Timestamp</th>
                                <th style="width:100px;">Method</th>
                                <th style="width:90px;">Sync</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(log, index) in paginatedLogs" :key="log.id">
                                <tr>
                                    <td><span class="row-num" x-text="(currentPage - 1) * pageSize + index + 1"></span></td>
                                    <td>
                                        <span class="staff-name" x-text="log.staff || log.name || 'Unknown'"></span>
                                        <span class="staff-id" x-text="log.employee_id || log.staff_id || ''"></span>
                                    </td>
                                    <td>
                                        <span class="badge-status in" x-show="log.type === 'sign-in'">Check-In</span>
                                        <span class="badge-status out" x-show="log.type === 'sign-out'">Check-Out</span>
                                        <span x-show="log.type !== 'sign-in' && log.type !== 'sign-out'" x-text="log.type" style="color:var(--text);"></span>
                                    </td>
                                    <td>
                                        <span class="time-main" x-text="formatTime(log.timestamp)"></span>
                                        <span class="time-date" x-text="formatDate(log.timestamp)"></span>
                                    </td>
                                    <td>
                                        <span class="badge-method" :class="(log.method || 'QR').toLowerCase()" x-text="log.method || 'QR'"></span>
                                    </td>
                                    <td>
                                        <span class="badge-sync" x-text="log.sync || 'Synced'"></span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredLogs.length === 0">
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="icon">📭</div>
                                        <h3>No Records Found</h3>
                                        <p style="font-size:13px;">Try adjusting your filters or refresh the page.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-bar" x-show="!isLoading && filteredLogs.length > 0">
                    <span class="info">
                        Showing <strong x-text="((currentPage - 1) * pageSize) + 1"></strong> 
                        to <strong x-text="Math.min(currentPage * pageSize, filteredLogs.length)"></strong> 
                        of <strong x-text="filteredLogs.length"></strong> records
                    </span>
                    <div class="pagination-controls">
                        <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1">←</button>
                        <span class="page-info" x-text="currentPage + ' / ' + totalPages"></span>
                        <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages">→</button>
                    </div>
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
        filterMethod: 'all',
        filterDate: '',
        isLoading: false,
        currentPage: 1,
        pageSize: 20,
        refreshInterval: null,

        get stats() {
            const total = this.logs.length;
            const checkIns = this.logs.filter(l => l.type === 'sign-in').length;
            const checkOuts = this.logs.filter(l => l.type === 'sign-out').length;
            const uniqueStaff = new Set(this.logs.map(l => l.staff || l.name)).size;
            return { total, checkIns, checkOuts, uniqueStaff };
        },

        get isTodayFilter() {
            const today = new Date().toISOString().split('T')[0];
            return this.filterDate === today && this.filterDate !== '';
        },

        get isWeekFilter() {
            if (!this.filterDate) return false;
            const today = new Date();
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());
            return this.filterDate === weekStart.toISOString().split('T')[0];
        },

        get filteredLogs() {
            let filtered = [...this.logs];
            
            if (this.filterType !== 'all') {
                filtered = filtered.filter(log => log.type === this.filterType);
            }
            
            if (this.filterMethod !== 'all') {
                filtered = filtered.filter(log => (log.method || 'QR').toLowerCase() === this.filterMethod.toLowerCase());
            }
            
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(log => 
                    (log.staff && log.staff.toLowerCase().includes(query)) ||
                    (log.name && log.name.toLowerCase().includes(query)) ||
                    (log.location && log.location.toLowerCase().includes(query)) ||
                    (log.employee_id && log.employee_id.toLowerCase().includes(query)) ||
                    (log.staff_id && log.staff_id.toLowerCase().includes(query))
                );
            }
            
            if (this.filterDate) {
                filtered = filtered.filter(log => {
                    try {
                        const date = new Date(log.timestamp);
                        return date.toISOString().split('T')[0] === this.filterDate;
                    } catch {
                        return false;
                    }
                });
            }
            
            // Sort newest first
            filtered.sort((a, b) => {
                try {
                    const dateA = new Date(a.timestamp);
                    const dateB = new Date(b.timestamp);
                    return dateB - dateA;
                } catch {
                    return 0;
                }
            });
            
            return filtered;
        },

        get paginatedLogs() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            return this.filteredLogs.slice(start, end);
        },

        get totalPages() {
            return Math.ceil(this.filteredLogs.length / this.pageSize) || 1;
        },

        formatTime(timestamp) {
            if (!timestamp) return '--';
            try {
                const date = new Date(timestamp);
                return date.toLocaleTimeString('en-ZA', { 
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
            } catch (e) {
                return timestamp;
            }
        },

        formatDate(timestamp) {
            if (!timestamp) return '--';
            try {
                const date = new Date(timestamp);
                return date.toLocaleDateString('en-ZA', { 
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            } catch (e) {
                return timestamp;
            }
        },

        async init() {
            window.themeManager.initTheme();
            await this.loadLogs();
            this.refreshInterval = setInterval(() => this.loadLogs(), 30000);
        },

        async loadLogs() {
            this.isLoading = true;
            try {
                const response = await fetch('/index.php/api/attendance-logs?_=' + Date.now());
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

        todayFilter() {
            this.filterDate = new Date().toISOString().split('T')[0];
            this.currentPage = 1;
        },

        weekFilter() {
            const today = new Date();
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());
            this.filterDate = weekStart.toISOString().split('T')[0];
            this.currentPage = 1;
        },

        clearFilters() {
            this.searchQuery = '';
            this.filterType = 'all';
            this.filterMethod = 'all';
            this.filterDate = '';
            this.currentPage = 1;
        },

        exportCSV() {
            if (this.filteredLogs.length === 0) {
                window.appUtils.showToast('No records to export.', 'error');
                return;
            }
            
            let csv = 'Staff,Type,Timestamp,Location,Method,Sync\n';
            this.filteredLogs.forEach(log => {
                const staff = log.staff || log.name || 'Unknown';
                const type = log.type === 'sign-in' ? 'Check-In' : 'Check-Out';
                const timestamp = log.timestamp || '';
                const location = log.location || 'Office';
                const method = log.method || 'QR';
                const sync = log.sync || 'Synced';
                csv += `"${staff}","${type}","${timestamp}","${location}","${method}","${sync}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
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