<?php
// ============================================================
// FILE: frontend/public/scan.php
// QR SCAN PROCESSOR - DEBUG VERSION
// ============================================================

// Force error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log every request
error_log("========================================");
error_log("📱 SCAN.PHP CALLED at " . date('Y-m-d H:i:s'));
error_log("📱 GET params: " . json_encode($_GET));

// Load Google Sheets functions
require_once __DIR__ . '/../../backend/src/config/GoogleSheets.php';
session_start();

error_log("📱 Session data: " . json_encode($_SESSION));

// --- Helper Functions ---
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

error_log("📱 QR Params - token: {$token}, staffId: {$staffId}, name: {$name}, location: {$location}");

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
    </head><body><div class="card"><div class="error">⏰</div><h2>QR Code Expired</h2><p style="color:#6b6f76;">This QR code has expired. Please request a new one from the admin screen.</p>
    <a href="' . route_url('/staff-dashboard') . '" class="btn">Return to Dashboard</a>
    </div></body></html>');
    exit;
}

// --- Store QR data in session ---
$_SESSION['qr_scan'] = [
    'token' => $token,
    'expires' => $expires,
    'staff_id' => $staffId,
    'name' => $name,
    'method' => $method,
    'location' => $location
];

error_log("📱 QR data stored in session");

// --- Check Login Status ---
error_log("📱 Checking login status - isStaff(): " . (isStaff() ? 'true' : 'false'));

if (isStaff()) {
    error_log("📱 User is staff, processing scan...");
    processScan();
    exit;
}

if (isAdmin()) {
    error_log("📱 User is admin, redirecting...");
    $_SESSION['message'] = '❌ Admins cannot clock in/out via QR.';
    $_SESSION['message_type'] = 'error';
    header('Location: ' . route_url('/admin-dashboard'));
    exit;
}

// --- Not logged in → redirect to login ---
error_log("📱 User not logged in, redirecting to login...");
$_SESSION['redirect_after_login'] = '/scan.php?' . http_build_query([
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
// PROCESS SCAN - Uses logged-in user
// ============================================================
function processScan() {
    global $token, $expires, $staffId, $name, $method, $location;
    
    error_log("========================================");
    error_log("📱 PROCESSING SCAN");
    error_log("📱 Session: " . json_encode($_SESSION));
    
    // Use the LOGGED-IN user's ID
    $useStaffId = $_SESSION['staff_id'] ?? '';
    $useStaffName = $_SESSION['staff_name'] ?? '';
    
    error_log("📱 Using staff: {$useStaffId} ({$useStaffName})");
    
    // If user isn't properly logged in, redirect
    if (empty($useStaffId)) {
        error_log("❌ No staff ID in session!");
        $_SESSION['message'] = '❌ Please log in first.';
        $_SESSION['message_type'] = 'error';
        header('Location: ' . route_url('/login'));
        exit;
    }
    
    // --- STEP 1: Get current status from CACHE ---
    $currentStatus = getStatusFromCache($useStaffId);
    error_log("📱 Current status from cache: {$currentStatus}");
    
    // Determine new status
    $newStatusDisplay = $currentStatus === 'in' ? 'Check-out' : 'Check-in';
    $actionDisplay = $currentStatus === 'in' ? 'Sign out' : 'Sign in';
    
    error_log("📱 New status: {$newStatusDisplay}, Action: {$actionDisplay}");
    
    // --- STEP 2: Update LOCAL CACHE immediately ---
    updateLocalStatus($useStaffId, $newStatusDisplay, $useStaffName);
    error_log("📱 Local cache updated");
    
    // --- STEP 3: Send success message to user ---
    $_SESSION['qr_result'] = [
        'success' => true,
        'action' => $actionDisplay,
        'name' => $useStaffName,
        'staff_id' => $useStaffId,
        'location' => $location,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // --- STEP 4: Send to Google Sheets ---
    error_log("📤 Sending to Google Sheets: {$useStaffId} ({$useStaffName})");
    error_log("📤 Method: {$method}, Location: {$location}");
    
    $result = sendToGoogleSheets($useStaffId, $useStaffName, $method, $token, $expires);
    
    error_log("📤 Google Sheets response: " . json_encode($result));
    
    // Async backup
    sendAsyncToGoogleSheets($useStaffId, $useStaffName, $method, $token, $expires);
    error_log("📤 Async send triggered");
    
    unset($_SESSION['qr_scan']);
    
    error_log("✅ Scan complete, redirecting to dashboard");
    
    // Redirect back to staff dashboard with success message
    header('Location: ' . route_url('/staff-dashboard') . '?scan=success');
    exit;
}