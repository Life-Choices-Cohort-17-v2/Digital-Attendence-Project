<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header('Location: /index.php/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="/assets/js/app.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .staff-dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .greeting { margin-bottom: 24px; }
        .greeting h1 { font-size: 28px; font-weight: 700; color: var(--heading); }
        .greeting p { font-size: 14px; color: var(--text); }
        
        .status-block {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 24px;
        }
        .offsite-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
            background: rgba(168, 201, 122, 0.12);
            color: #A8C97A;
        }
        .offsite-badge.onsite {
            background: rgba(156, 176, 122, 0.2);
            color: #9CB07A;
        }
        .status-block p { font-size: 14px; color: var(--text); margin-bottom: 4px; }
        .status-block h2 { font-size: 32px; font-weight: 700; color: var(--heading); margin-bottom: 16px; }
        .scan-btn {
            display: inline-block;
            background: var(--sidebar-blue);
            color: white;
            padding: 12px 32px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }
        .scan-btn:hover { background: var(--sidebar-hover); }
        
        .dashboard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 700px) { .dashboard-grid { grid-template-columns: 1fr; } }
        .dashboard-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
        }
        .dashboard-card:hover { transform: translateY(-4px); border-color: var(--olive-green); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .dashboard-card .icon { font-size: 32px; margin-bottom: 8px; }
        .dashboard-card h3 { font-size: 16px; font-weight: 600; color: var(--heading); }
        .dashboard-card p { font-size: 13px; color: var(--text); }
        
        .staff-status-section {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .staff-status-section .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--heading);
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .staff-status-section .section-title .count {
            font-size: 13px;
            color: var(--text);
            font-weight: 400;
        }
        .staff-list { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        @media (max-width: 600px) { .staff-list { grid-template-columns: 1fr; } }
        .staff-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: var(--background);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .staff-item .name { font-weight: 500; color: var(--heading); }
        .staff-item .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .staff-item .status-dot.online { background: #22c55e; }
        .staff-item .status-dot.offline { background: #94a3b8; }
        .staff-item .status-text { font-size: 12px; font-weight: 500; }
        .staff-item .status-text.online { color: #22c55e; }
        .staff-item .status-text.offline { color: #94a3b8; }
        
        .activity-section {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
        }
        .activity-section .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--heading);
            margin-bottom: 16px;
        }
        .activity-list { display: flex; flex-direction: column; gap: 8px; }
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-item .activity-left { display: flex; align-items: center; gap: 10px; }
        .activity-item .activity-type {
            font-size: 12px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 12px;
        }
        .activity-item .activity-type.in { background: var(--olive-green-soft); color: var(--olive-green); }
        .activity-item .activity-type.out { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .activity-item .activity-name { font-weight: 500; color: var(--heading); }
        .activity-item .activity-time { font-size: 12px; color: var(--text); }
        
        .empty-state { text-align: center; padding: 20px; color: var(--text); }
    </style>
</head>
<body>

<div x-data="dashboardApp()" x-init="init()" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'dashboard'; include __DIR__ . '/staff-sidebar.php'; ?>
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            <div class="staff-dashboard-container">
                <!-- Greeting -->
                <div class="greeting">
                    <h1>Hi, <?= htmlspecialchars($_SESSION['staff_name'] ?? 'User') ?></h1>
                    <p x-text="currentDateFormatted"></p>
                </div>

                <!-- Status Block -->
                <div class="status-block">
                    <div class="offsite-badge" :class="isClockedIn ? 'onsite' : ''" x-text="isClockedIn ? '🟢 ONSITE' : '⚪ OFFSITE'">OFFSITE</div>
                    <p>You are currently</p>
                    <h2 x-text="isClockedIn ? 'Signed In' : 'Signed Out'">Sign Out</h2>
                    <a href="/index.php/scan-qr" class="scan-btn">📷 Scan QR</a>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-grid">
                    <a href="/index.php/calendar" class="dashboard-card">
                        <div class="icon">📅</div>
                        <h3>Calendar</h3>
                        <p>View your schedule</p>
                    </a>
                    <a href="/index.php/history" class="dashboard-card">
                        <div class="icon">📊</div>
                        <h3>History</h3>
                        <p>Your attendance records</p>
                    </a>
                    <a href="/index.php/profile" class="dashboard-card">
                        <div class="icon">👤</div>
                        <h3>Profile</h3>
                        <p>Manage your account</p>
                    </a>
                </div>

                <!-- Staff Status -->
                <div class="staff-status-section">
                    <div class="section-title">
                        <span>👥 Staff Online</span>
                        <span class="count" x-text="onlineStaff.length + ' online'"></span>
                    </div>
                    <div class="staff-list" x-show="onlineStaff.length > 0">
                        <template x-for="staff in onlineStaff" :key="staff.id">
                            <div class="staff-item">
                                <span class="name" x-text="staff.name"></span>
                                <div>
                                    <span class="status-dot online"></span>
                                    <span class="status-text online">Online</span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="empty-state" x-show="onlineStaff.length === 0">
                        No staff currently online
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-section">
                    <div class="section-title">📋 Recent Activity</div>
                    <div class="activity-list" x-show="recentActivity.length > 0">
                        <template x-for="activity in recentActivity" :key="activity.id">
                            <div class="activity-item">
                                <div class="activity-left">
                                    <span class="activity-type" :class="activity.action" x-text="activity.action === 'sign-in' ? 'IN' : 'OUT'"></span>
                                    <span class="activity-name" x-text="activity.name"></span>
                                </div>
                                <span class="activity-time" x-text="formatTime(activity.timestamp)"></span>
                            </div>
                        </template>
                    </div>
                    <div class="empty-state" x-show="recentActivity.length === 0">
                        No recent activity
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
        isClockedIn: false,
        onlineStaff: [],
        recentActivity: [],
        user: <?php echo json_encode([
            'id' => $_SESSION['staff_id'] ?? 'STF-001',
            'name' => $_SESSION['staff_name'] ?? 'Staff'
        ]); ?>,
        pollTimer: null,
        
        get currentDateFormatted() {
            return new Date().toLocaleDateString('en-ZA', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },
        
        formatTime(timestamp) {
            if (!timestamp) return '--:--';
            return new Date(timestamp).toLocaleTimeString('en-ZA', { hour: '2-digit', minute: '2-digit' });
        },
        
        async init() {
            window.themeManager.initTheme();
            await this.loadDashboardData();
            
            const isMobile = /iPhone|Android|iPad|iPod|Mobile/i.test(navigator.userAgent);
            const interval = isMobile ? 30000 : 15000;
            
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.pollTimer = setInterval(() => this.loadDashboardData(), interval);
        },

        async loadDashboardData() {
            try {
                const staffResponse = await fetch('/index.php/api/onsite-staff');
                const staffData = await staffResponse.json();
                const onsite = staffData.data || [];
                
                this.isClockedIn = onsite.some(s => s.id === this.user.id);
                this.onlineStaff = onsite;
                
                const activityResponse = await fetch('/index.php/api/recent-activity');
                const activityData = await activityResponse.json();
                this.recentActivity = activityData.data || [];
                
                this.updateUI();
            } catch (err) {
                console.error('Error loading dashboard data:', err);
            }
        },
        
        updateUI() {
            const badge = document.querySelector('.offsite-badge');
            const statusText = document.querySelector('.status-block h2');
            if (badge) {
                badge.textContent = this.isClockedIn ? '🟢 ONSITE' : '⚪ OFFSITE';
                badge.className = 'offsite-badge' + (this.isClockedIn ? ' onsite' : '');
            }
            if (statusText) statusText.textContent = this.isClockedIn ? 'Signed In' : 'Signed Out';
        }
    }
}
</script>
</body>
</html>