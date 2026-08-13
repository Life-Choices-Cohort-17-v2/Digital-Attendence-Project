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
    <title><?= $title ?? 'Admin Dashboard' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body>

<div x-data="dashboardApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'dashboard'; include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <div>
                        <h1>Admin Dashboard</h1>
                        <p>Live overview of your team's attendance.</p>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-outline" @click="syncData()" :disabled="isSyncing">
                            <span x-text="isSyncing ? 'Syncing...' : 'Sync now'"></span>
                        </button>
                        <button class="btn-primary" @click="exportData()">Export to Sheets</button>
                    </div>
                </div>

                <div x-show="isLoading" class="stats-grid">
                    <template x-for="i in 4">
                        <div class="metric-card animate-pulse"><div class="skeleton-icon"></div><div class="skeleton-line"></div></div>
                    </template>
                </div>

                <div x-show="!isLoading">
                    <div class="stats-grid">
                        <!-- Currently Onsite - Location Pin/Map Marker Icon -->
                        <div class="metric-card">
                            <div class="metric-top">
                                <div class="stat-icon icon-olive">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </div>
                                <div class="live-badge">
                                    <div class="live-dot"></div>
                                    <span class="live-text">Live</span>
                                </div>
                            </div>
                            <div class="stat-value" x-text="stats.currentlyOnsite"></div>
                            <div class="stat-label">Currently onsite</div>
                            <div class="stat-subtitle">Live count, updates within seconds</div>
                        </div>
                        
                        <!-- Total Signed in Today - Calendar Icon -->
                        <div class="metric-card">
                            <div class="metric-top">
                                <div class="stat-icon icon-olive">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-value" x-text="stats.totalClockedInToday"></div>
                            <div class="stat-label">Total Signed in today</div>
                            <div class="stat-subtitle" x-text="weekday"></div>
                        </div>
                        
                        <!-- Pending Sync - Cloud/Upload Icon -->
                        <div class="metric-card">
                            <div class="metric-top">
                                <div class="stat-icon icon-olive">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                                        <path d="m9 15 3-3 3 3"/>
                                        <path d="M12 12v6"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-value" x-text="stats.pendingSync"></div>
                            <div class="stat-label">Pending sync</div>
                            <div class="stat-subtitle" x-text="stats.pendingSync > 0 ? 'Needs sync' : 'All synced'"></div>
                        </div>
                        
                        <!-- Total Events Today - Trending Up/Activity Icon -->
                        <div class="metric-card">
                            <div class="metric-top">
                                <div class="stat-icon icon-olive">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="23 6 13.5 15.5 8.5 10.5 2 17"/>
                                        <polyline points="17 6 23 6 23 12"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="stat-value" x-text="stats.totalEventsToday"></div>
                            <div class="stat-label">Total events today</div>
                            <div class="stat-subtitle">In + Out</div>
                        </div>
                    </div>

                    <!-- Feature Cards -->
                    <div class="feature-grid">
                        <!-- QR Generator - QR Code Icon -->
                        <div class="feature-card" onclick="window.location.href='<?= route_url('/admin-dashboard/qr-generator') ?>'">
                            <div class="feature-top">
                                <div class="feature-icon icon-olive">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="7" height="7"/>
                                        <rect x="14" y="3" width="7" height="7"/>
                                        <rect x="14" y="14" width="7" height="7"/>
                                        <rect x="3" y="14" width="7" height="7"/>
                                    </svg>
                                </div>
                                <div class="arrow-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                </div>
                            </div>
                            <h3>QR Generator</h3>
                            <p>Create and manage sign-in QR codes</p>
                        </div>
                        
                        <!-- Attendance Logs - List/Document Icon -->
                        <div class="feature-card" onclick="window.location.href='<?= route_url('/admin-dashboard/attendance') ?>'">
                            <div class="feature-top">
                                <div class="feature-icon icon-olive">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="8" y1="6" x2="21" y2="6"/>
                                        <line x1="8" y1="12" x2="21" y2="12"/>
                                        <line x1="8" y1="18" x2="21" y2="18"/>
                                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                                    </svg>
                                </div>
                                <div class="arrow-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                </div>
                            </div>
                            <h3>Attendance Logs</h3>
                            <p>All signed-in events with full audit trail</p>
                        </div>
                        
                        <!-- Settings - Settings/Gear Icon -->
                        <div class="feature-card" onclick="window.location.href='<?= route_url('/admin-dashboard/settings') ?>'">
                            <div class="feature-top">
                                <div class="feature-icon icon-olive">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                    </svg>
                                </div>
                                <div class="arrow-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                </div>
                            </div>
                            <h3>Settings</h3>
                            <p>Manage users and integration</p>
                        </div>
                    </div>

                    <div class="large-card">
                        <div class="section-header">
                            <div class="section-header-left">
                                <h3>Currently onsite</h3>
                                <p>Live count, updates within seconds</p>
                            </div>
                            <div class="live-indicator">
                                <div class="live-dot"></div>
                                <span class="live-text">Live</span>
                            </div>
                        </div>
                        <div x-show="onsiteStaff.length === 0" class="empty-state">No staff currently onsite.</div>
                        <div class="staff-list" x-show="onsiteStaff.length > 0">
                            <template x-for="person in onsiteStaff" :key="person.id">
                                <div class="staff-item">
                                    <div class="staff-left">
                                        <div class="staff-avatar" x-text="getInitials(person.name)"></div>
                                        <div class="staff-info">
                                            <div class="staff-name" x-text="person.name"></div>
                                            <div class="staff-id" x-text="person.role"></div>
                                        </div>
                                    </div>
                                    <div class="staff-right">
                                        <div class="status-pill">
                                            <div class="status-dot"></div>
                                            Onsite
                                        </div>
                                        <div class="staff-time">since <span x-text="formatTime(person.sign_in_time)"></span></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="large-card">
                        <div class="section-header">
                            <h3 class="section-title">Recent Activity</h3>
                            <button class="view-all-btn" onclick="window.location.href='<?= route_url('/admin-dashboard/attendance') ?>'">View all →</button>
                        </div>
                        <div x-show="recentEvents.length === 0" class="empty-state">No recent activity.</div>
                        <div class="activity-list" x-show="recentEvents.length > 0">
                            <template x-for="event in recentEvents" :key="event.id">
                                <div class="activity-item">
                                    <div class="activity-left">
                                        <div class="activity-indicator" :class="event.action.replace('-', '')"></div>
                                        <div class="activity-info">
                                            <div class="activity-name" x-text="event.name"></div>
                                            <div class="activity-type" x-text="event.action"></div>
                                        </div>
                                    </div>
                                    <div class="activity-time">
                                        <div class="activity-time-value" x-text="formatTime(event.timestamp)"></div>
                                        <div class="activity-date" x-text="formatDate(event.timestamp)"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="sheets-card">
                        <div class="sheets-content">
                            <div class="sheets-left">
                                <div class="sheets-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                        <polyline points="10 9 9 9 8 9"/>
                                    </svg>
                                </div>
                                <div class="sheets-info">
                                    <h3>Sheets Integration</h3>
                                    <p>Sync attendance data to Google Sheets</p>
                                </div>
                            </div>
                            <div class="sheets-right">
                                <div class="connection-status">Not connected</div>
                                <button class="connect-btn" onclick="alert('Connect to Google Sheets - Demo only')">Connect</button>
                            </div>
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
        isLoading: true,
        isSyncing: false,
        stats: { currentlyOnsite: 0, totalClockedInToday: 0, pendingSync: 0, totalEventsToday: 0 },
        onsiteStaff: [],
        recentEvents: [],
        weekday: '',
        refreshInterval: null,
        
        async init() {
            this.weekday = getWeekday();
            await this.loadDashboard();
            this.refreshInterval = setInterval(() => this.loadDashboard(), 30000);
        },
        
        async loadDashboard() {
            try {
                const [statsData, staffData, eventsData] = await Promise.all([
                    fetch('api/dashboard-stats.php').then(r => r.json()),
                    fetch('api/onsite-staff.php').then(r => r.json()),
                    fetch('api/recent-activity.php').then(r => r.json())
                ]);
                this.stats = statsData.data || statsData;
                this.onsiteStaff = staffData.data || staffData;
                this.recentEvents = eventsData.data || eventsData;
                this.isLoading = false;
            } catch (err) {
                console.error('Error loading dashboard:', err);
                this.isLoading = false;
            }
        },
        
        async syncData() {
            this.isSyncing = true;
            await this.loadDashboard();
            setTimeout(() => this.isSyncing = false, 500);
        },
        
        exportData() {
            if (this.onsiteStaff.length === 0) { 
                window.appUtils.showToast('No data to export', 'info'); 
                return; 
            }
            let csv = "Name,Role,Sign In Time\n";
            this.onsiteStaff.forEach(p => { 
                csv += `"${p.name}","${p.role}","${formatTime(p.sign_in_time)}"\n`; 
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `attendance_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            window.appUtils.showToast('Export completed!', 'success');
        }
    }
}
</script>
</body>
</html>