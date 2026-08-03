<?php
require_once 'config.php';
session_start();

// Require staff login
if (!isStaff()) {
    header('Location: login.php');
    exit;
}

// --- AJAX: Refresh Cache ---
if (isset($_GET['refresh_cache'])) {
    $response = @file_get_contents(APP_SCRIPT_URL);
    if ($response !== false) {
        $data = json_decode($response, true);
        file_put_contents(CACHE_FILE, json_encode(['data' => $data, 'fetched_at' => time()]));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// --- AJAX: Dashboard Data ---
if (isset($_GET['ajax']) && $_GET['ajax'] === 'data') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['staff_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    // Fetch fresh data from Google Sheets directly
    $response = @file_get_contents(APP_SCRIPT_URL);
    if ($response === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to fetch data']);
        exit;
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['success']) || !$data['success']) {
        echo json_encode(['success' => false, 'error' => 'Invalid data from Sheets']);
        exit;
    }
    
    // Update cache with fresh data
    file_put_contents(CACHE_FILE, json_encode(['data' => $data, 'fetched_at' => time()]));
    
    $rows = $data['rows'] ?? [];
    array_shift($rows);
    
    $staffId = $_SESSION['staff_id'];
    $staffName = $_SESSION['staff_name'];
    $history = [];
    $staffStatus = [];
    $staffSet = [];
    $today = date('Y-m-d');
    $todayCheckins = 0;
    $currentStatus = 'out';
    
    foreach ($rows as $row) {
        if (isset($row[1])) {
            $sid = $row[1];
            $status = str_replace('Check-', '', $row[3]);
            $statusLower = strtolower($status);
            $timestamp = substr($row[0], 0, 10);
            
            if ($sid === $staffId) {
                $history[] = [
                    'timestamp' => $row[0],
                    'staff_id' => $sid,
                    'name' => $row[2],
                    'status' => $row[3],
                    'method' => $row[4] ?? 'web'
                ];
            }
            
            if (!isset($staffSet[$sid])) {
                $staffSet[$sid] = true;
                $staffStatus[] = [
                    'staff_id' => $sid,
                    'name' => $row[2],
                    'current_status' => $statusLower,
                    'last_action' => $row[0]
                ];
            }
            
            if ($timestamp === $today && $statusLower === 'in') {
                $todayCheckins++;
            }
        }
    }
    
    $history = array_slice(array_reverse($history), 0, 10);
    if (!empty($history)) {
        $currentStatus = strtolower(str_replace('Check-', '', $history[0]['status']));
    }
    
    $in = 0;
    foreach ($staffStatus as $s) {
        if ($s['current_status'] === 'in') $in++;
    }
    
    echo json_encode([
        'success' => true,
        'staff_id' => $staffId,
        'name' => $staffName,
        'current_status' => $currentStatus,
        'history' => $history,
        'all_staff' => $staffStatus,
        'stats' => [
            'total_staff' => count($staffStatus),
            'currently_in' => $in,
            'today_checkins' => $todayCheckins
        ]
    ]);
    exit;
}

// --- Handle Manual Clock ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clock_manual') {
    $staffId = $_POST['staff_id'] ?? '';
    $pin = $_POST['pin'] ?? '';
    
    // NOTE: We cannot verify the PIN here anymore because it's in Sheets.
    // For manual clock, we assume the user is already logged in and just use their session.
    if ($staffId !== $_SESSION['staff_id']) {
        $_SESSION['message'] = '❌ Staff ID mismatch.';
        $_SESSION['message_type'] = 'error';
    } else {
        $cacheFile = CACHE_FILE;
        $currentStatus = 'out';
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            $data = $cached['data'] ?? ['rows' => []];
            $rows = $data['rows'] ?? [];
            array_shift($rows);
            foreach ($rows as $row) {
                if (isset($row[1]) && $row[1] === $staffId) {
                    $currentStatus = strtolower(str_replace('Check-', '', $row[3]));
                    break;
                }
            }
        }
        
        $result = sendToGoogleSheets($staffId, $_SESSION['staff_name'], 'web');
        if ($result['success']) {
            $_SESSION['message'] = "✅ {$_SESSION['staff_name']} — " . ($currentStatus === 'in' ? 'Check-out' : 'Check-in') . " successful!";
            $_SESSION['message_type'] = 'success';
            @unlink(CACHE_FILE);
        } else {
            $_SESSION['message'] = '❌ Error: ' . ($result['error'] ?? 'Unknown error');
            $_SESSION['message_type'] = 'error';
        }
    }
    header('Location: index.php');
    exit;
}

// --- Handle Logout ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// --- Handle Flash Messages ---
$message = '';
$messageType = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Ensure session is valid ---
if (!isset($_SESSION['staff_name']) || !isset($_SESSION['staff_id'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
<title>Staff Dashboard - Attendance</title>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<style>
    :root {
        --primary: #2f6f4f;
        --primary-light: #e9f4ee;
        --danger: #a3432f;
        --danger-light: #fbeceb;
        --ink: #1b1f23;
        --paper: #fafaf8;
        --line: #dcdcd6;
        --muted: #6b6f76;
        --radius: 12px;
        --shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--paper);
        color: var(--ink);
        padding: 16px;
        padding-bottom: 80px;
        min-height: 100vh;
        -webkit-tap-highlight-color: transparent;
    }
    .container { max-width: 480px; margin: 0 auto; }
    
    .header {
        background: var(--primary);
        color: #fff;
        margin: -16px -16px 16px -16px;
        padding: 16px 20px 14px;
        border-radius: 0 0 var(--radius) var(--radius);
        box-shadow: 0 2px 12px rgba(47,111,79,0.25);
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .header h1 { font-size: 20px; font-weight: 700; }
    .header .sub { font-size: 13px; opacity: 0.85; margin-top: 2px; }
    
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
    
    .welcome-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .welcome-row .name { font-size: 20px; font-weight: 700; }
    .welcome-row .id { font-size: 13px; color: var(--muted); }
    .badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    .badge-in { background: var(--primary-light); color: var(--primary); }
    .badge-out { background: var(--danger-light); color: var(--danger); }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        text-align: center;
    }
    .stats-grid .stat-value { font-size: 24px; font-weight: 700; }
    .stats-grid .stat-label { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .stat-in { color: var(--primary); }
    
    .btn {
        display: block;
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: var(--radius);
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.15s, transform 0.1s;
        text-align: center;
        touch-action: manipulation;
    }
    .btn:active { transform: scale(0.97); }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-danger { background: var(--danger); color: #fff; }
    .btn-outline { background: transparent; border: 2px solid var(--line); color: var(--ink); }
    .btn-sm { padding: 10px 14px; font-size: 14px; }
    
    .staff-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }
    .staff-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background: var(--paper);
        border-radius: 6px;
        font-size: 14px;
    }
    .staff-item .name { font-weight: 500; }
    .badge-sm { font-size: 11px; padding: 2px 10px; }
    
    .history-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--line);
        font-size: 14px;
    }
    .history-item:last-child { border-bottom: none; }
    
    .message {
        padding: 12px 16px;
        border-radius: var(--radius);
        margin-bottom: 12px;
        font-size: 14px;
        font-weight: 500;
        text-align: center;
    }
    .message.success { background: var(--primary-light); color: var(--primary); }
    .message.error { background: var(--danger-light); color: var(--danger); }
    
    /* QR Scanner */
    #scanner-container {
        position: relative;
        background: #000;
        border-radius: var(--radius);
        overflow: hidden;
        min-height: 280px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #qr-video {
        width: 100%;
        display: none;
        border-radius: var(--radius);
        background: #000;
        min-height: 280px;
        object-fit: cover;
    }
    #qr-canvas {
        display: none;
    }
    #scanner-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.7);
        color: #fff;
        padding: 20px;
        text-align: center;
        border-radius: var(--radius);
    }
    #scanner-overlay .icon { font-size: 48px; margin-bottom: 12px; }
    #scanner-overlay .text { font-size: 16px; }
    #scanner-overlay .sub { font-size: 13px; opacity: 0.7; margin-top: 4px; }
    #scanner-overlay .btn {
        max-width: 200px;
        margin-top: 16px;
    }
    .scan-result {
        margin-top: 12px;
        padding: 10px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        text-align: center;
        display: none;
    }
    .scan-result.success { background: var(--primary-light); color: var(--primary); display: block; }
    .scan-result.error { background: var(--danger-light); color: var(--danger); display: block; }
    .scan-result.info { background: #f0f0ed; color: var(--muted); display: block; }
    
    .spinner {
        text-align: center;
        padding: 30px 20px;
        color: var(--muted);
    }
    .spinner .dot {
        display: inline-block;
        width: 12px;
        height: 12px;
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
    
    @media (max-width: 480px) {
        .staff-grid { grid-template-columns: 1fr; }
        #scanner-container { min-height: 220px; }
        #qr-video { min-height: 220px; }
    }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="welcome-row">
            <div>
                <h1>👋 <?= htmlspecialchars($_SESSION['staff_name']) ?></h1>
                <div class="sub"><?= htmlspecialchars($_SESSION['staff_id']) ?></div>
            </div>
            <div>
                <span class="badge" id="statusBadge">⏳ Loading...</span>
            </div>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>
    
    <div id="dashboardContent">
        <div class="spinner">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            <div style="margin-top:8px;font-size:13px;color:var(--muted);">Loading...</div>
        </div>
    </div>
</div>

<script>
let currentStatus = 'out';
let scannerActive = false;
let scanning = false;
let stream = null;

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

async function loadDashboard() {
    try {
        const response = await fetch('?ajax=data');
        const data = await response.json();
        if (data.success) {
            renderDashboard(data);
        }
    } catch (err) {
        document.getElementById('dashboardContent').innerHTML = 
            '<div class="message error">❌ Connection error. Pull to refresh.</div>';
    }
}

function renderDashboard(data) {
    currentStatus = data.current_status || 'out';
    const statusBadge = document.getElementById('statusBadge');
    if (statusBadge) {
        statusBadge.className = 'badge ' + (currentStatus === 'in' ? 'badge-in' : 'badge-out');
        statusBadge.textContent = currentStatus === 'in' ? '✅ In' : '❌ Out';
    }
    
    const html = `
        <div class="card">
            <div class="stats-grid">
                <div><div class="stat-value">${data.stats.total_staff}</div><div class="stat-label">Staff</div></div>
                <div><div class="stat-value stat-in">${data.stats.currently_in}</div><div class="stat-label">In</div></div>
                <div><div class="stat-value">${data.stats.today_checkins}</div><div class="stat-label">Today</div></div>
            </div>
        </div>

        <div class="card">
            <form method="POST">
                <input type="hidden" name="staff_id" value="${escapeHtml(data.staff_id)}">
                <input type="hidden" name="pin" value="<?= htmlspecialchars($_SESSION['staff_id'] ?? '') ?>">
                <input type="hidden" name="action" value="clock_manual">
                ${currentStatus === 'in' 
                    ? '<button type="submit" class="btn btn-danger">⏹️ Check Out</button>'
                    : '<button type="submit" class="btn btn-primary">▶️ Check In</button>'}
            </form>
        </div>

        <!-- QR Scanner -->
        <div class="card">
            <div class="card-title">📷 Scan QR Code</div>
            <div id="scanner-container">
                <video id="qr-video" playsinline autoplay></video>
                <canvas id="qr-canvas" style="display:none;"></canvas>
                <div id="scanner-overlay">
                    <div class="icon">📸</div>
                    <div class="text">Tap to scan QR code</div>
                    <div class="sub">Point camera at the admin QR screen</div>
                    <button class="btn btn-primary" onclick="startScanner()" id="scanBtn">
                        📷 Start Camera
                    </button>
                </div>
            </div>
            <div id="scanResult" class="scan-result info">Ready to scan</div>
        </div>

        <div class="card">
            <div class="card-title">👥 All Staff</div>
            <div class="staff-grid">
                ${data.all_staff.map(s => {
                    const st = s.current_status || 'unknown';
                    const badge = st === 'in' ? 'badge-in' : 'badge-out';
                    const label = st === 'in' ? 'In' : 'Out';
                    return `<div class="staff-item">
                        <span class="name">${escapeHtml(s.name)}</span>
                        <span class="badge badge-sm ${badge}">${label}</span>
                    </div>`;
                }).join('')}
            </div>
        </div>

        <div class="card">
            <div class="card-title">📜 My History</div>
            ${data.history.length === 0 
                ? '<div style="color:var(--muted);font-size:13px;">No records yet.</div>'
                : data.history.map(h => `
                    <div class="history-item">
                        <span>${h.status === 'Check-in' ? '✅' : '⏹️'} ${h.status}</span>
                        <span style="color:var(--muted);font-size:13px;">${new Date(h.timestamp).toLocaleTimeString()}</span>
                    </div>
                `).join('')
            }
        </div>

        <a href="?logout=1" class="btn btn-outline" style="text-decoration:none;text-align:center;display:block;margin-top:4px;">🚪 Logout</a>
    `;
    
    document.getElementById('dashboardContent').innerHTML = html;
}

// ============================================================
// QR SCANNER - FIXED VERSION
// ============================================================
async function startScanner() {
    const video = document.getElementById('qr-video');
    const overlay = document.getElementById('scanner-overlay');
    const resultEl = document.getElementById('scanResult');
    const scanBtn = document.getElementById('scanBtn');
    
    if (scannerActive) {
        stopScanner();
        return;
    }
    
    try {
        // Request camera with proper constraints
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 640 },
                height: { ideal: 480 }
            },
            audio: false
        });
        
        // Set up video
        video.srcObject = stream;
        video.style.display = 'block';
        video.style.width = '100%';
        video.style.height = 'auto';
        video.setAttribute('playsinline', '');
        
        // Wait for video to be ready
        await video.play();
        
        // Hide overlay, show video
        overlay.style.display = 'none';
        scanBtn.textContent = '⏹️ Stop Camera';
        
        resultEl.className = 'scan-result info';
        resultEl.textContent = '🔍 Scanning for QR code...';
        resultEl.style.display = 'block';
        
        scannerActive = true;
        scanning = true;
        
        // Start scanning loop
        scanLoop();
        
    } catch (err) {
        console.error('Camera error:', err);
        let errorMsg = '❌ Camera access denied. ';
        if (err.name === 'NotAllowedError') {
            errorMsg += 'Please allow camera access in your browser settings.';
        } else if (err.name === 'NotFoundError') {
            errorMsg += 'No camera found on this device.';
        } else {
            errorMsg += 'Please try again. Error: ' + err.message;
        }
        resultEl.className = 'scan-result error';
        resultEl.textContent = errorMsg;
        resultEl.style.display = 'block';
        overlay.style.display = 'flex';
    }
}

function stopScanner() {
    scannerActive = false;
    scanning = false;
    
    const video = document.getElementById('qr-video');
    const overlay = document.getElementById('scanner-overlay');
    const scanBtn = document.getElementById('scanBtn');
    
    // Stop all tracks
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    
    if (video) {
        video.srcObject = null;
        video.style.display = 'none';
        video.pause();
    }
    
    overlay.style.display = 'flex';
    scanBtn.textContent = '📷 Start Camera';
}

async function scanLoop() {
    if (!scanning || !scannerActive) return;
    
    const video = document.getElementById('qr-video');
    const resultEl = document.getElementById('scanResult');
    const canvas = document.getElementById('qr-canvas');
    
    try {
        // Check if video is ready
        if (video.readyState === video.HAVE_ENOUGH_DATA && 
            video.videoWidth > 0 && 
            video.videoHeight > 0) {
            
            // Set canvas size
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            
            // Draw video frame to canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Get image data for QR detection
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            
            // Try to decode QR code
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert',
                greyScaleWeights: {
                    red: 0.299,
                    green: 0.587,
                    blue: 0.114
                }
            });
            
            if (code && code.data && code.data.length > 0) {
                // QR detected!
                scanning = false;
                scannerActive = false;
                
                resultEl.className = 'scan-result success';
                resultEl.textContent = '✅ QR detected! Processing...';
                
                // Navigate to the QR URL (scan.php)
                try {
                    const qrUrl = code.data;
                    if (qrUrl.startsWith('http://') || qrUrl.startsWith('https://')) {
                        window.location.href = qrUrl;
                    } else {
                        window.location.href = window.location.origin + '/' + qrUrl.replace(/^\//, '');
                    }
                } catch (err) {
                    resultEl.className = 'scan-result error';
                    resultEl.textContent = '❌ Invalid QR code: ' + err.message;
                    // Resume scanning after 3 seconds
                    setTimeout(() => {
                        if (!scannerActive) {
                            scanning = true;
                            scannerActive = true;
                            resultEl.className = 'scan-result info';
                            resultEl.textContent = '🔍 Scanning for QR code...';
                            scanLoop();
                        }
                    }, 3000);
                }
                
                // Stop camera
                stopScanner();
                return;
            }
        }
    } catch (err) {
        // Silently continue
    }
    
    // Continue scanning
    if (scanning && scannerActive) {
        requestAnimationFrame(scanLoop);
    }
}

// Clean up when page unloads
window.addEventListener('beforeunload', function() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
});

loadDashboard();
setInterval(loadDashboard, 15000);

document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        loadDashboard();
    }
});
</script>
</body>
</html>