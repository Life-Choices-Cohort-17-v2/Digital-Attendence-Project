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
    <title>Admin Dashboard | SpySee</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body>

<script>window.themeManager.initTheme();</script>

<div x-data="dashboardApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'dashboard'; include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <div>
                        <h1>Dashboard</h1>
                        <p>Live overview of your team's attendance.</p>
                    </div>
                    <div class="action-buttons">
                        <a href="<?= route_url('/admin-dashboard/qr') ?>" class="btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            QR Terminal
                        </a>
                        <a href="<?= route_url('/logout') ?>" class="btn-outline" style="display:inline-flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="metric-card">
                        <div class="metric-top">
                            <div class="stat-icon icon-olive">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div class="live-badge">
                                <span class="live-dot"></span>
                                <span class="live-text">Live</span>
                            </div>
                        </div>
                        <div class="stat-value" x-text="stats.currentlyOnsite || 0"></div>
                        <div class="stat-label">Currently Onsite</div>
                        <div class="stat-subtitle">Staff signed in now</div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-top">
                            <div class="stat-icon icon-navy">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="stat-value" x-text="stats.totalClockedInToday || 0"></div>
                        <div class="stat-label">Signed In Today</div>
                        <div class="stat-subtitle">Total clock-ins today</div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-top">
                            <div class="stat-icon icon-gray">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                        </div>
                        <div class="stat-value" x-text="stats.totalEventsToday || 0"></div>
                        <div class="stat-label">Total Events</div>
                        <div class="stat-subtitle">All attendance actions</div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-top">
                            <div class="stat-icon icon-olive">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                        </div>
                        <div class="stat-value" x-text="stats.pendingSync || 0"></div>
                        <div class="stat-label">Pending Sync</div>
                        <div class="stat-subtitle">Awaiting Google Sheets</div>
                    </div>
                </div>

                <!-- Quick Access Features -->
                <div class="feature-grid">
                    <div class="feature-card" onclick="window.location.href='<?= route_url('/admin-dashboard/qr') ?>'">
                        <div class="feature-top">
                            <div class="feature-icon icon-olive">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </div>
                            <div class="arrow-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                        <h3>QR Terminal</h3>
                        <p>Generate live QR codes for staff clock-in/out</p>
                    </div>

                    <div class="feature-card" onclick="window.location.href='<?= route_url('/admin-dashboard/users') ?>'">
                        <div class="feature-top">
                            <div class="feature-icon icon-navy">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <div class="arrow-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                        <h3>User Management</h3>
                        <p>Add, edit, or disable staff accounts</p>
                    </div>

                    <div class="feature-card" onclick="window.location.href='<?= route_url('/admin-dashboard/attendance') ?>'">
                        <div class="feature-top">
                            <div class="feature-icon icon-gray">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="arrow-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                        <h3>Attendance Logs</h3>
                        <p>View and export all attendance records</p>
                    </div>
                </div>

                <!-- Staff Online -->
                <div class="large-card">
                    <div class="section-header">
                        <div class="section-header-left">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                style="display:inline-block; vertical-align:middle; margin-right:6px;">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            Staff Online
                        </h3>
                            <p>Team members currently signed in</p>
                        </div>
                        <div class="live-indicator">
                            <span class="live-dot"></span>
                            <span class="live-text" x-text="onsiteStaff.length + ' online'"></span>
                        </div>
                    </div>
                    <div class="staff-list" x-show="onsiteStaff.length > 0">
                        <template x-for="staff in onsiteStaff" :key="staff.id">
                            <div class="staff-item">
                                <div class="staff-left">
                                    <div class="staff-avatar" x-text="getInitials(staff.name)"></div>
                                    <div class="staff-info">
                                        <div class="staff-name" x-text="staff.name"></div>
                                        <div class="staff-id" x-text="'ID: ' + (staff.staff_id || staff.employee_id || staff.id)"></div>
                                    </div>
                                </div>
                                <div class="staff-right">
                                    <span class="status-pill">
                                        <span class="status-dot"></span>
                                        <span>SIGNED IN</span>
                                    </span>
                                    <div class="staff-time" x-text="'since ' + formatTime(staff.sign_in_time)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="onsiteStaff.length === 0" style="color:var(--muted);padding:20px;text-align:center;">
                        No staff currently signed in.
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="large-card">
                    <div class="section-header">
                        <div class="section-header-left">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                style="display:inline-block; vertical-align:middle; margin-right:6px;">
                                <rect width="8" height="4" x="8" y="2" rx="1"/>
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                <path d="M12 11h4"/>
                                <path d="M12 16h4"/>
                                <path d="M8 11h.01"/>
                                <path d="M8 16h.01"/>
                            </svg>
                            Recent Activity
                        </h3>
                            <p>Latest attendance events</p>
                        </div>
                            <button class="view-all-btn" onclick="window.location.href='<?= route_url('/admin-dashboard/attendance') ?>'">
                                View all
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    style="vertical-align:middle;">
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                    <polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </button>
                    </div>
                    <div class="activity-list" x-show="recentActivity.length > 0">
                        <template x-for="activity in recentActivity" :key="activity.id">
                            <div class="activity-item">
                                <div class="activity-left">
                                    <span class="activity-indicator" :class="activity.action"></span>
                                    <div class="activity-info">
                                        <div class="activity-name" x-text="activity.name"></div>
                                        <div class="activity-type" x-text="activity.action === 'sign-in' ? 'Signed In' : 'Signed Out'"></div>
                                    </div>
                                </div>
                                <div class="activity-time">
                                    <div class="activity-time-value" x-text="formatTime(activity.timestamp)"></div>
                                    <div class="activity-date" x-text="formatDate(activity.timestamp)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="recentActivity.length === 0" style="color:var(--muted);padding:20px;text-align:center;">
                        No recent activity.
                    </div>
                </div>

                <!-- Google Sheets Integration -->
                <div class="sheets-card">
                    <div class="sheets-content">
                        <div class="sheets-left">
                            <div class="sheets-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                            </div>
                            <div class="sheets-info">
                                <h3>Google Sheets Sync</h3>
                                <p>Real-time attendance data pushed to Google Sheets</p>
                            </div>
                        </div>
                        <div class="sheets-right">
                            <span class="connection-status" id="sheetsStatus">● Checking...</span>
                            <button class="connect-btn" onclick="testSheetsConnection()">Test Connection</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function dashboardApp() {
    return {
        sidebarOpen: false,
        stats: { currentlyOnsite: 0, totalClockedInToday: 0, totalEventsToday: 0, pendingSync: 0 },
        onsiteStaff: [],
        recentActivity: [],
        isLoading: true,
        refreshInterval: null,
        
        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        },
        
        formatTime(timestamp) {
            if (!timestamp) return '--:--';
            const date = new Date(timestamp);
            return date.toLocaleTimeString('en-ZA', { hour: '2-digit', minute: '2-digit' });
        },
        
        formatDate(timestamp) {
            if (!timestamp) return '--';
            const date = new Date(timestamp);
            return date.toLocaleDateString('en-ZA', { day: '2-digit', month: 'short' });
        },
        
        async init() {
            window.themeManager.initTheme();
            await this.loadDashboard();
            this.refreshInterval = setInterval(() => this.loadDashboard(), 15000);
        },
        
        async loadDashboard() {
            try {
                // Use the correct API paths with /index.php prefix
                const [statsRes, staffRes, activityRes] = await Promise.all([
                    fetch('/index.php/api/dashboard-stats'),
                    fetch('/index.php/api/onsite-staff'),
                    fetch('/index.php/api/recent-activity')
                ]);
                
                const statsData = await statsRes.json();
                const staffData = await staffRes.json();
                const activityData = await activityRes.json();
                
                this.stats = statsData.data || statsData || {};
                this.onsiteStaff = staffData.data || staffData || [];
                this.recentActivity = activityData.data || activityData || [];
                this.isLoading = false;
                this.updateSheetsStatus();
            } catch (err) {
                console.error('Error loading dashboard:', err);
                this.isLoading = false;
                window.appUtils.showToast('Failed to load dashboard data', 'error');
            }
        },
        
        updateSheetsStatus() {
            const el = document.getElementById('sheetsStatus');
            if (el) {
                if (this.stats.pendingSync === 0) {
                    el.textContent = '● Connected';
                    el.style.color = 'var(--accent)';
                } else {
                    el.textContent = '● Syncing...';
                    el.style.color = '#F59E0B';
                }
            }
        }
    }
}

// Global function for sheets connection test
async function testSheetsConnection() {
    const el = document.getElementById('sheetsStatus');
    if (el) {
        el.textContent = '● Testing...';
        el.style.color = '#F59E0B';
    }
    
    try {
        const response = await fetch('/index.php/api/test-sheets-connection?_=' + Date.now());
        const data = await response.json();
        
        if (el) {
            if (data.connected) {
                el.textContent = '● Connected';
                el.style.color = 'var(--accent)';
                window.appUtils.showToast('Google Sheets is connected!', 'success');
            } else {
                el.textContent = '● Disconnected';
                el.style.color = '#EF4444';
                window.appUtils.showToast('❌ Connection failed: ' + (data.error || 'Unknown error'), 'error');
            }
        }
    } catch (err) {
        if (el) {
            el.textContent = '● Error';
            el.style.color = '#EF4444';
        }
        window.appUtils.showToast('Connection test failed', 'error');
    }
}
</script>

</body>
</html>