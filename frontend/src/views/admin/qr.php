<?php
// frontend/src/views/admin/qr.php
// Fullscreen QR Display for wall-mounted tablets
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
    <title>QR Terminal - Clock In/Out</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #fafaf8;
            color: #1b1f23;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2f6f4f;
        }
        .header .sub {
            color: #6b6f76;
            font-size: 14px;
            margin-top: 4px;
        }
        .location-badge {
            display: inline-block;
            background: #e9f4ee;
            color: #2f6f4f;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .back-btn {
            display: inline-block;
            background: var(--sidebar-blue);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .back-btn:hover {
            background: var(--sidebar-hover);
        }
        .header-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .qr-card {
            background: #ffffff;
            border: 2px solid #dcdcd6;
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            margin-bottom: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: transform 0.1s;
        }
        .qr-card:active {
            transform: scale(0.99);
        }
        .qr-card .label {
            font-size: 13px;
            color: #6b6f76;
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
        }
        
        .countdown {
            font-size: 48px;
            font-weight: 700;
            color: #2f6f4f;
            margin: 4px 0;
            line-height: 1;
        }
        .countdown-label {
            font-size: 14px;
            color: #6b6f76;
        }
        .refresh-hint {
            font-size: 12px;
            color: #6b6f76;
            margin-top: 12px;
            opacity: 0.6;
        }
        
        .staff-card {
            background: #ffffff;
            border: 1px solid #dcdcd6;
            border-radius: 12px;
            padding: 16px;
            margin-top: 12px;
        }
        .staff-card .title {
            font-weight: 600;
            font-size: 14px;
            color: #6b6f76;
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
            background: #fafaf8;
            border-radius: 6px;
            border: 1px solid #dcdcd6;
            font-size: 13px;
        }
        .badge-in { color: #2f6f4f; font-weight: 600; }
        .badge-out { color: #a3432f; font-weight: 600; }
        .staff-empty { color: #6b6f76; font-size: 13px; padding: 8px 0; }
        
        .footer {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: #6b6f76;
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
            border: 4px solid #dcdcd6;
            border-top-color: #2f6f4f;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 600px) {
            .container { padding: 0; }
            .qr-card { padding: 20px; }
            .countdown { font-size: 36px; }
            #qrbox { min-height: 220px; }
            .staff-grid { grid-template-columns: 1fr; }
            body { padding: 10px; }
        }
        @media print {
            .footer { display: none; }
            body { background: #fff; padding: 0; }
            .qr-card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="header-actions">
        <a href="<?= route_url('/admin-dashboard') ?>" class="back-btn">← Back to Dashboard</a>
    </div>

    <div class="header">
        <h1>📱 QR Terminal</h1>
        <p class="sub">Scan this QR code with your phone to clock in/out</p>
        <div class="location-badge" id="locationBadge">📍 HQ Entrance</div>
    </div>

    <div class="qr-card" onclick="generateQR()" id="qrCard">
        <div class="label">📸 Scan to Clock In/Out</div>
        <div id="qrbox">
            <div class="qr-loading" id="qrLoading">
                <div class="spinner"></div>
                <div style="margin-top:12px;color:#6b6f76;font-size:14px;">Generating QR code...</div>
            </div>
        </div>
        <div class="countdown" id="countdown">30</div>
        <div class="countdown-label">seconds until refresh</div>
        <div class="refresh-hint">👆 Tap the QR code to refresh</div>
    </div>

    <div class="staff-card">
        <div class="title">👥 Staff Status</div>
        <div id="staffList">
            <div class="staff-empty">Loading staff...</div>
        </div>
    </div>

    <div class="footer">
        Powered by SpySee &bull; <span id="currentTime"></span>
    </div>

</div>

<script>
let timer = null;
let secondsLeft = 30;
let qrCodeInstance = null;

const LOCATIONS = ['HQ Entrance', 'HQ Exit', 'Lobby', 'Office A', 'Office B'];
let currentLocation = LOCATIONS[0];
let locationIndex = 0;

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
    
    box.innerHTML = `
        <div class="qr-loading">
            <div class="spinner"></div>
            <div style="margin-top:12px;color:#6b6f76;font-size:14px;">Generating QR code...</div>
        </div>
    `;
    
    try {
        const token = Math.random().toString(36).substring(2, 10);
        const expires = new Date(Date.now() + 30000).toISOString();
        
        const baseUrl = getBaseUrl();
        const qrUrl = baseUrl + '/scan.php?' + 
            'token=' + encodeURIComponent(token) +
            '&expires=' + encodeURIComponent(expires) +
            '&staff_id=QR_SCAN' +
            '&name=QR_Scan' +
            '&method=QR' +
            '&location=' + encodeURIComponent(currentLocation);
        
        console.log('QR URL:', qrUrl);
        
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
        
        document.getElementById('locationBadge').textContent = '📍 ' + currentLocation;
        
        startCountdown(30);
        
        locationIndex = (locationIndex + 1) % LOCATIONS.length;
        currentLocation = LOCATIONS[locationIndex];
        
    } catch (err) {
        console.error('QR Generation Error:', err);
        box.innerHTML = '<div style="color:#a3432f;padding:20px;">❌ Error generating QR: ' + err.message + '</div>';
    }
}

function startCountdown(seconds) {
    if (timer) clearInterval(timer);
    secondsLeft = seconds;
    const el = document.getElementById('countdown');
    if (el) el.textContent = secondsLeft;
    
    timer = setInterval(() => {
        secondsLeft -= 1;
        if (secondsLeft <= 0) {
            generateQR();
        } else {
            const e = document.getElementById('countdown');
            if (e) e.textContent = secondsLeft;
        }
    }, 1000);
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
                const badgeClass = status === 'in' ? 'badge-in' : 'badge-out';
                const label = status === 'in' ? '✅ In' : '❌ Out';
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
        document.getElementById('staffList').innerHTML = '<div style="color:#a3432f;font-size:13px;">❌ Error loading staff</div>';
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

// Init
generateQR();
loadStaff();
updateClock();
setInterval(loadStaff, 15000);
setInterval(updateClock, 1000);
</script>
</body>
</html>