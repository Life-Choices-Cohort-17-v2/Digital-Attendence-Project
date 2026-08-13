<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /index.php/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Admin Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="/assets/js/app.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --primary: #2f6f4f;
            --danger: #a3432f;
            --paper: #fafaf8;
            --line: #dcdcd6;
            --muted: #6b6f76;
            --ink: #1b1f23;
            --radius: 12px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--paper); color: var(--ink); padding: 16px; min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 12px 0 16px; border-bottom: 1px solid var(--line); margin-bottom: 16px; flex-wrap: wrap; gap: 8px; }
        .header h1 { font-size: 22px; }
        .header .sub { color: var(--muted); font-size: 14px; }
        .header-actions { display: flex; gap: 8px; align-items: center; }
        .fullscreen-btn { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-outline { background: transparent; border: 2px solid var(--line); border-radius: 8px; padding: 8px 16px; font-size: 14px; font-weight: 600; cursor: pointer; color: var(--ink); text-decoration: none; }
        .btn-outline.danger { border-color: var(--danger); color: var(--danger); }
        .qr-section { background: #fff; border: 2px solid var(--line); border-radius: 16px; padding: 20px; text-align: center; margin-bottom: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); cursor: pointer; }
        .qr-section .label { font-size: 13px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        #qrbox { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 180px; margin: 8px 0 4px; }
        #qrbox canvas { max-width: 100%; height: auto; }
        .countdown { font-size: 28px; font-weight: 700; color: var(--primary); margin: 2px 0; }
        .countdown-label { font-size: 12px; color: var(--muted); }
        .refresh-btn { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 8px 20px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .qr-info { font-size: 12px; color: var(--muted); margin-top: 6px; }
        .staff-count { font-size: 13px; color: var(--muted); margin-top: 4px; }
        .staff-count b { color: var(--primary); }
        .staff-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 8px; }
        .staff-item { display: flex; justify-content: space-between; padding: 6px 12px; background: var(--paper); border-radius: 6px; border: 1px solid var(--line); font-size: 13px; }
        .badge-in { color: var(--primary); font-weight: 600; }
        .badge-out { color: var(--danger); font-weight: 600; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 16px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card-title { font-size: 14px; font-weight: 600; color: var(--muted); margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .spinner { text-align: center; padding: 20px; color: var(--muted); }
        .spinner .dot { display: inline-block; width: 10px; height: 10px; margin: 0 4px; background: var(--primary); border-radius: 50%; animation: bounce 1.2s infinite ease-in-out; }
        .spinner .dot:nth-child(2) { animation-delay: 0.2s; }
        .spinner .dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; } 40% { transform: scale(1); opacity: 1; } }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
        .stat-box { background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 16px; text-align: center; }
        .stat-box .number { font-size: 28px; font-weight: 700; color: var(--primary); }
        .stat-box .label { font-size: 12px; color: var(--muted); margin-top: 4px; }
        .scan-toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); padding: 16px 24px; border-radius: 12px; text-align: center; font-size: 16px; font-weight: 600; display: none; z-index: 1000; max-width: 90%; box-shadow: 0 8px 32px rgba(0,0,0,0.15); }
        .scan-toast.success { display: block; background: var(--primary); color: #fff; }
        .scan-toast .name { font-weight: 700; }
        .scan-toast .time { font-weight: 400; font-size: 13px; opacity: 0.8; margin-top: 4px; }
        .activity-list { display: flex; flex-direction: column; gap: 4px; }
        .activity-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--line); font-size: 13px; }
        .activity-item:last-child { border-bottom: none; }
        .activity-item .act-type { font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 10px; }
        .activity-item .act-type.in { background: var(--primary-light); color: var(--primary); }
        .activity-item .act-type.out { background: rgba(163, 67, 47, 0.1); color: var(--danger); }
        .activity-item .act-time { color: var(--muted); font-size: 12px; }
        @media (max-width: 480px) { .header { flex-direction: column; gap: 8px; align-items: stretch; text-align: center; } .header-actions { justify-content: center; } .staff-grid { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; } .stat-box .number { font-size: 20px; } .qr-section { padding: 16px; } #qrbox { min-height: 150px; } }
    </style>
</head>
<body>

<script>window.themeManager.initTheme();</script>

<div x-data="dashboardApp()" x-init="init()" x-cloak>
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
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="/index.php/admin-dashboard/qr" class="fullscreen-btn">📱 QR Fullscreen</a>
                        <a href="/index.php/logout" class="btn-outline danger" style="padding: 8px 16px; font-size: 13px;">🚪 Logout</a>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="number" x-text="stats.currentlyOnsite || 0"></div>
                        <div class="label">Currently Onsite</div>
                    </div>
                    <div class="stat-box">
                        <div class="number" x-text="stats.totalClockedInToday || 0"></div>
                        <div class="label">Signed In Today</div>
                    </div>
                    <div class="stat-box">
                        <div class="number" x-text="stats.totalEventsToday || 0"></div>
                        <div class="label">Total Events</div>
                    </div>
                </div>

                <div class="qr-section" id="qrCard">
                    <div class="label">📱 Scan to Clock In/Out</div>
                    <div id="qrbox"><div style="color:var(--muted);">⏳ Generating...</div></div>
                    <div class="countdown" id="countdown">30</div>
                    <div class="countdown-label">seconds until refresh</div>
                    <div class="staff-count" id="staffCount">👥 <b>0</b> staff online</div>
                    <button class="refresh-btn" onclick="generateQR()">🔄 Refresh Now</button>
                    <div class="qr-info">💡 Each QR is valid for <strong>one scan only</strong>. New code every 30s.</div>
                </div>

                <div id="scanToast" class="scan-toast">
                    <div id="toastContent">
                        <span id="toastIcon">✅</span>
                        <span id="toastText">Sign in successful</span>
                        <div class="name" id="toastName"></div>
                        <div class="time" id="toastTime"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">👥 Staff Online</div>
                    <div id="staffList">
                        <div class="spinner">
                            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                            <div style="margin-top:6px;font-size:13px;">Loading staff...</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">📋 Recent Activity</div>
                    <div id="activityList">
                        <div class="spinner">
                            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                            <div style="margin-top:6px;font-size:13px;">Loading activity...</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// ============================================================
// ADMIN DASHBOARD - FULL FEATURES
// ============================================================

const isMobile = /iPhone|Android|iPad|iPod|Mobile/i.test(navigator.userAgent);

const STAFF_INTERVAL = isMobile ? 30000 : 15000;
const STATS_INTERVAL = isMobile ? 60000 : 30000;
const SCAN_INTERVAL = isMobile ? 10000 : 5000;

let timer = null;
let secondsLeft = 30;
let qrCodeInstance = null;
let toastTimeout = null;
let locations = ['HQ Entrance', 'HQ Exit', 'Lobby', 'Office A', 'Office B'];
let currentLocationIndex = 0;

function getBaseUrl() {
    const isLocalhost = window.location.hostname === 'localhost' || 
                        window.location.hostname === '127.0.0.1' ||
                        window.location.hostname === '0.0.0.0';
    if (isLocalhost) {
        return 'https://glance-rancidity-level.ngrok-free.dev';
    }
    return window.location.origin;
}

async function generateQR() {
    const box = document.getElementById('qrbox');
    if (!box) return;
    
    box.innerHTML = '<div style="color:var(--muted);">⏳ Generating...</div>';
    
    try {
        const token = Math.random().toString(36).substring(2, 10);
        const expires = new Date(Date.now() + 30000).toISOString();
        const location = locations[currentLocationIndex % locations.length];
        currentLocationIndex++;
        const baseUrl = getBaseUrl();
        const qrUrl = baseUrl + '/scan.php?' + 
            'token=' + encodeURIComponent(token) +
            '&expires=' + encodeURIComponent(expires) +
            '&staff_id=QR_SCAN' +
            '&name=QR_Scan' +
            '&method=QR' +
            '&location=' + encodeURIComponent(location);
        
        box.innerHTML = '';
        const div = document.createElement('div');
        box.appendChild(div);
        qrCodeInstance = new QRCode(div, {
            text: qrUrl,
            width: 200,
            height: 200,
            colorDark: '#1b1f23',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
        startCountdown(30);
    } catch (err) {
        box.innerHTML = '<div style="color:#a3432f;">❌ Error: ' + err.message + '</div>';
    }
}

function startCountdown(seconds) {
    if (timer) clearInterval(timer);
    secondsLeft = seconds;
    const countdownEl = document.getElementById('countdown');
    if (countdownEl) countdownEl.textContent = secondsLeft;
    timer = setInterval(() => {
        secondsLeft -= 1;
        if (secondsLeft <= 0) {
            generateQR();
        } else {
            const el = document.getElementById('countdown');
            if (el) el.textContent = secondsLeft;
        }
    }, 1000);
}

function showToast(message, type, name = '', timestamp = '') {
    const toast = document.getElementById('scanToast');
    document.getElementById('toastIcon').textContent = type === 'success' ? '✅' : '❌';
    document.getElementById('toastText').textContent = message;
    document.getElementById('toastName').textContent = name ? '[' + name + ']' : '';
    document.getElementById('toastTime').textContent = timestamp ? 'at ' + timestamp : '';
    toast.className = 'scan-toast ' + type;
    toast.style.display = 'block';
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => { toast.style.display = 'none'; }, 5000);
}

async function loadStaff() {
    try {
        const response = await fetch('/api/onsite-staff');
        const data = await response.json();
        const staff = data.data || [];
        const container = document.getElementById('staffList');
        if (staff.length > 0) {
            const html = staff.map(s => {
                const status = s.status === 'signed_in' ? 'in' : 'out';
                return `<div class="staff-item"><span>${escapeHtml(s.name)}</span><span class="${status === 'in' ? 'badge-in' : 'badge-out'}">${status === 'in' ? '✅ In' : '❌ Out'}</span></div>`;
            }).join('');
            container.innerHTML = '<div class="staff-grid">' + html + '</div>';
            document.getElementById('staffCount').innerHTML = '👥 <b>' + staff.length + '</b> staff online';
        } else {
            container.innerHTML = '<div style="color:var(--muted);font-size:14px;">No staff currently signed in.</div>';
            document.getElementById('staffCount').innerHTML = '👥 <b>0</b> staff online';
        }
    } catch (err) {
        console.error('Error loading staff:', err);
        document.getElementById('staffList').innerHTML = '<div style="color:var(--danger);font-size:14px;">❌ Error loading staff</div>';
    }
}

async function loadStats() {
    try {
        const response = await fetch('/api/dashboard-stats');
        const data = await response.json();
        const stats = data.data || {};
        const numbers = document.querySelectorAll('.stat-box .number');
        if (numbers.length >= 3) {
            numbers[0].textContent = stats.currentlyOnsite || 0;
            numbers[1].textContent = stats.totalClockedInToday || 0;
            numbers[2].textContent = stats.totalEventsToday || 0;
        }
    } catch (err) {
        console.error('Error loading stats:', err);
    }
}

async function loadActivity() {
    try {
        const response = await fetch('/api/recent-activity');
        const data = await response.json();
        const activities = data.data || [];
        const container = document.getElementById('activityList');
        
        if (activities.length > 0) {
            container.innerHTML = activities.map(a => `
                <div class="activity-item">
                    <span>
                        <span class="act-type ${a.action}">${a.action === 'sign-in' ? 'IN' : 'OUT'}</span>
                        <span style="margin-left:8px;">${escapeHtml(a.name)}</span>
                    </span>
                    <span class="act-time">${new Date(a.timestamp).toLocaleTimeString()}</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div style="color:var(--muted);font-size:14px;padding:12px;">No recent activity</div>';
        }
    } catch (err) {
        console.error('Error loading activity:', err);
        document.getElementById('activityList').innerHTML = '<div style="color:var(--danger);font-size:14px;padding:12px;">❌ Error loading activity</div>';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        if (m === '"') return '&quot;';
        return m;
    });
}

async function checkScanResult() {
    try {
        const response = await fetch('/api/check-scan-result');
        const data = await response.json();
        if (data.success && data.name) {
            showToast(data.action + ' successful', 'success', data.name, data.timestamp);
            setTimeout(() => { loadStaff(); loadStats(); loadActivity(); }, 1000);
        }
    } catch (e) { /* silent fail */ }
}

document.getElementById('qrCard')?.addEventListener('click', function(e) {
    if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;
    generateQR();
});

// Init with delay
setTimeout(() => {
    generateQR();
    loadStaff();
    loadStats();
    loadActivity();
}, isMobile ? 500 : 100);

// Polling with longer intervals
setInterval(loadStaff, STAFF_INTERVAL);
setInterval(loadStats, STATS_INTERVAL);
setInterval(loadActivity, STATS_INTERVAL);
setInterval(checkScanResult, SCAN_INTERVAL);

document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        checkScanResult();
        loadStaff();
        loadStats();
        loadActivity();
    }
});
</script>
</body>
</html>