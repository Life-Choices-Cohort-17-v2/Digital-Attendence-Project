<?php
// frontend/src/views/admin/qr.php
// Dynamic QR Terminal - Ephemeral QR codes, auto-refreshing
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$role = $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? null;
if ($role !== 'admin') {
    header('Location: ' . route_url('/login'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>QR Terminal | SpySee</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        .qr-page-content {
            padding: 28px 32px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .qr-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .qr-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
        }
        .qr-header .sub {
            color: var(--text);
            font-size: 14px;
            margin-top: 4px;
        }
        .location-badge {
            display: inline-block;
            background: var(--accent-soft);
            color: var(--accent);
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .qr-card {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 20px;
            padding: 32px;
            text-align: center;
            margin-bottom: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: transform 0.1s, border-color 0.2s;
        }
        .qr-card:hover {
            border-color: var(--accent);
        }
        .qr-card:active {
            transform: scale(0.99);
        }
        .qr-card .label {
            font-size: 13px;
            color: var(--text);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        #qrbox {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            margin: 12px 0 8px;
        }
        #qrbox canvas,
        #qrbox img {
            max-width: 100%;
            height: auto;
            background: #fff;
            padding: 16px;
            border-radius: 12px;
        }
        
        .countdown {
            font-size: 48px;
            font-weight: 700;
            color: var(--accent);
            margin: 4px 0;
            line-height: 1;
        }
        .countdown-label {
            font-size: 14px;
            color: var(--text);
        }
        .refresh-hint {
            font-size: 12px;
            color: var(--muted);
            margin-top: 12px;
            opacity: 0.6;
        }
        
        .staff-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 16px;
            margin-top: 12px;
        }
        .staff-card .title {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
            margin-bottom: 8px;
        }
        .staff-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        .staff-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 12px;
            background: var(--background);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 13px;
            color: var(--heading);
        }
        .badge-in { color: var(--accent); font-weight: 600; }
        .badge-out { color: #EF4444; font-weight: 600; }
        .staff-empty { color: var(--text); font-size: 13px; padding: 8px 0; text-align: center; }
        
        .footer {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: var(--muted);
        }
        
        .qr-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
        }
        .qr-loading .spinner {
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
        
        .info-text {
            margin-top: 12px;
            font-size: 12px;
            color: var(--muted);
            text-align: center;
        }
        .info-text strong {
            color: var(--accent);
        }
        
        @media (max-width: 600px) {
            .qr-page-content { padding: 16px; }
            .qr-card { padding: 20px; }
            .countdown { font-size: 36px; }
            #qrbox { min-height: 220px; }
            .staff-grid { grid-template-columns: 1fr; }
        }
        @media print {
            .footer { display: none; }
            .qr-card { box-shadow: none; border-color: var(--border-color); }
        }
    </style>
</head>
<body>

<script>window.themeManager.initTheme();</script>

<div x-data="qrApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'qr'; include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="qr-page-content">
                <div class="qr-header">
                    <h1 style="display:flex; align-items:center; justify-content:center; gap:8px;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                            <path d="M14 14h3v3h-3z"/>
                            <path d="M21 14v3"/>
                            <path d="M17 21h4"/>
                        </svg>
                        QR Terminal
                    </h1>
                    <p class="sub">Scan this QR code with your phone to clock in/out</p>
                    <div class="location-badge" id="locationBadge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"
                            style="vertical-align:middle; margin-right:4px;">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        HQ Entrance
                    </div>
                </div>

                <div class="qr-card" @click="generateQR()" id="qrCard">
                    <div class="label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"
                            style="vertical-align:middle; margin-right:4px;">
                            <path d="M9 2H5a2 2 0 0 0-2 2v4"/>
                            <path d="M15 2h4a2 2 0 0 1 2 2v4"/>
                            <path d="M21 15v4a2 2 0 0 1-2 2h-4"/>
                            <path d="M9 21H5a2 2 0 0 1-2-2v-4"/>
                            <path d="M7 7h3v3H7z"/>
                            <path d="M14 7h3v3h-3z"/>
                            <path d="M7 14h3v3H7z"/>
                            <path d="M14 14h3v3h-3z"/>
                        </svg>
                        Scan to Clock In/Out
                    </div>
                    <div id="qrbox">
                        <div class="qr-loading" id="qrLoading">
                            <div class="spinner"></div>
                            <div style="margin-top:12px;color:var(--text);font-size:14px;">Generating QR code...</div>
                        </div>
                    </div>
                    <div class="countdown" id="countdown">30</div>
                    <div class="countdown-label">seconds until refresh</div>
                    <div class="refresh-hint">Tap the QR code to refresh</div>
                </div>

                <div class="info-text">
                    <div class="info-text">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"
                            style="vertical-align:middle; margin-right:4px;">
                            <path d="M21 12a9 9 0 0 0-15.3-6.3L3 8"/>
                            <path d="M3 3v5h5"/>
                            <path d="M3 12a9 9 0 0 0 15.3 6.3L21 16"/>
                            <path d="M21 21v-5h-5"/>
                        </svg>
                        <strong>Dynamic QR</strong> • Refreshes every 30s • <strong>No token storage</strong> • Uses your logged-in session
                    </div>
                </div>

                <div class="staff-card">
                    <div class="title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"
                            style="vertical-align:middle; margin-right:4px;">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Staff Online
                    </div>
                    <div id="staffList">
                        <div class="staff-empty">Loading staff...</div>
                    </div>
                </div>

                <div class="footer">
                    Powered by SpySee &bull; <span id="currentTime"></span>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let timer = null;
let secondsLeft = 30;
let qrCodeInstance = null;
let refreshTimeout = null;

const LOCATIONS = ['HQ Entrance', 'HQ Exit', 'Lobby', 'Office A', 'Office B'];
let currentLocation = LOCATIONS[0];
let locationIndex = 0;

function getBaseUrl() {
    // Get the current host
    const host = window.location.host;
    
    // Check if we're on localhost
    const isLocalhost = host === 'localhost' || 
                        host === '127.0.0.1' || 
                        host === '0.0.0.0' ||
                        host.startsWith('192.168.') ||
                        host.startsWith('10.') ||
                        host.startsWith('172.') ||
                        host.includes('localhost');
    
    if (isLocalhost) {
        // Try to get the ngrok URL from the page
        // If the page was loaded via ngrok, use that
        // Otherwise, use a hardcoded fallback
        
        // Check if there's a meta tag with the ngrok URL
        const metaNgrok = document.querySelector('meta[name="ngrok-url"]');
        if (metaNgrok) {
            return metaNgrok.getAttribute('content');
        }
        
        // Fallback to hardcoded URL - update this when ngrok restarts
        // You can also set this via an environment variable or config
        return 'https://glance-rancidity-level.ngrok-free.dev';
    }
    
    // If we're already on ngrok, use the current host
    if (host.includes('ngrok-free.dev') || host.includes('grrok-free.dev')) {
        return 'https://' + host;
    }
    
    // Fallback
    return window.location.origin;
}

async function generateQR() {
    const box = document.getElementById('qrbox');
    if (!box) return;
    
    // Clear any existing QR code instance
    if (qrCodeInstance) {
        qrCodeInstance.clear();
        qrCodeInstance = null;
    }
    
    box.innerHTML = `
        <div class="qr-loading">
            <div class="spinner"></div>
            <div style="margin-top:12px;color:var(--text);font-size:14px;">Generating QR code...</div>
        </div>
    `;
    
    try {
        // Generate a fresh token - completely random, not stored anywhere
        const token = Math.random().toString(36).substring(2, 10);
        const expires = new Date(Date.now() + 30000).toISOString();
        
        const baseUrl = getBaseUrl();
        
        // Ephemeral QR - token only exists in this URL, never stored
        const qrUrl = baseUrl + '/scan.php?' + 
            'token=' + encodeURIComponent(token) +
            '&expires=' + encodeURIComponent(expires) +
            '&staff_id=QR_SCAN' +
            '&name=QR_Scan' +
            '&method=QR' +
            '&location=' + encodeURIComponent(currentLocation);
        
        console.log('📱 QR Generated at ' + new Date().toLocaleTimeString());
        console.log('📍 Location:', currentLocation);
        console.log('🔑 Token:', token);
        console.log('⏰ Expires:', expires);
        
        box.innerHTML = '';
        const div = document.createElement('div');
        box.appendChild(div);
        
        qrCodeInstance = new QRCode(div, {
            text: qrUrl,
            width: 280,
            height: 280,
            colorDark: '#1b1f23',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
        
        document.getElementById('locationBadge').innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                style="vertical-align:middle; margin-right:4px;">
                <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            ${currentLocation}
        `;
        
        // Reset and start countdown
        startCountdown(30);
        
        // Rotate location for next QR
        locationIndex = (locationIndex + 1) % LOCATIONS.length;
        currentLocation = LOCATIONS[locationIndex];
        
    } catch (err) {
        console.error('QR Generation Error:', err);
        box.innerHTML = '<div style="color:#EF4444;padding:20px;">Error generating QR: ' + err.message + '</div>';
    }
}

function startCountdown(seconds) {
    // Clear any existing timers
    if (timer) clearInterval(timer);
    if (refreshTimeout) clearTimeout(refreshTimeout);
    
    secondsLeft = seconds;
    const el = document.getElementById('countdown');
    if (el) el.textContent = secondsLeft;
    
    timer = setInterval(() => {
        secondsLeft -= 1;
        if (secondsLeft <= 0) {
            clearInterval(timer);
            timer = null;
            // Auto-refresh when countdown reaches 0
            refreshTimeout = setTimeout(() => {
                generateQR();
            }, 100);
        } else {
            const e = document.getElementById('countdown');
            if (e) e.textContent = secondsLeft;
        }
    }, 1000);
}

async function loadStaff() {
    try {
        const response = await fetch('/index.php/api/onsite-staff?_=' + Date.now());
        const data = await response.json();
        const staff = data.data || [];
        const container = document.getElementById('staffList');
        
        if (staff.length > 0) {
            const html = staff.map(s => {
                const status = s.status === 'signed_in' ? 'in' : 'out';
                const badgeClass = status === 'in' ? 'badge-in' : 'badge-out';
            const label = status === 'in'
                ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="vertical-align:middle; margin-right:3px;">
                    <polyline points="20 6 9 17 4 12"/>
                </svg> In`
                : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="vertical-align:middle; margin-right:3px;">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg> Out`;
                return `
                    <div class="staff-item">
                        <span>${escapeHtml(s.name)}</span>
                        <span class="${badgeClass}">${label}</span>
                    </div>
                `;
            }).join('');
            container.innerHTML = '<div class="staff-grid">' + html + '</div>';
        } else {
            container.innerHTML = '<div class="staff-empty">No staff currently signed in.</div>';
        }
    } catch (err) {
        console.error('Error loading staff:', err);
        document.getElementById('staffList').innerHTML = '<div style="color:#EF4444;font-size:13px;">Error loading staff: ' + err.message + '</div>';
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

function updateClock() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString();
}

// Alpine.js App
function qrApp() {
    return {
        sidebarOpen: false,
        
        init() {
            window.themeManager.initTheme();
            generateQR();
            loadStaff();
            updateClock();
            setInterval(loadStaff, 15000);
            setInterval(updateClock, 1000);
        }
    }
}
</script>

</body>
</html>