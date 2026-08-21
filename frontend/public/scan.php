<?php
// ============================================================
// FILE: frontend/public/scan.php
// QR SCAN PROCESSOR - WITH PROPER SESSION LOCKING
// ============================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/src/config/GoogleSheets.php';

// ============================================================
// 🛡️ CRITICAL FIX: SESSION LOCKING
// ============================================================
session_start();

// Use a separate lock file for atomic operations
$lockFile = sys_get_temp_dir() . '/spysee_scan_lock_' . md5($_SESSION['staff_id'] ?? 'unknown');

// Acquire exclusive lock
$fp = fopen($lockFile, 'c+');
if (!flock($fp, LOCK_EX)) {
    error_log("❌ Could not acquire lock for scan processing");
    if (isset($_SESSION['scan_processing']) && $_SESSION['scan_processing'] === true) {
        usleep(100000);
        if (isset($_SESSION['scan_processing']) && $_SESSION['scan_processing'] === true) {
            header('Location: ' . route_url('/staff-dashboard') . '?scan=success');
            exit;
        }
    }
    $_SESSION['scan_processing'] = true;
}

error_log("========================================");
error_log("📱 SCAN.PHP CALLED at " . date('Y-m-d H:i:s'));

function redirect_to($path) {
    header('Location: ' . $path);
    exit;
}

function route_url($path) {
    return '/index.php' . ($path === '/' ? '' : $path);
}

// --- QR Code Parameters ---
$token = $_GET['token'] ?? '';
$expires = $_GET['expires'] ?? '';
$staffId = $_GET['staff_id'] ?? '';
$name = $_GET['name'] ?? '';
$method = $_GET['method'] ?? 'QR';
$location = $_GET['location'] ?? 'HQ Entrance';
$scanId = $_GET['scan_id'] ?? '';

// ============================================================
// 🛡️ SERVER-SIDE COOLDOWN - Atomic with locking
// ============================================================

$useStaffId = $_SESSION['staff_id'] ?? '';
$cooldownKey = 'scan_cooldown_' . $useStaffId;

// Check 1: No scan_id = reject
if (empty($scanId)) {
    error_log("❌ No scan_id provided");
    $_SESSION['message'] = '❌ Invalid scan request.';
    header('Location: ' . route_url('/staff-dashboard'));
    exit;
}

// 🛡️ Check 2: Atomic duplicate check using the lock file
$processedScans = [];
if (file_exists($lockFile . '.processed')) {
    $processedScans = json_decode(file_get_contents($lockFile . '.processed'), true) ?: [];
}
if (in_array($scanId, $processedScans)) {
    error_log("⚠️ DUPLICATE SCAN (atomic lock): scan_id={$scanId}");
    $_SESSION['qr_result'] = [
        'success' => true,
        'action' => 'already_processed',
        'name' => $_SESSION['staff_name'] ?? 'Staff',
        'staff_id' => $useStaffId,
        'location' => $location,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    header('Location: ' . route_url('/staff-dashboard') . '?scan=success');
    exit;
}

// 🛡️ Check 3: COOLDOWN - Prevent multiple scans within 10 seconds
if (!empty($useStaffId) && isset($_SESSION[$cooldownKey])) {
    $timeSinceLastScan = time() - $_SESSION[$cooldownKey];
    if ($timeSinceLastScan < 10) {
        error_log("⏳ COOLDOWN: Staff {$useStaffId} scanned {$timeSinceLastScan}s ago - REJECTING");

        $processedScans[] = $scanId;
        file_put_contents($lockFile . '.processed', json_encode($processedScans));

        $_SESSION['qr_result'] = [
            'success' => true,
            'action' => 'cooldown',
            'name' => $_SESSION['staff_name'] ?? 'Staff',
            'staff_id' => $useStaffId,
            'location' => $location,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        header('Location: ' . route_url('/staff-dashboard') . '?scan=success');
        exit;
    }
}

// 🛡️ Set cooldown timestamp
if (!empty($useStaffId)) {
    $_SESSION[$cooldownKey] = time();
}

// Store this scan_id as processed (atomic)
$processedScans[] = $scanId;
file_put_contents($lockFile . '.processed', json_encode($processedScans));

// Keep the list manageable
if (count($processedScans) > 100) {
    $processedScans = array_slice($processedScans, -50);
    file_put_contents($lockFile . '.processed', json_encode($processedScans));
}

error_log("📱 scan_id {$scanId} processed, cooldown set for {$useStaffId}");

// --- Validate Token ---
if (empty($token) || empty($expires)) {
    error_log("❌ Invalid QR - missing token or expires");
    die('<!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invalid QR</title>
    <style>
        body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#fafaf8;margin:0;}
        .card{background:#fff;padding:40px;border-radius:12px;border:1px solid #dcdcd6;text-align:center;max-width:400px;}
        .error{color:#a3432f;font-size:48px;margin-bottom:12px;}
        .btn{display:inline-block;padding:10px 20px;background:#2f6f4f;color:#fff;text-decoration:none;border-radius:8px;margin-top:16px;border:none;cursor:pointer;}
        .btn:hover{background:#3a8a5f;}
    </style>
    </head><body><div class="card"><div class="error">❌</div><h2>Invalid QR Code</h2><p style="color:#6b6f76;">This QR code is missing required data. Please try again.</p>
    <a href="' . route_url('/staff-dashboard') . '" class="btn">Return to Dashboard</a>
    </div></body></html>');
    exit;
}

// --- Check Expiry ---
$expireTime = strtotime($expires);
if ($expireTime < time()) {
    error_log("❌ QR expired - expireTime: {$expireTime}, now: " . time());
    die('<!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Expired</title>
    <style>
        body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#fafaf8;margin:0;}
        .card{background:#fff;padding:40px;border-radius:12px;border:1px solid #dcdcd6;text-align:center;max-width:400px;}
        .error{color:#a3432f;font-size:48px;margin-bottom:12px;}
        .btn{display:inline-block;padding:10px 20px;background:#2f6f4f;color:#fff;text-decoration:none;border-radius:8px;margin-top:16px;border:none;cursor:pointer;}
        .btn:hover{background:#3a8a5f;}
    </style>
    </head><body><div class="card"><div class="error">⏰</div><h2>QR Code Expired</h2><p style="color:#6b6f76;">This QR code has expired. Please request a new one.</p>
    <a href="' . route_url('/staff-dashboard') . '" class="btn">Return to Dashboard</a>
    </div></body></html>');
    exit;
}

// --- Store QR data in session ---
$_SESSION['qr_scan'] = [
    'scan_id' => $scanId,
    'token' => $token,
    'expires' => $expires,
    'staff_id' => $staffId,
    'name' => $name,
    'method' => $method,
    'location' => $location
];

// --- Check Login Status ---
if (isStaff()) {
    processScan();
    exit;
}

if (isAdmin()) {
    $_SESSION['message'] = '❌ Admins cannot clock in/out via QR.';
    $_SESSION['message_type'] = 'error';
    header('Location: ' . route_url('/admin-dashboard'));
    exit;
}

// --- Not logged in → redirect to login ---
$_SESSION['redirect_after_login'] = '/scan.php?' . http_build_query([
    'scan_id' => $scanId,
    'token' => $token,
    'expires' => $expires,
    'staff_id' => $staffId,
    'name' => $name,
    'method' => $method,
    'location' => $location
]);
header('Location: ' . route_url('/login'));
exit;

// ============================================================
// PROCESS SCAN
// ============================================================
function processScan() {
    global $token, $expires, $staffId, $name, $method, $location, $scanId, $fp, $lockFile;

    $useStaffId = $_SESSION['staff_id'] ?? '';
    $useStaffName = $_SESSION['staff_name'] ?? '';

    if (empty($useStaffId)) {
        $_SESSION['message'] = '❌ Please log in first.';
        header('Location: ' . route_url('/login'));
        exit;
    }

    // --- Get current status from CACHE ---
    $currentStatus = getStatusFromCache($useStaffId);

    // Determine new status
    $newStatusDisplay = $currentStatus === 'in' ? 'Check-out' : 'Check-in';
    $actionDisplay = $currentStatus === 'in' ? 'Sign out' : 'Sign in';

    // --- Update LOCAL CACHE ---
    updateLocalStatus($useStaffId, $newStatusDisplay, $useStaffName);

    // --- Send success message ---
    $_SESSION['qr_result'] = [
        'success' => true,
        'action' => $actionDisplay,
        'name' => $useStaffName,
        'staff_id' => $useStaffId,
        'location' => $location,
        'timestamp' => date('Y-m-d H:i:s'),
        'scan_id' => $scanId
    ];

    // --- Send to Google Sheets (ONLY ONCE!) ---
    sendToGoogleSheets($useStaffId, $useStaffName, $method, $token, $expires);

    unset($_SESSION['qr_scan']);
    
    // 🛡️ Release the lock
    $_SESSION['scan_processing'] = false;
    if ($fp) {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    // Clean up lock files
    @unlink($lockFile);
    @unlink($lockFile . '.processed');

    error_log("✅ Scan complete, redirecting to dashboard");

    // Redirect back to staff dashboard with success message
    header('Location: ' . route_url('/staff-dashboard') . '?scan=success');
    exit;
}