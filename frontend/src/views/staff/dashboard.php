<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'staff') {
    header('Location: /index.php/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Staff Dashboard | SpySee</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="/assets/js/app.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        .staff-dashboard-container {
            max-width: 100%;
            padding: 28px 32px;
        }
        
        .greeting {
            margin-bottom: 32px;
        }
        .greeting h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 6px;
        }
        .greeting p {
            font-size: 14px;
            color: var(--text);
        }
        
        .status-block {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 24px;
        }
        .offsite-badge {
            display: inline-block;
            background: rgba(168, 201, 122, 0.12);
            color: #00B000;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .offsite-badge.onsite {
            background: rgba(93, 214, 44, 0.2);
            color: #5DD62C;
        }
        .status-block p {
            font-size: 14px;
            color: var(--text);
            margin-bottom: 6px;
        }
        .status-block h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 24px;
        }
        
        .scan-btn {
            display: inline-block;
            background: var(--accent);
            color: #202020;
            padding: 12px 32px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            font-size: 14px;
        }
        .scan-btn:hover {
            background: var(--primary-green-dark);
            color: #f8f8f8;
        }
        
        .menu-items {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 600px) {
            .menu-items {
                grid-template-columns: 1fr;
            }
        }
        .menu-item {
            display: block;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
        }
        .menu-item:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            background: var(--accent-soft);
        }
        .menu-item h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--heading);
            margin-bottom: 4px;
        }
        .menu-item p {
            font-size: 13px;
            color: var(--text);
        }

        .offsite-badge,
        .scan-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .offsite-badge svg {
            width: 8px;
            height: 8px;
            flex-shrink: 0;
        }

        .scan-btn svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .menu-item > svg {
            width: 24px;
            height: 24px;
            margin-bottom: 12px;
            color: var(--accent);
        }
    </style>
</head>
<body>

<script>window.themeManager.initTheme();</script>

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
                <div class="offsite-badge" :class="isClockedIn ? 'onsite' : ''">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" fill="currentColor"/>
                    </svg>
                    <span x-text="isClockedIn ? 'ONSITE' : 'OFFSITE'">OFFSITE</span>
                </div>
                    <p>You are currently</p>
                    <h2 x-text="isClockedIn ? 'Signed In' : 'Signed Out'">Signed Out</h2>
                    <a href="/index.php/scan-qr" class="scan-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                            <path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 18h2v3h-2z"></path>
                        </svg>
                        <span>Scan QR</span>
                    </a>
                </div>

                    <!-- quick actions -->
                <div class="menu-items">
                    <a href="/index.php/calendar" class="menu-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                            <path d="M16 2v4M8 2v4M3 10h18"></path>
                        </svg>
                        <h3>Calendar</h3>
                        <p>View your schedule</p>
                    </a>

                    <a href="/index.php/history" class="menu-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 19V5"></path>
                            <path d="M4 19h16"></path>
                            <path d="M7 15l3-4 3 2 5-6"></path>
                        </svg>
                        <h3>History</h3>
                        <p>Your attendance records</p>
                    </a>

                    <a href="/index.php/profile" class="menu-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"></path>
                        </svg>
                        <h3>Profile</h3>
                        <p>Manage your account</p>
                    </a>
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
        user: <?php echo json_encode([
            'id' => $_SESSION['staff_id'] ?? 'STF-001',
            'name' => $_SESSION['staff_name'] ?? 'Staff'
        ]); ?>,
        pollTimer: null,
        
        get currentDateFormatted() {
            return new Date().toLocaleDateString('en-ZA', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },

        async init() {
            window.themeManager.initTheme();
            await this.updateStatus();
            
            const isMobile = /iPhone|Android|iPad|iPod|Mobile/i.test(navigator.userAgent);
            const interval = isMobile ? 30000 : 15000;
            
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.pollTimer = setInterval(() => this.updateStatus(), interval);
        },

        async updateStatus() {
            try {
                const response = await fetch('/index.php/api/onsite-staff');
                const data = await response.json();
                const onsite = data.data || [];
                
                this.isClockedIn = onsite.some(s => s.id === this.user.id);
                this.updateUI();
            } catch (err) {
                console.error('Error updating status:', err);
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