<?php
require_once 'config.php';
session_start();

// Require admin login
if (!isAdmin()) {
    header('Location: login.php?tab=admin');
    exit;
}

// --- AJAX: Get Staff Data ---
if (isset($_GET['ajax']) && $_GET['ajax'] === 'staff') {
    header('Content-Type: application/json');
    $cacheFile = CACHE_FILE;
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        $data = $cached['data'] ?? ['rows' => []];
    } else {
        $data = ['rows' => []];
    }
    
    $rows = $data['rows'] ?? [];
    array_shift($rows);
    
    $staffStatus = [];
    $staffSet = [];
    foreach ($rows as $row) {
        if (isset($row[1]) && !isset($staffSet[$row[1]])) {
            $staffSet[$row[1]] = true;
            $status = strtolower(str_replace('Check-', '', $row[3]));
            $staffStatus[] = [
                'staff_id' => $row[1],
                'name' => $row[2],
                'status' => $status
            ];
        }
    }
    
    echo json_encode(['success' => true, 'staff' => $staffStatus]);
    exit;
}

// --- Handle Logout ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Admin - QR Display</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
    :root {
        --primary: #2f6f4f;
        --primary-light: #e9f4ee;
        --danger: #a3432f;
        --paper: #fafaf8;
        --line: #dcdcd6;
        --muted: #6b6f76;
        --ink: #1b1f23;
        --radius: 12px;
        --shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        background: var(--paper);
        color: var(--ink);
        padding: 16px;
        min-height: 100vh;
    }
    .container { max-width: 600px; margin: 0 auto; }
    
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0 16px;
        border-bottom: 1px solid var(--line);
        margin-bottom: 16px;
    }
    .header h1 { font-size: 22px; }
    .header .sub { color: var(--muted); font-size: 14px; }
    .header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .qr-card {
        background: #fff;
        border: 2px solid var(--line);
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        margin-bottom: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .qr-card .label {
        font-size: 13px;
        color: var(--muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    #qrbox {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 250px;
        margin: 12px 0 8px;
    }
    #qrbox img, #qrbox canvas { max-width: 100%; height: auto; }
    
    .countdown {
        font-size: 32px;
        font-weight: 700;
        color: var(--primary);
        margin: 4px 0;
    }
    .countdown-label { font-size: 13px; color: var(--muted); }
    .staff-count {
        font-size: 14px;
        color: var(--muted);
        margin-top: 6px;
    }
    .staff-count b { color: var(--primary); }
    
    .refresh-btn {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 24px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        touch-action: manipulation;
        margin-top: 12px;
    }
    .refresh-btn:active { opacity: 0.7; }
    
    .btn-outline {
        background: transparent;
        border: 2px solid var(--line);
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        color: var(--ink);
        text-decoration: none;
        touch-action: manipulation;
    }
    .btn-outline:active { opacity: 0.7; }
    .btn-outline.danger { border-color: var(--danger); color: var(--danger); }
    
    .staff-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-top: 8px;
    }
    .staff-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 12px;
        background: #fff;
        border-radius: 6px;
        border: 1px solid var(--line);
        font-size: 14px;
    }
    .badge-in { color: var(--primary); font-weight: 600; }
    .badge-out { color: var(--danger); font-weight: 600; }
    
    .card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: var(--shadow);
    }
    .card-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--muted);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .spinner {
        text-align: center;
        padding: 20px;
        color: var(--muted);
    }
    .spinner .dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        margin: 0 4px;
        background: var(--primary);
        border-radius: 50%;
        animation: bounce 1.2s infinite ease-in-out;
    }
    .spinner .dot:nth-child(2) { animation-delay: 0.2s; }
    .spinner .dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes bounce {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
        40% { transform: scale(1); opacity: 1; }
    }
    
    .qr-info {
        font-size: 13px;
        color: var(--muted);
        margin-top: 8px;
    }
    .qr-info strong { color: var(--ink); }
    
    @media (max-width: 480px) {
        .header { flex-direction: column; gap: 8px; align-items: stretch; text-align: center; }
        .header-actions { justify-content: center; }
        .staff-grid { grid-template-columns: 1fr; }
        .qr-card { padding: 16px; }
    }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>📋 Admin Dashboard</h1>
            <div class="sub">Staff scan this QR code to clock in/out</div>
        </div>
        <div class="header-actions">
            <a href="?logout=1" class="btn-outline danger">🚪 Logout</a>
        </div>
    </div>

    <!-- QR Display -->
    <div class="qr-card">
        <div class="label">📱 Scan to Clock In/Out</div>
        <div id="qrbox"><div style="color:var(--muted);">⏳ Generating...</div></div>
        <div class="countdown" id="countdown">30</div>
        <div class="countdown-label">seconds until refresh</div>
        <div class="staff-count" id="staffCount">👥 <b>0</b> staff online</div>
        <button class="refresh-btn" onclick="generateQR()">🔄 Refresh Now</button>
        <div class="qr-info">
            💡 Staff will be prompted to login if not already authenticated
        </div>
    </div>

    <!-- Staff Status -->
    <div class="card">
        <div class="card-title">👥 Staff Status</div>
        <div id="staffList">
            <div class="spinner">
                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                <div style="margin-top:6px;font-size:13px;">Loading staff...</div>
            </div>
        </div>
    </div>
</div>

<script>
const APP_SCRIPT_URL = '<?= APP_SCRIPT_URL ?>';
let timer = null;
let secondsLeft = 30;

async function generateQR() {
    const box = document.getElementById('qrbox');
    if (!box) return;
    
    box.innerHTML = '<div style="color:var(--muted);">⏳ Generating...</div>';
    
    try {
        const token = Math.random().toString(36).substring(2, 10);
        const expires = new Date(Date.now() + 30000).toISOString();
        
        // --- FIX: Auto-detect if using localhost or ngrok ---
        const isLocalhost = window.location.hostname === 'localhost' || 
                           window.location.hostname === '127.0.0.1' ||
                           window.location.hostname === '0.0.0.0';
        
        let baseUrl;
        if (isLocalhost) {
            // Use ngrok URL - REPLACE WITH YOUR ACTUAL NGROK URL
            baseUrl = 'https://glance-rancidity-level.ngrok-free.dev/scan.php';
        } else {
            baseUrl = window.location.origin + '/scan.php';
        }
        
        const qrUrl = baseUrl + 
            '?token=' + encodeURIComponent(token) +
            '&expires=' + encodeURIComponent(expires) +
            '&staff_id=QR_SCAN' +
            '&name=QR_Scan' +
            '&method=QR';
        
        console.log('QR URL:', qrUrl);
        
        box.innerHTML = '';
        const div = document.createElement('div');
        box.appendChild(div);
        new QRCode(div, {
            text: qrUrl,
            width: 250,
            height: 250,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.L
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

async function loadStaff() {
    try {
        const response = await fetch('?ajax=staff');
        const data = await response.json();
        if (data.success && data.staff.length > 0) {
            const html = data.staff.map(s => `
                <div class="staff-item">
                    <span>${escapeHtml(s.name)}</span>
                    <span class="${s.status === 'in' ? 'badge-in' : 'badge-out'}">${s.status === 'in' ? '✅ In' : '❌ Out'}</span>
                </div>
            `).join('');
            document.getElementById('staffList').innerHTML = '<div class="staff-grid">' + html + '</div>';
            document.getElementById('staffCount').innerHTML = '👥 <b>' + data.staff.length + '</b> staff online';
        } else {
            document.getElementById('staffList').innerHTML = '<div style="color:var(--muted);font-size:14px;">No staff data available yet. Check-in records will appear here.</div>';
            document.getElementById('staffCount').innerHTML = '👥 <b>0</b> staff online';
        }
    } catch (err) {
        document.getElementById('staffList').innerHTML = '<div style="color:var(--danger);font-size:14px;">❌ Error loading staff</div>';
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

// Init
generateQR();
loadStaff();
setInterval(loadStaff, 15000);
</script>
</body>
</html>